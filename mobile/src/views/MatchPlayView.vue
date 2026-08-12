<template>
  <section v-if="session" class="play">
    <header class="scoreboard">
      <Button
        class="scoreboard-nav"
        icon="pi pi-chevron-left"
        text
        rounded
        @click="goPrev"
        :disabled="currentEndIndex === 0"
        :aria-label="t('play.nav.prevEnd')"
      />
      <div class="scoreboard-main">
        <span class="end-pill">{{ t('play.end') }} {{ currentEnd.index }}</span>
        <div class="score-row">
          <span class="score-label">{{ t('play.score') }}</span>
          <div class="score-values">
            <strong class="score-value score-value--a">{{ scoreA }}</strong>
            <span class="score-sep">–</span>
            <strong class="score-value score-value--b">{{ scoreB }}</strong>
          </div>
        </div>
      </div>
      <Button
        class="scoreboard-nav"
        icon="pi pi-chevron-right"
        text
        rounded
        @click="goNext"
        :disabled="currentEndIndex >= ends.length - 1"
        :aria-label="t('play.nav.nextEnd')"
      />
    </header>

    <div class="teams">
      <section class="team team--a">
        <h3 class="team-title">{{ teamALabel }}</h3>
        <article v-for="pid in setup.teamA" :key="pid" class="player">
          <button
            type="button"
            class="player-name"
            :class="{ 'player-name--clickable': isTracked(pid) }"
            :disabled="!isTracked(pid)"
            @click="openFormChart(pid)"
          >
            {{ nameFor(pid) }}
          </button>
          <div v-if="isTracked(pid)" class="balls" :style="{ '--ball-count': ballsPerPlayer }">
            <Button
              v-for="i in ballsPerPlayer"
              :key="i"
              :severity="severityFor(noteAt(pid, i - 1))"
              :label="ballLabel(pid, i - 1)"
              text
              rounded
              class="ball"
              :class="{ 'ball--played': noteAt(pid, i - 1) !== undefined, 'ball--empty': noteAt(pid, i - 1) === undefined }"
              :disabled="!canEnterBall(pid, i - 1)"
              @click="openNote($event, pid, i - 1)"
            />
          </div>
        </article>
      </section>

      <section class="team team--b">
        <h3 class="team-title">{{ teamBLabel }}</h3>
        <article v-for="pid in setup.teamB" :key="pid" class="player">
          <button
            type="button"
            class="player-name"
            :class="{ 'player-name--clickable': isTracked(pid) }"
            :disabled="!isTracked(pid)"
            @click="openFormChart(pid)"
          >
            {{ nameFor(pid) }}
          </button>
          <div v-if="isTracked(pid)" class="balls" :style="{ '--ball-count': ballsPerPlayer }">
            <Button
              v-for="i in ballsPerPlayer"
              :key="i"
              :severity="severityFor(noteAt(pid, i - 1))"
              :label="ballLabel(pid, i - 1)"
              text
              rounded
              class="ball"
              :class="{ 'ball--played': noteAt(pid, i - 1) !== undefined, 'ball--empty': noteAt(pid, i - 1) === undefined }"
              :disabled="!canEnterBall(pid, i - 1)"
              @click="openNote($event, pid, i - 1)"
            />
          </div>
        </article>
      </section>
    </div>

    <div class="distance-estimate">
      <span class="distance-estimate-label">{{ t('play.distanceEstimate') }}</span>
      <InputText
        v-model.number="distanceEstimateInput"
        type="number"
        inputmode="decimal"
        step="0.05"
        min="6"
        max="20"
        class="distance-estimate-input"
      />
      <span class="distance-estimate-unit">m</span>
    </div>

    

    <Dialog
      v-model:visible="formChartDialog"
      :header="formChartTitle"
      :modal="true"
      :dismissableMask="true"
      class="play-dialog form-chart-dialog"
    >
      <div v-if="formChart" class="form-chart-content">
        <div class="form-chart-box">
          <Chart type="line" :data="formChart.data" :options="formChart.options" />
        </div>
        <div v-if="formChartSeries && (formChartSeries.pointAverage !== null || formChartSeries.tirAverage !== null)" class="form-chart-stats">
          <div v-if="formChartSeries.pointAverage !== null" class="form-chart-stat">
            <span>{{ t('play.formChart.pointAverage') }}</span>
            <Tag :value="formatFormAvg(formChartSeries.pointAverage)" :severity="avgSeverity(formChartSeries.pointAverage)" />
          </div>
          <div v-if="formChartSeries.tirAverage !== null" class="form-chart-stat">
            <span>{{ t('play.formChart.tirAverage') }}</span>
            <Tag :value="formatFormAvg(formChartSeries.tirAverage)" :severity="avgSeverity(formChartSeries.tirAverage)" />
          </div>
        </div>
      </div>
      <p v-else class="form-chart-empty">{{ t('play.formChart.empty') }}</p>
    </Dialog>

    <OverlayPanel ref="op" class="note-overlay-panel">
      <div class="note-overlay">
        <div class="shot-type">
          <SelectButton v-model="shotType" :options="shotOptions" optionLabel="label" optionValue="value" size="small" />
        </div>
        <div class="note-picker" :class="{ 'note-picker--simple': setup.statisticsMode === 'simple' }">
          <Button
            v-for="opt in notesOptions()"
            :key="String(opt)"
            :label="formatNote(opt)"
            :severity="severityFor(opt)"
            size="small"
            class="note-btn"
            @click="applyNote(opt)"
          />
        </div>
      </div>
    </OverlayPanel>

    <Dialog
      v-model:visible="scoreDialog"
      :modal="false"
      :dismissableMask="true"
      :header="t('play.endScore.title')"
      :closable="true"
      class="play-dialog end-score-dialog"
      position="bottom"
    >
      <div class="end-score">
        <p v-if="!currentEndComplete" class="end-score-hint">{{ t('play.endScore.earlyHint') }}</p>
        <div class="winner">
          <SelectButton v-model="winner" :options="winnerOptions" optionLabel="label" optionValue="value" />
        </div>
        <div class="points">
          <InputText v-model.number="points" type="number" min="1" :max="pointsMax" />
        </div>
        <div class="actions">
          <Button :label="t('play.actions.saveEnd')" @click="confirmEndScore" :disabled="!winner || !points" />
        </div>
      </div>
    </Dialog>

    <footer class="play-actions">
      <div v-if="canValidateEnd && !scoreDialog" class="play-actions-primary">
        <Button class="validate-end-btn" :label="t('play.actions.validateEnd')" icon="pi pi-check" @click="reopenEndDialog" />
        <Button class="cancel-end-btn" :label="t('play.actions.cancelEnd')" icon="pi pi-times" severity="secondary" outlined @click="openCancelDialog" />
      </div>
      <Button class="finish-btn" :label="t('play.actions.finish')" icon="pi pi-flag" severity="secondary" text @click="openFinishDialog" />
    </footer>

    <Dialog v-model:visible="cancelDialog" :modal="true" :header="t('play.cancel.title')" :closable="false" class="play-dialog">
      <div class="cancel-content">
        <p>{{ t('play.cancel.message1') }}</p>
        <p>{{ t('play.cancel.message2') }}</p>
        <p><strong>{{ t('play.cancel.question') }}</strong></p>
        <div class="actions">
          <Button :label="t('play.cancel.abort')" severity="secondary" @click="cancelDialog = false" />
          <Button :label="t('play.cancel.confirm')" severity="danger" @click="confirmCancelEnd" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="finishDialog" :modal="true" :header="t('play.finish.title')" :closable="false" class="play-dialog">
      <div class="finish-content">
        <p>{{ t('play.finish.message1') }}</p>
        <p>{{ t('play.finish.message2') }}</p>
        <div class="actions">
          <Button :label="t('play.finish.abort')" severity="secondary" @click="finishDialog = false" />
          <Button :label="t('play.finish.confirm')" icon="pi pi-check" @click="confirmFinish" />
        </div>
      </div>
    </Dialog>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import OverlayPanel from 'primevue/overlaypanel'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'
import type { TeamSide } from '../models/MatchPlay'
import { DEFAULT_TARGET_SCORE, type MatchType, type ShotType, type StatisticsMode } from '../models/Match'
import { useMatchPlay } from '../composables/useMatchPlay'
import { useMatchTeamLabels } from '../composables/useMatchTeamLabels'
import { formatFormAvg, usePlayerEndFormChart } from '../composables/usePlayerEndFormChart'
import { avgSeverity } from '../composables/usePlayerStatsCharts'
import type { MatchContext } from '../models/MatchContext'
import { clampEndPoints, maxPointsForWinner, suggestEndScore } from '../models/EndScoreSuggestion'
import { matchesService } from '../services/matches'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import { playersService } from '../services/players'
import { useAuthStore } from '../stores/auth'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import { clearMatchDraft, loadMatchDraft, saveMatchDraft } from '../services/matchDraftStorage'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const matchId = Number(route.params.id)

function parseSetupFromQuery(): MatchSetup | null {
  const q = route.query as Record<string, string | undefined>
  const teamA = q.teamA ? q.teamA.split(',').map((x) => Number(x)) : []
  const teamB = q.teamB ? q.teamB.split(',').map((x) => Number(x)) : []
  if (teamA.length === 0 || teamB.length === 0) return null

  const type = (q.type as MatchType) || 'doublette'
  const statisticsMode = (q.statisticsMode as StatisticsMode) || 'standard'
  const trackedPlayers = q.tracked ? q.tracked.split(',').map((x) => Number(x)) : [...teamA, ...teamB]

  const defaultShotTypes: Record<number, 'point' | 'tir'> = {}
  const defaultsParam = q.defaults || ''
  if (defaultsParam) {
    for (const pair of defaultsParam.split(',')) {
      const [pidStr, st] = pair.split(':')
      const pid = Number(pidStr)
      if (pid && (st === 'point' || st === 'tir')) defaultShotTypes[pid] = st
    }
  }

  return {
    id: matchId,
    type,
    targetScore: DEFAULT_TARGET_SCORE,
    statisticsMode,
    teamA,
    teamB,
    trackedPlayers,
    defaultShotTypes,
  }
}

function setupFromDraft(draft: NonNullable<ReturnType<typeof loadMatchDraft>>): MatchSetup {
  return {
    id: draft.id,
    type: draft.type,
    targetScore: draft.targetScore,
    statisticsMode: draft.statisticsMode,
    teamA: draft.teamA,
    teamB: draft.teamB,
    trackedPlayers: draft.trackedPlayers,
    defaultShotTypes: draft.defaultShotTypes,
  }
}

const storedDraft = loadMatchDraft(auth.user?.id ?? null)
const draftForMatch = storedDraft !== null && storedDraft.id === matchId ? storedDraft : null
const querySetup = parseSetupFromQuery()

function resolvePlaySession(): { setup: MatchSetup; initial?: MatchPlayState } | null {
  if (!matchId) return null
  if (draftForMatch) {
    return {
      setup: setupFromDraft(draftForMatch),
      initial: {
        currentEndIndex: draftForMatch.currentEndIndex,
        ends: draftForMatch.ends,
        distanceEstimate: draftForMatch.distanceEstimate,
      },
    }
  }
  if (querySetup && querySetup.teamA.length > 0 && querySetup.teamB.length > 0) {
    return { setup: querySetup }
  }
  return null
}

const session = resolvePlaySession()
if (!session) {
  void router.replace({ name: 'home' })
}

const setup = session?.setup ?? {
  id: 0,
  type: 'doublette' as MatchType,
  targetScore: DEFAULT_TARGET_SCORE,
  statisticsMode: 'standard' as StatisticsMode,
  teamA: [0],
  teamB: [0],
  trackedPlayers: [0],
}

const initialPlayState = session?.initial

function persistPlayState(state: MatchPlayState): void {
  if (!session) return
  saveMatchDraft(session.setup, state, auth.user?.id ?? null)
}

const context = ref<MatchContext | null>(null)
const { teamALabel, teamBLabel } = useMatchTeamLabels(context, t)

const {
  currentEndIndex,
  currentEnd,
  ends,
  scoreA,
  scoreB,
  isFinished,
  ballsPerPlayer,
  goPrevEnd,
  goNextEnd,
  setNoteWithShot,
  setEndScore,
  distanceEstimate,
  setDistanceEstimate,
  notesOptions,
  currentEndComplete,
  canValidateEnd,
  canPlayBallSlot,
  toSubmission,
  cancelCurrentEnd,
} = useMatchPlay(setup, initialPlayState, persistPlayState)

const distanceEstimateInput = computed<number | null>({
  get: () => distanceEstimate.value,
  set: (v) => setDistanceEstimate(v === undefined || Number.isNaN(v as number) ? null : v),
})

const op = ref<InstanceType<typeof OverlayPanel> | null>(null)
const formChartDialog = ref(false)
const selectedChartPlayerId = ref<number | null>(null)

const formChartTitle = computed(() => {
  if (selectedChartPlayerId.value === null) {
    return t('play.formChart.title')
  }
  return t('play.formChart.titleFor', { name: nameFor(selectedChartPlayerId.value) })
})

const endsSnapshot = computed(() => [...ends])
const { series: formChartSeries, chart: formChart } = usePlayerEndFormChart(
  endsSnapshot,
  currentEndIndex,
  selectedChartPlayerId,
  t,
)

function openFormChart(playerId: number) {
  if (!isTracked(playerId)) {
    return
  }
  selectedChartPlayerId.value = playerId
  formChartDialog.value = true
}

const noteCtx = ref<{ playerId: number; noteIndex: number } | null>(null)
const shotType = ref<ShotType>('point')
const shotOptions = computed(() => [
  { label: t('play.shots.point'), value: 'point' as ShotType },
  { label: t('play.shots.tir'), value: 'tir' as ShotType },
])

function shotAt(playerId: number, idx: number): ShotType | undefined {
  const e = currentEnd.value
  const entry = e.balls.find((b) => b.playerId === playerId)
  const v = entry?.shotTypes[idx]
  return v as ShotType | undefined
}

function openNote(event: Event, playerId: number, noteIndex: number) {
  if (!canEnterBall(playerId, noteIndex)) {
    return
  }
  noteCtx.value = { playerId, noteIndex }
  // initialize shot type from existing note or default map
  const existing = shotAt(playerId, noteIndex)
  shotType.value = existing ?? (setup.defaultShotTypes?.[playerId] ?? 'point')
  op.value?.toggle(event)
}

function applyNote(val: -2 | -1 | 0 | 1 | 2) {
  if (!noteCtx.value) return
  setNoteWithShot(noteCtx.value.playerId, noteCtx.value.noteIndex, val, shotType.value)
  op.value?.hide()
}

function formatNote(n: number): string {
  return n > 0 ? `+${n}` : String(n)
}

function severityFor(n?: number): 'danger' | 'warn' | 'secondary' | 'success' | 'help' | undefined {
  switch (n) {
    case -2:
      return 'danger'
    case -1:
      return 'warn'
    case 0:
      return 'secondary'
    case 1:
      return 'success'
    case 2:
      return 'help'
    default:
      return undefined
  }
}

function noteAt(playerId: number, idx: number): number | undefined {
  const e = currentEnd.value
  const entry = e.balls.find((b) => b.playerId === playerId)
  return entry?.notes[idx]
}

function ballLabel(playerId: number, idx: number): string {
  const n = noteAt(playerId, idx)
  if (n === undefined) return '⚪'
  return formatNote(n)
}

function isTracked(playerId: number): boolean {
  return setup.trackedPlayers.includes(playerId)
}

function canEnterBall(playerId: number, noteIndex: number): boolean {
  if (isFinished.value) {
    return false
  }
  return canPlayBallSlot(currentEnd.value, playerId, noteIndex)
}

const scoreDialog = ref(false)
const winner = ref<TeamSide | null>(null)
const points = ref<number | null>(null)

const winnerOptions = computed(() => [
  { label: teamALabel.value, value: 'A' as TeamSide },
  { label: teamBLabel.value, value: 'B' as TeamSide },
])

const pointsMax = computed(() => {
  if (!winner.value) {
    return 13
  }
  return maxPointsForWinner(winner.value, scoreA.value, scoreB.value, setup.targetScore)
})

function applyScoreDialogDefaults(): void {
  const suggestion = suggestEndScore({
    end: currentEnd.value,
    teamA: setup.teamA,
    teamB: setup.teamB,
    scoreA: scoreA.value,
    scoreB: scoreB.value,
    targetScore: setup.targetScore,
  })
  winner.value = suggestion.winner
  points.value = suggestion.points
}

watch(scoreDialog, (visible, wasVisible) => {
  if (visible && !wasVisible) {
    applyScoreDialogDefaults()
  }
})

watch(currentEndComplete, (complete) => {
  if (complete && !currentEnd.value.points) {
    scoreDialog.value = true
  }
})

watch(winner, (selectedWinner) => {
  if (!selectedWinner || points.value === null) {
    return
  }
  points.value = clampEndPoints(
    selectedWinner,
    points.value,
    scoreA.value,
    scoreB.value,
    setup.targetScore,
  )
})

const cancelDialog = ref(false)
function openCancelDialog() { cancelDialog.value = true }
function confirmCancelEnd() {
  cancelCurrentEnd()
  cancelDialog.value = false
}

const finishDialog = ref(false)
function openFinishDialog() { finishDialog.value = true }
async function confirmFinish() {
  finishDialog.value = false
  await onFinish()
}

function confirmEndScore() {
  if (!winner.value || !points.value) return
  const clamped = clampEndPoints(
    winner.value,
    points.value,
    scoreA.value,
    scoreB.value,
    setup.targetScore,
  )
  setEndScore(winner.value, clamped)
  scoreDialog.value = false
}

function reopenEndDialog() {
  scoreDialog.value = true
}

function goPrev() { goPrevEnd() }
function goNext() { goNextEnd() }

async function onFinish() {
  const payload: CompleteMatchRequestDto = toSubmission()
  await matchesService.complete(matchId, payload)
  clearMatchDraft()
  router.push({ name: 'matchSummary', params: { id: matchId } })
}

const names = ref<Record<number, string>>({})

function nameFor(pid: number): string {
  return names.value[pid] ?? `#${pid}`
}

onMounted(async () => {
  if (!session) return

  const ids = Array.from(new Set([...setup.teamA, ...setup.teamB]))
  try {
    const [contextData, ...playerResults] = await Promise.all([
      matchesService.getContext(matchId),
      ...ids.map((id) => playersService.getById(id)),
    ])
    context.value = contextData
    for (const p of playerResults) {
      const full = `${p.firstName} ${p.lastName}`.trim()
      names.value[p.id] = p.nickname ? `${p.nickname} (${full})` : full
    }
  } catch {
    // ignore errors; fallback labels remain
  }
})
</script>

<style scoped>
.play {
  --play-team-a: #15803d;
  --play-team-a-soft: #ecfdf3;
  --play-team-a-border: #86efac;
  --play-team-b: #1d4ed8;
  --play-team-b-soft: #eff6ff;
  --play-team-b-border: #93c5fd;
  --play-note-bad: #b91c1c;
  --play-note-bad-bg: #fef2f2;
  --play-note-warn: #c2410c;
  --play-note-warn-bg: #fff7ed;
  --play-note-neutral: #52525b;
  --play-note-neutral-bg: #f4f4f5;
  --play-note-good: #15803d;
  --play-note-good-bg: #ecfdf3;
  --play-note-great: #1d4ed8;
  --play-note-great-bg: #eff6ff;

  max-width: var(--app-page-max);
  margin: 0 auto;
  min-height: 100dvh;
  display: grid;
  grid-template-rows: auto 1fr auto;
  gap: var(--app-space-md);
  padding: var(--app-space-sm) var(--app-space-lg) calc(var(--app-nav-h) + env(safe-area-inset-bottom, 0px) + var(--app-space-sm));
  background:
    radial-gradient(circle at 0% 0%, rgba(31, 107, 88, 0.07), transparent 38%),
    radial-gradient(circle at 100% 0%, rgba(184, 146, 58, 0.06), transparent 34%),
    var(--app-bg);
}

.scoreboard {
  position: sticky;
  top: 0;
  z-index: 20;
  display: grid;
  grid-template-columns: 2.5rem 1fr 2.5rem;
  align-items: center;
  gap: var(--app-space-xs);
  padding: var(--app-space-md);
  border-radius: var(--app-radius-lg);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  box-shadow: var(--app-shadow-sm);
}

.scoreboard-nav {
  color: var(--app-text);
}

.scoreboard-main {
  display: grid;
  gap: 0.375rem;
  justify-items: center;
  text-align: center;
}

.end-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.1875rem 0.625rem;
  border-radius: 999px;
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.score-row {
  display: grid;
  gap: 0.125rem;
}

.score-label {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--app-text-subtle);
}

.score-values {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
}

.score-value {
  font-size: clamp(2rem, 9vw, 2.75rem);
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.04em;
  font-variant-numeric: tabular-nums;
}

.score-value--a {
  color: var(--play-team-a);
}

.score-value--b {
  color: var(--play-team-b);
}

.score-sep {
  font-size: 1.375rem;
  font-weight: 300;
  color: var(--app-text-subtle);
  opacity: 0.7;
}

.distance-estimate {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.25rem 0;
  color: var(--app-text-subtle);
}

.distance-estimate-label {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.distance-estimate-input {
  width: 4.5rem;
}

.distance-estimate-input :deep(.p-inputtext),
.distance-estimate-input.p-inputtext {
  text-align: center;
  padding: 0.25rem 0.375rem;
  font-size: 0.8125rem;
  font-weight: 600;
}

.distance-estimate-unit {
  font-size: 0.75rem;
  font-weight: 600;
}

.teams {
  display: grid;
  gap: var(--app-space-md);
  align-content: start;
}

.team {
  padding: var(--app-space-md);
  border-radius: var(--app-radius);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  display: grid;
  gap: 0;
  overflow: hidden;
}

.team--a {
  border-left: 4px solid var(--play-team-a-border);
  background: linear-gradient(90deg, var(--play-team-a-soft) 0%, var(--app-surface) 18%);
}

.team--b {
  border-left: 4px solid var(--play-team-b-border);
  background: linear-gradient(90deg, var(--play-team-b-soft) 0%, var(--app-surface) 18%);
}

.team-title {
  margin: 0 0 var(--app-space-sm);
  font-size: 0.8125rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--app-text-muted);
}

.team--a .team-title {
  color: var(--play-team-a);
}

.team--b .team-title {
  color: var(--play-team-b);
}

.player {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-sm) 0;
  border-top: 1px solid var(--app-border);
}

.player:first-of-type {
  border-top: none;
  padding-top: 0;
}

.player-name {
  margin: 0;
  padding: 0;
  border: none;
  background: none;
  font: inherit;
  font-weight: 700;
  font-size: 0.9375rem;
  letter-spacing: -0.01em;
  text-align: left;
  color: var(--app-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.player-name--clickable {
  cursor: pointer;
  color: var(--app-primary);
  text-decoration: underline;
  text-underline-offset: 0.18em;
  text-decoration-color: rgba(31, 107, 88, 0.35);
}

.player-name:disabled {
  cursor: default;
  text-decoration: none;
  color: var(--app-text-muted);
  font-weight: 600;
}

.balls {
  display: grid;
  grid-template-columns: repeat(var(--ball-count, 3), minmax(0, 1fr));
  gap: var(--app-space-xs);
}

.balls :deep(.ball.p-button) {
  width: 100%;
  aspect-ratio: 1;
  min-height: 2.875rem;
  max-height: 3.5rem;
  padding: 0;
  border-radius: 999px;
  border: 2px solid var(--app-border-strong);
  background: var(--app-surface-muted);
  box-shadow: inset 0 1px 2px rgba(28, 36, 48, 0.04);
  transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
}

.balls :deep(.ball.p-button:not(:disabled):active) {
  transform: scale(0.96);
}

.balls :deep(.ball--empty.p-button) {
  border-style: dashed;
  border-color: var(--app-border);
  background: transparent;
  color: var(--app-text-subtle);
  font-size: 1.125rem;
}

.balls :deep(.ball--empty.p-button:not(:disabled)) {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  box-shadow: 0 0 0 2px rgba(31, 107, 88, 0.12);
}

.balls :deep(.ball.p-button:disabled) {
  opacity: 0.42;
  cursor: default;
  box-shadow: none;
}

.balls :deep(.ball.p-button.p-button-danger) {
  background: var(--play-note-bad-bg);
  border-color: #fca5a5;
  color: var(--play-note-bad);
}

.balls :deep(.ball.p-button.p-button-warn) {
  background: var(--play-note-warn-bg);
  border-color: #fdba74;
  color: var(--play-note-warn);
}

.balls :deep(.ball.p-button.p-button-secondary) {
  background: var(--play-note-neutral-bg);
  border-color: #d4d4d8;
  color: var(--play-note-neutral);
}

.balls :deep(.ball.p-button.p-button-success) {
  background: var(--play-note-good-bg);
  border-color: var(--play-team-a-border);
  color: var(--play-note-good);
}

.balls :deep(.ball.p-button.p-button-help) {
  background: var(--play-note-great-bg);
  border-color: var(--play-team-b-border);
  color: var(--play-note-great);
}

.balls :deep(.ball--played .p-button-label) {
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.play-actions {
  position: sticky;
  bottom: calc(var(--app-nav-h) + env(safe-area-inset-bottom, 0px));
  z-index: 15;
  display: grid;
  gap: var(--app-space-xs);
  padding-top: var(--app-space-xs);
  background: linear-gradient(to top, var(--app-bg) 78%, transparent);
}

.play-actions-primary {
  display: grid;
  gap: var(--app-space-xs);
}

.validate-end-btn,
.cancel-end-btn,
.finish-btn {
  width: 100%;
  min-height: var(--app-touch-min);
}

.validate-end-btn {
  min-height: 3rem;
  font-weight: 700;
}

.note-overlay {
  display: grid;
  gap: var(--app-space-md);
  min-width: min(92vw, 20rem);
  padding: var(--app-space-xs);
}

.shot-type :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: 100%;
}

.shot-type :deep(.p-togglebutton) {
  justify-content: center;
  min-height: 2.5rem;
  font-weight: 700;
}

.note-picker {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 0.375rem;
}

.note-picker--simple {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.note-picker :deep(.note-btn.p-button) {
  min-height: 2.75rem;
  border-radius: var(--app-radius-sm);
  font-weight: 800;
  font-size: 0.9375rem;
}

.end-score {
  display: grid;
  gap: var(--app-space-md);
}

.end-score-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  text-align: center;
  line-height: 1.45;
  padding: 0 var(--app-space-sm);
}

.end-score .winner :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: 100%;
}

.end-score .winner :deep(.p-togglebutton) {
  justify-content: center;
  min-height: 2.5rem;
  font-weight: 600;
}

.end-score .points {
  display: grid;
  gap: var(--app-space-xs);
  justify-items: center;
}

.end-score .points :deep(.p-inputtext) {
  width: 5rem;
  text-align: center;
  font-size: 1.25rem;
  font-weight: 700;
}

.end-score .actions :deep(.p-button) {
  width: 100%;
  min-height: var(--app-touch-min);
  font-weight: 700;
}

.cancel-content,
.finish-content {
  display: grid;
  gap: var(--app-space-md);
}

.cancel-content p,
.finish-content p {
  margin: 0;
  line-height: 1.45;
  color: var(--app-text-muted);
}

.cancel-content .actions,
.finish-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.form-chart-content {
  display: grid;
  gap: var(--app-space-md);
}

.form-chart-box {
  height: 220px;
  position: relative;
}

.form-chart-stats {
  display: grid;
  gap: 0.5rem;
  padding-top: var(--app-space-sm);
  border-top: 1px solid var(--app-border);
}

.form-chart-stat {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.form-chart-empty {
  margin: 0;
  text-align: center;
  font-size: 0.875rem;
  color: var(--app-text-muted);
  padding: 1rem 0;
}
</style>

<style>
.note-overlay-panel.p-overlaypanel {
  border-radius: var(--app-radius-lg);
  border: 1px solid var(--app-border);
  box-shadow: var(--app-shadow-md);
  max-width: calc(100vw - 2rem);
}

.note-overlay-panel.p-overlaypanel::before,
.note-overlay-panel.p-overlaypanel::after {
  display: none;
}

.play-dialog.p-dialog {
  border-radius: var(--app-radius-lg);
  overflow: hidden;
  box-shadow: var(--app-shadow-md);
}

.play-dialog .p-dialog-header {
  padding: var(--app-space-md) var(--app-space-lg);
  border-bottom: 1px solid var(--app-border);
}

.play-dialog .p-dialog-content {
  padding: var(--app-space-lg);
}

.end-score-dialog.p-dialog {
  margin: 0;
  width: 100%;
  max-width: var(--app-page-max);
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
</style>
