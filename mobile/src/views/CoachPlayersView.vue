<template>
  <AppPage>
    <PageHeader
      :title="t('coach.title')"
      :subtitle="clubName ?? undefined"
      :back-to="{ name: 'home' }"
    >
      <template #actions>
        <Button
          icon="pi pi-plus"
          rounded
          text
          :aria-label="t('coach.addPlayer.action')"
          @click="openAddDialog"
        />
      </template>
    </PageHeader>

    <StatsCollapsibleFilters :active-count="activeFilterCount">
      <div class="filter-group">
        <span class="filter-group-label">{{ t('stats.filters.nature') }}</span>
        <div class="nature-filter">
          <button
            v-for="opt in natureFilterOptions"
            :key="opt.value"
            type="button"
            class="filter-btn"
            :class="{ active: natureFilter === opt.value }"
            @click="setNatureFilter(opt.value)"
          >
            {{ opt.label }}
          </button>
        </div>
      </div>

      <div class="filter-group">
        <span class="filter-group-label">{{ t('coach.list.sort') }}</span>
        <div class="sort-filter">
          <button
            v-for="opt in sortOptions"
            :key="opt.value"
            type="button"
            class="filter-btn"
            :class="{ active: sortBy === opt.value }"
            @click="sortBy = opt.value"
          >
            {{ opt.label }}
          </button>
        </div>
      </div>

      <StatsDateRangeFilter
        v-model:date-from="dateFrom"
        v-model:date-to="dateTo"
        v-model:date-filter-enabled="dateFilterEnabled"
        :max-date="maxDate"
        show-all-button
        @change="onFiltersChange"
      />
    </StatsCollapsibleFilters>

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <EmptyState
      v-else-if="sortedPlayers.length === 0"
      :title="t('coach.empty')"
      icon="pi pi-users"
    />

    <ul v-else class="player-list">
      <li v-for="player in sortedPlayers" :key="player.id">
        <button type="button" class="player-card app-card" @click="openPlayer(player.id)">
          <div class="player-card-name">
            <span class="player-first">{{ player.firstName }}</span>
            <span class="player-last">{{ player.lastName }}</span>
          </div>
          <div class="player-card-stats">
            <div class="stat-line">
              <span class="stat-label">{{ t('play.shots.point') }}</span>
              <span class="stat-values">
                <Tag
                  v-if="player.point.average !== null"
                  :value="formatAvg(player.point.average)"
                  :severity="avgSeverity(player.point.average)"
                />
                <span v-else class="stat-empty">—</span>
                <span v-if="formatMastersLine(player.point)" class="stat-masters">
                  {{ formatMastersLine(player.point) }}
                </span>
              </span>
            </div>
            <div class="stat-line">
              <span class="stat-label">{{ t('play.shots.tir') }}</span>
              <span class="stat-values">
                <Tag
                  v-if="player.tir.average !== null"
                  :value="formatAvg(player.tir.average)"
                  :severity="avgSeverity(player.tir.average)"
                />
                <span v-else class="stat-empty">—</span>
                <span v-if="formatMastersLine(player.tir)" class="stat-masters">
                  {{ formatMastersLine(player.tir) }}
                </span>
              </span>
            </div>
          </div>
        </button>
      </li>
    </ul>

    <Dialog
      v-model:visible="addDialog"
      :modal="true"
      :header="t('coach.addPlayer.title')"
      :dismissable-mask="true"
      class="coach-add-dialog"
    >
      <form class="add-player-form" @submit.prevent="submitAddPlayer">
        <p v-if="clubName" class="add-player-hint">
          {{ t('coach.addPlayer.hint', { club: clubName }) }}
        </p>

        <section class="add-section">
          <h4 class="add-section-title">{{ t('coach.addPlayer.searchTitle') }}</h4>
          <PlayerSearchSelect
            v-model="selectedPlayer"
            :label="t('coach.addPlayer.searchLabel')"
            :placeholder="t('coach.addPlayer.searchPlaceholder')"
            :empty-hint="t('coach.addPlayer.searchEmpty')"
            :search-players="searchPlayersWithoutClub"
          />
          <Button
            type="button"
            :label="t('coach.addPlayer.attach')"
            icon="pi pi-user-plus"
            class="w-full"
            :loading="attachSubmitting"
            :disabled="!selectedPlayer || attachSubmitting"
            @click="submitAttachPlayer"
          />
          <Message v-if="attachFormError" severity="error">{{ attachFormError }}</Message>
        </section>

        <div class="add-divider">
          <span>{{ t('coach.addPlayer.orCreate') }}</span>
        </div>

        <section class="add-section">
          <h4 class="add-section-title">{{ t('coach.addPlayer.createTitle') }}</h4>
        <label class="app-field">
          <span>{{ t('players.fields.firstName') }}</span>
          <InputText v-model="addForm.firstName" :invalid="!!addErrors.firstName" fluid />
          <small v-if="addErrors.firstName" class="field-error">{{ addErrors.firstName }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('players.fields.lastName') }}</span>
          <InputText v-model="addForm.lastName" :invalid="!!addErrors.lastName" fluid />
          <small v-if="addErrors.lastName" class="field-error">{{ addErrors.lastName }}</small>
        </label>

        <label class="app-field">
          <span>{{ t('players.fields.nickname') }}</span>
          <InputText v-model="addForm.nickname" fluid />
        </label>

        <Message v-if="addFormError" severity="error">{{ addFormError }}</Message>

        <div class="add-player-actions">
          <Button
            type="button"
            :label="t('coach.addPlayer.cancel')"
            severity="secondary"
            outlined
            @click="addDialog = false"
          />
          <Button
            type="submit"
            :label="t('coach.addPlayer.createSubmit')"
            icon="pi pi-check"
            :loading="addSubmitting"
            :disabled="!canSubmitAdd"
          />
        </div>
        </section>
      </form>
    </Dialog>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import StatsCollapsibleFilters from '../components/stats/StatsCollapsibleFilters.vue'
import StatsDateRangeFilter from '../components/stats/StatsDateRangeFilter.vue'
import { avgSeverity, formatAvg } from '../composables/usePlayerStatsCharts'
import { useStatsDateRange, toInputDate } from '../composables/useStatsDateRange'
import type { CoachPlayerListItem, CoachPlayerShotSummary } from '../models/Coach'
import type { MatchNature } from '../models/MatchContext'
import type { Player } from '../models/Player'
import { coachService } from '../services/coach'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

type CoachSortBy = 'name' | 'point' | 'tir'

const loading = ref(true)
const players = ref<CoachPlayerListItem[]>([])
const clubName = ref<string | null>(auth.user?.coachForClubName ?? null)
const natureFilter = ref<MatchNature | 'all'>('all')
const sortBy = ref<CoachSortBy>('name')

const addDialog = ref(false)
const addSubmitting = ref(false)
const attachSubmitting = ref(false)
const addFormError = ref('')
const attachFormError = ref('')
const selectedPlayer = ref<Player | null>(null)
const addForm = reactive({ firstName: '', lastName: '', nickname: '' })
const addErrors = reactive<{ firstName?: string; lastName?: string }>({})

const canSubmitAdd = computed(
  () => addForm.firstName.trim() !== '' && addForm.lastName.trim() !== '' && !addSubmitting.value,
)

const { dateFrom, dateTo, maxDate, dateFilterEnabled, normalizeRange, queryParams } = useStatsDateRange()

const defaultDateFrom = (() => {
  const today = new Date()
  const from = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate())
  return toInputDate(from)
})()

const natureFilterOptions = computed(() => [
  { value: 'all' as const, label: t('stats.filters.all') },
  { value: 'friendly' as const, label: t('context.nature.friendly') },
  { value: 'training' as const, label: t('context.nature.training') },
  { value: 'competition' as const, label: t('context.nature.competition') },
])

const sortOptions = computed(() => [
  { value: 'name' as const, label: t('coach.list.sortName') },
  { value: 'point' as const, label: t('coach.list.sortPoint') },
  { value: 'tir' as const, label: t('coach.list.sortTir') },
])

const activeFilterCount = computed(() => {
  let count = 0
  if (natureFilter.value !== 'all') count++
  if (sortBy.value !== 'name') count++
  if (!dateFilterEnabled.value) count++
  else if (dateFrom.value !== defaultDateFrom || dateTo.value !== maxDate) count++
  return count
})

function shotSuccessRate(summary: CoachPlayerShotSummary): number | null {
  if (summary.successCount === null || summary.totalCount === null || summary.totalCount === 0) {
    return null
  }
  return summary.successCount / summary.totalCount
}

const sortedPlayers = computed(() => {
  const list = [...players.value]
  if (sortBy.value === 'name') {
    return list.sort((a, b) => {
      const last = a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
      if (last !== 0) return last
      return a.firstName.localeCompare(b.firstName, undefined, { sensitivity: 'base' })
    })
  }

  const field = sortBy.value
  return list.sort((a, b) => {
    const rateA = shotSuccessRate(a[field])
    const rateB = shotSuccessRate(b[field])
    if (rateA === null && rateB === null) {
      return a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
    }
    if (rateA === null) return 1
    if (rateB === null) return -1
    if (rateB !== rateA) return rateB - rateA
    const avgA = a[field].average ?? Number.NEGATIVE_INFINITY
    const avgB = b[field].average ?? Number.NEGATIVE_INFINITY
    if (avgB !== avgA) return avgB - avgA
    return a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
  })
})

async function load(): Promise<void> {
  loading.value = true
  try {
    const res = await coachService.listPlayers(queryParams(), natureFilter.value)
    players.value = res.items
    clubName.value = res.clubName
  } finally {
    loading.value = false
  }
}

function setNatureFilter(value: MatchNature | 'all'): void {
  natureFilter.value = value
  void load()
}

function onFiltersChange(): void {
  normalizeRange()
  void load()
}

function playerDetailQuery(): Record<string, string> {
  const query: Record<string, string> = {}
  if (dateFilterEnabled.value) {
    query.from = dateFrom.value
    query.to = dateTo.value
  }
  if (natureFilter.value !== 'all') {
    query.nature = natureFilter.value
  }
  return query
}

function formatMastersLine(summary: CoachPlayerShotSummary): string | null {
  if (summary.successCount === null || summary.totalCount === null || summary.totalCount === 0) {
    return null
  }
  return `(${summary.successCount}/${summary.totalCount})`
}

function openPlayer(playerId: number): void {
  router.push({
    name: 'coachPlayer',
    params: { id: playerId },
    query: playerDetailQuery(),
  })
}

function resetAddForm(): void {
  addForm.firstName = ''
  addForm.lastName = ''
  addForm.nickname = ''
  addErrors.firstName = undefined
  addErrors.lastName = undefined
  addFormError.value = ''
  attachFormError.value = ''
  selectedPlayer.value = null
}

function searchPlayersWithoutClub(query: string): Promise<Player[]> {
  return coachService.searchAvailablePlayers(query)
}

async function submitAttachPlayer(): Promise<void> {
  if (!selectedPlayer.value) {
    return
  }

  attachSubmitting.value = true
  attachFormError.value = ''
  try {
    await coachService.attachPlayer(selectedPlayer.value.id)
    addDialog.value = false
    toast.add({ severity: 'success', summary: t('coach.addPlayer.attachSuccess'), life: 2000 })
    await load()
  } catch {
    attachFormError.value = t('coach.addPlayer.attachError')
  } finally {
    attachSubmitting.value = false
  }
}

function openAddDialog(): void {
  resetAddForm()
  addDialog.value = true
}

async function submitAddPlayer(): Promise<void> {
  addErrors.firstName = addForm.firstName.trim() === '' ? t('players.validations.required') : undefined
  addErrors.lastName = addForm.lastName.trim() === '' ? t('players.validations.required') : undefined
  if (addErrors.firstName || addErrors.lastName) {
    return
  }

  addSubmitting.value = true
  addFormError.value = ''
  try {
    await coachService.createPlayer({
      firstName: addForm.firstName.trim(),
      lastName: addForm.lastName.trim(),
      nickname: addForm.nickname.trim() || undefined,
    })
    addDialog.value = false
    toast.add({ severity: 'success', summary: t('coach.addPlayer.success'), life: 2000 })
    await load()
  } catch {
    addFormError.value = t('coach.addPlayer.error')
  } finally {
    addSubmitting.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<style scoped>
.filter-group {
  display: grid;
  gap: var(--app-space-xs);
}

.filter-group-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.nature-filter,
.sort-filter {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--app-space-xs);
}

@media (min-width: 420px) {
  .nature-filter {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .sort-filter {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

:deep(.date-range-filter) {
  padding: 0;
  border: none;
  box-shadow: none;
  background: transparent;
}

.filter-btn {
  min-width: 0;
  min-height: 2.25rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: #fff;
  font: inherit;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
  cursor: pointer;
  line-height: 1.2;
  white-space: normal;
}

.filter-btn.active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary);
}

:deep(.date-range-filter) {
  padding: 0;
  border: none;
  box-shadow: none;
  background: transparent;
}

.loading {
  display: flex;
  justify-content: center;
  padding: var(--app-space-xl);
}

.player-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-sm);
}

.player-card {
  width: 100%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-md);
  padding: var(--app-space-md);
  text-align: left;
  font: inherit;
  color: inherit;
  transition: transform 0.12s ease;
}

.player-card:active {
  transform: scale(0.99);
}

.player-card-name {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}

.player-first {
  font-weight: 700;
  font-size: 1rem;
}

.player-last {
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.player-card-stats {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  flex-shrink: 0;
  align-items: flex-end;
}

.stat-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stat-label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-muted);
  min-width: 2.5rem;
  text-align: right;
}

.stat-values {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.stat-masters {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  font-variant-numeric: tabular-nums;
}

.stat-empty {
  color: var(--app-text-muted);
}

.add-player-form {
  display: grid;
  gap: var(--app-space-md);
}

.add-player-hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.add-player-actions {
  display: flex;
  gap: var(--app-space-sm);
  justify-content: flex-end;
}

.add-section {
  display: grid;
  gap: var(--app-space-sm);
}

.add-section-title {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 700;
}

.add-divider {
  display: flex;
  align-items: center;
  gap: var(--app-space-sm);
  color: var(--app-text-muted);
  font-size: 0.8125rem;
}

.add-divider::before,
.add-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--app-border);
}

.w-full {
  width: 100%;
}

.field-error {
  color: var(--app-danger);
  font-size: 0.8125rem;
}
</style>
