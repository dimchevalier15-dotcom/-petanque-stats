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

    <section class="filters app-card">
      <StatsDateRangeFilter
        v-model:date-from="dateFrom"
        v-model:date-to="dateTo"
        :max-date="maxDate"
        @change="onDateChange"
      />
    </section>

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <EmptyState
      v-else-if="players.length === 0"
      :title="t('coach.empty')"
      icon="pi pi-users"
    />

    <ul v-else class="player-list">
      <li v-for="player in players" :key="player.id">
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
import StatsDateRangeFilter from '../components/stats/StatsDateRangeFilter.vue'
import { avgSeverity, formatAvg } from '../composables/usePlayerStatsCharts'
import { useStatsDateRange } from '../composables/useStatsDateRange'
import type { CoachPlayerListItem, CoachPlayerShotSummary } from '../models/Coach'
import type { Player } from '../models/Player'
import { coachService } from '../services/coach'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const loading = ref(true)
const players = ref<CoachPlayerListItem[]>([])
const clubName = ref<string | null>(auth.user?.coachForClubName ?? null)

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

const { dateFrom, dateTo, maxDate, queryParams } = useStatsDateRange()

async function load(): Promise<void> {
  loading.value = true
  try {
    const res = await coachService.listPlayers(queryParams())
    players.value = res.items
    clubName.value = res.clubName
  } finally {
    loading.value = false
  }
}

function onDateChange(): void {
  void load()
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
    query: { from: dateFrom.value, to: dateTo.value },
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
.filters {
  margin-bottom: var(--app-space-md);
  padding: var(--app-space-md);
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
