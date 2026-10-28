<template>
  <AppPage>
    <PageHeader
      :title="pageTitle"
      :subtitle="contextSubtitle ?? undefined"
      :back-to="listBackTo"
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
              <span class="stat-label">{{ t('coach.list.shootingAverage') }}</span>
              <span class="stat-values">
                <Tag
                  v-if="player.shooting.averageSessionScore !== null"
                  :value="`${player.shooting.averageSessionScore}/100`"
                  :severity="sessionScoreSeverity(player.shooting.averageSessionScore)"
                />
                <span v-else class="stat-empty">—</span>
              </span>
            </div>
            <div class="stat-line">
              <span class="stat-label">{{ t('coach.list.shootingBest') }}</span>
              <span class="stat-values">
                <span v-if="player.shooting.bestSessionScore !== null" class="stat-best">
                  {{ player.shooting.bestSessionScore }}/100
                </span>
                <span v-else class="stat-empty">—</span>
              </span>
            </div>
            <div v-if="player.shooting.sessionsCount > 0" class="stat-sessions">
              {{ t('coach.list.shootingSessions', { n: player.shooting.sessionsCount }) }}
            </div>
          </div>
        </button>
      </li>
    </ul>

    <Dialog
      v-model:visible="addDialog"
      :modal="true"
      :header="addDialogTitle"
      :dismissable-mask="true"
      class="coach-add-dialog"
    >
      <form class="add-player-form" @submit.prevent="submitAddPlayer">
        <p v-if="contextSubtitle" class="add-player-hint">
          {{ addPlayerHint }}
        </p>

        <section class="add-section">
          <h4 class="add-section-title">{{ addSearchTitle }}</h4>
          <PlayerSearchSelect
            v-model="selectedPlayer"
            :label="t('coach.addPlayer.searchLabel')"
            :placeholder="t('coach.addPlayer.searchPlaceholder')"
            :empty-hint="addSearchEmpty"
            :search-players="searchPlayersWithoutClub"
          />
          <Button
            type="button"
            :label="addAttachLabel"
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
            :label="addCreateSubmitLabel"
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
import { useCoachSpaceFromRoute } from '../composables/useCoachSpace'
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
import { sessionScoreSeverity } from '../composables/useShootingStatsCharts'
import { useStatsDateRange, toInputDate } from '../composables/useStatsDateRange'
import type { CoachPlayerListItem } from '../models/Coach'
import type { Player } from '../models/Player'
import { coachService } from '../services/coach'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const {
  kind: coachSpaceKind,
  selectionGlobaleId,
  playerDetailRouteName,
} = useCoachSpaceFromRoute()

type CoachSortBy = 'name' | 'shootingAvg' | 'shootingBest'

const loading = ref(true)
const players = ref<CoachPlayerListItem[]>([])
const contextSubtitle = ref<string | null>(
  coachSpaceKind.value === 'club' ? (auth.user?.coachForClubName ?? null) : null,
)

const pageTitle = computed(() =>
  coachSpaceKind.value === 'selection' ? t('selectionGlobale.title') : t('coach.title'),
)

const listBackTo = computed(() =>
  coachSpaceKind.value === 'selection'
    ? { name: 'selectionGlobaleHub' }
    : { name: 'home' },
)

const coachSpaceQuery = computed(() =>
  selectionGlobaleId.value ? { selectionGlobaleId: selectionGlobaleId.value } : undefined,
)

const isSelectionSpace = computed(() => coachSpaceKind.value === 'selection')

const addDialogTitle = computed(() =>
  isSelectionSpace.value ? t('selectionGlobale.addPlayer.title') : t('coach.addPlayer.title'),
)

const addPlayerHint = computed(() => {
  if (isSelectionSpace.value) {
    return t('selectionGlobale.addPlayer.hint', { selection: contextSubtitle.value ?? '' })
  }
  return t('coach.addPlayer.hint', { club: contextSubtitle.value ?? '' })
})

const addSearchTitle = computed(() =>
  isSelectionSpace.value ? t('selectionGlobale.addPlayer.searchTitle') : t('coach.addPlayer.searchTitle'),
)

const addSearchEmpty = computed(() =>
  isSelectionSpace.value ? t('selectionGlobale.addPlayer.searchEmpty') : t('coach.addPlayer.searchEmpty'),
)

const addAttachLabel = computed(() =>
  isSelectionSpace.value ? t('selectionGlobale.addPlayer.attach') : t('coach.addPlayer.attach'),
)

const addCreateSubmitLabel = computed(() =>
  isSelectionSpace.value ? t('selectionGlobale.addPlayer.createSubmit') : t('coach.addPlayer.createSubmit'),
)
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

const sortOptions = computed(() => [
  { value: 'name' as const, label: t('coach.list.sortName') },
  { value: 'shootingAvg' as const, label: t('coach.list.sortShootingAvg') },
  { value: 'shootingBest' as const, label: t('coach.list.sortShootingBest') },
])

const activeFilterCount = computed(() => {
  let count = 0
  if (sortBy.value !== 'name') count++
  if (!dateFilterEnabled.value) count++
  else if (dateFrom.value !== defaultDateFrom || dateTo.value !== maxDate) count++
  return count
})

const sortedPlayers = computed(() => {
  const list = [...players.value]
  if (sortBy.value === 'name') {
    return list.sort((a, b) => {
      const last = a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
      if (last !== 0) return last
      return a.firstName.localeCompare(b.firstName, undefined, { sensitivity: 'base' })
    })
  }

  if (sortBy.value === 'shootingAvg') {
    return list.sort((a, b) => {
      const avgA = a.shooting.averageSessionScore
      const avgB = b.shooting.averageSessionScore
      if (avgA === null && avgB === null) {
        return a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
      }
      if (avgA === null) return 1
      if (avgB === null) return -1
      if (avgB !== avgA) return avgB - avgA
      return a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
    })
  }

  return list.sort((a, b) => {
    const bestA = a.shooting.bestSessionScore
    const bestB = b.shooting.bestSessionScore
    if (bestA === null && bestB === null) {
      return a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
    }
    if (bestA === null) return 1
    if (bestB === null) return -1
    if (bestB !== bestA) return bestB - bestA
    return a.lastName.localeCompare(b.lastName, undefined, { sensitivity: 'base' })
  })
})

async function load(): Promise<void> {
  loading.value = true
  try {
    const res = await coachService.listPlayers(queryParams(), 'all', coachSpaceQuery.value)
    players.value = res.items
    contextSubtitle.value =
      res.selectionGlobaleName ?? res.clubName ?? contextSubtitle.value
  } finally {
    loading.value = false
  }
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
  return query
}

function openPlayer(playerId: number): void {
  const params: Record<string, string | number> = { id: playerId }
  if (selectionGlobaleId.value) {
    params.selectionId = selectionGlobaleId.value
  }
  router.push({
    name: playerDetailRouteName.value,
    params,
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
  return coachService.searchAvailablePlayers(query, coachSpaceQuery.value)
}

async function submitAttachPlayer(): Promise<void> {
  if (!selectedPlayer.value) {
    return
  }

  attachSubmitting.value = true
  attachFormError.value = ''
  try {
    await coachService.attachPlayer(selectedPlayer.value.id, coachSpaceQuery.value)
    addDialog.value = false
    toast.add({
      severity: 'success',
      summary: isSelectionSpace.value
        ? t('selectionGlobale.addPlayer.attachSuccess')
        : t('coach.addPlayer.attachSuccess'),
      life: 2000,
    })
    await load()
  } catch {
    attachFormError.value = isSelectionSpace.value
      ? t('selectionGlobale.addPlayer.attachError')
      : t('coach.addPlayer.attachError')
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
    await coachService.createPlayer(
      {
        firstName: addForm.firstName.trim(),
        lastName: addForm.lastName.trim(),
        nickname: addForm.nickname.trim() || undefined,
      },
      coachSpaceQuery.value,
    )
    addDialog.value = false
    toast.add({
      severity: 'success',
      summary: isSelectionSpace.value ? t('selectionGlobale.addPlayer.success') : t('coach.addPlayer.success'),
      life: 2000,
    })
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

.stat-best {
  font-size: 0.875rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.stat-sessions {
  font-size: 0.6875rem;
  color: var(--app-text-muted);
  text-align: right;
  margin-top: 0.125rem;
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
