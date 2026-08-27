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
          <div class="score-heading">
            <span class="score-label">{{ t('play.score') }}</span>
            <button
              type="button"
              class="match-timer"
              :class="{ 'match-timer--running': timerRunning }"
              :aria-label="timerRunning ? t('play.timer.pause') : t('play.timer.start')"
              :title="timerRunning ? t('play.timer.pause') : t('play.timer.start')"
              @click="toggleTimer"
            >
              {{ timerDisplay }}
            </button>
          </div>
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
    </header>

    <div class="teams">
      <section class="team team--a">
        <h3 class="team-title">{{ teamALabel }}</h3>
        <template v-for="slot in teamASlots" :key="`slot-a-${slot.originalPlayerId}`">
          <article
            v-if="slot.isSubstitutedOut && hasPlayedBallsInEnd(slot.originalPlayerId, currentEnd)"
            class="player player--out"
          >
            <button
              type="button"
              class="player-name player-name--clickable"
              :title="nameFor(slot.originalPlayerId)"
              @click="openFormChart(slot.originalPlayerId)"
            >
              <span class="player-name-line">
                <span class="player-name-main">{{ shortNameFor(slot.originalPlayerId) }}</span>
              </span>
              <span class="player-sub-badge">{{ t('play.substitution.playedBefore') }}</span>
            </button>
            <div class="balls balls--readonly" :style="{ '--ball-count': ballsPerPlayer }">
              <Button
                v-for="i in ballsPerPlayer"
                :key="`out-a-${slot.originalPlayerId}-${i}`"
                :severity="severityFor(noteAt(slot.originalPlayerId, i - 1))"
                :label="ballLabel(slot.originalPlayerId, i - 1)"
                text
                rounded
                class="ball"
                :class="{ 'ball--played': noteAt(slot.originalPlayerId, i - 1) !== undefined, 'ball--empty': noteAt(slot.originalPlayerId, i - 1) === undefined }"
                disabled
              />
            </div>
          </article>
          <article class="player" :class="{ 'player--sub': slot.isSubstitutedOut }">
            <button
              type="button"
              class="player-name"
              :class="{ 'player-name--clickable': isTracked(slot.activePlayerId) }"
              :disabled="!isTracked(slot.activePlayerId)"
              :title="nameFor(slot.activePlayerId)"
              @click="openFormChart(slot.activePlayerId)"
            >
              <span class="player-name-line">
                <span class="player-name-main">{{ shortNameFor(slot.activePlayerId) }}</span>
              </span>
              <span v-if="showRoles" class="player-role">{{ roleLabel(roleFor(slot.activePlayerId)) }}</span>
              <span v-if="slot.isSubstitutedOut" class="player-sub-badge">
                {{ t('play.substitution.replaces', { name: shortNameFor(slot.originalPlayerId) }) }}
              </span>
            </button>
            <div v-if="isTracked(slot.activePlayerId)" class="balls" :style="{ '--ball-count': ballsPerPlayer }">
              <Button
                v-for="i in ballsPerPlayer"
                :key="`a-${slot.activePlayerId}-${i}`"
                :severity="severityFor(noteAt(slot.activePlayerId, i - 1))"
                :label="ballLabel(slot.activePlayerId, i - 1)"
                text
                rounded
                class="ball"
                :class="{ 'ball--played': noteAt(slot.activePlayerId, i - 1) !== undefined, 'ball--empty': noteAt(slot.activePlayerId, i - 1) === undefined }"
                :disabled="!canEnterBall(slot.activePlayerId, i - 1)"
                @click="openNote($event, slot.activePlayerId, i - 1)"
              />
            </div>
          </article>
        </template>
      </section>

      <section class="team team--b">
        <h3 class="team-title">{{ teamBLabel }}</h3>
        <template v-for="slot in teamBSlots" :key="`slot-b-${slot.originalPlayerId}`">
          <article
            v-if="slot.isSubstitutedOut && hasPlayedBallsInEnd(slot.originalPlayerId, currentEnd)"
            class="player player--out"
          >
            <button
              type="button"
              class="player-name player-name--clickable"
              :title="nameFor(slot.originalPlayerId)"
              @click="openFormChart(slot.originalPlayerId)"
            >
              <span class="player-name-line">
                <span class="player-name-main">{{ shortNameFor(slot.originalPlayerId) }}</span>
              </span>
              <span class="player-sub-badge">{{ t('play.substitution.playedBefore') }}</span>
            </button>
            <div class="balls balls--readonly" :style="{ '--ball-count': ballsPerPlayer }">
              <Button
                v-for="i in ballsPerPlayer"
                :key="`out-b-${slot.originalPlayerId}-${i}`"
                :severity="severityFor(noteAt(slot.originalPlayerId, i - 1))"
                :label="ballLabel(slot.originalPlayerId, i - 1)"
                text
                rounded
                class="ball"
                :class="{ 'ball--played': noteAt(slot.originalPlayerId, i - 1) !== undefined, 'ball--empty': noteAt(slot.originalPlayerId, i - 1) === undefined }"
                disabled
              />
            </div>
          </article>
          <article class="player" :class="{ 'player--sub': slot.isSubstitutedOut }">
            <button
              type="button"
              class="player-name"
              :class="{ 'player-name--clickable': isTracked(slot.activePlayerId) }"
              :disabled="!isTracked(slot.activePlayerId)"
              :title="nameFor(slot.activePlayerId)"
              @click="openFormChart(slot.activePlayerId)"
            >
              <span class="player-name-line">
                <span class="player-name-main">{{ shortNameFor(slot.activePlayerId) }}</span>
              </span>
              <span v-if="showRoles" class="player-role">{{ roleLabel(roleFor(slot.activePlayerId)) }}</span>
              <span v-if="slot.isSubstitutedOut" class="player-sub-badge">
                {{ t('play.substitution.replaces', { name: shortNameFor(slot.originalPlayerId) }) }}
              </span>
            </button>
            <div v-if="isTracked(slot.activePlayerId)" class="balls" :style="{ '--ball-count': ballsPerPlayer }">
              <Button
                v-for="i in ballsPerPlayer"
                :key="`b-${slot.activePlayerId}-${i}`"
                :severity="severityFor(noteAt(slot.activePlayerId, i - 1))"
                :label="ballLabel(slot.activePlayerId, i - 1)"
                text
                rounded
                class="ball"
                :class="{ 'ball--played': noteAt(slot.activePlayerId, i - 1) !== undefined, 'ball--empty': noteAt(slot.activePlayerId, i - 1) === undefined }"
                :disabled="!canEnterBall(slot.activePlayerId, i - 1)"
                @click="openNote($event, slot.activePlayerId, i - 1)"
              />
            </div>
          </article>
        </template>
      </section>
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
            <div class="form-chart-stat-values">
              <Tag :value="formatFormAvg(formChartSeries.pointAverage)" :severity="avgSeverity(formChartSeries.pointAverage)" />
              <span v-if="formChartSeries.pointMasters" class="form-chart-masters">
                ({{ formatMasters(formChartSeries.pointMasters) }})
              </span>
            </div>
          </div>
          <div v-if="formChartSeries.tirAverage !== null" class="form-chart-stat">
            <span>{{ t('play.formChart.tirAverage') }}</span>
            <div class="form-chart-stat-values">
              <Tag :value="formatFormAvg(formChartSeries.tirAverage)" :severity="avgSeverity(formChartSeries.tirAverage)" />
              <span v-if="formChartSeries.tirMasters" class="form-chart-masters">
                ({{ formatMasters(formChartSeries.tirMasters) }})
              </span>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="form-chart-empty">{{ t('play.formChart.empty') }}</p>
    </Dialog>

    <OverlayPanel ref="op" class="note-overlay-panel" appendTo="body">
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
        <p v-else class="end-score-hint">{{ t('play.endScore.zeroHint') }}</p>
        <div class="winner">
          <SelectButton v-model="winner" :options="winnerOptions" optionLabel="label" optionValue="value" />
        </div>
        <div class="points">
          <InputText v-model.number="points" type="number" :min="pointsMin" :max="pointsMax" />
        </div>
        <div class="actions">
          <Button :label="t('play.actions.saveEnd')" @click="confirmEndScore" :disabled="!canSaveEndScore" />
        </div>
      </div>
    </Dialog>

    <div class="play-bottom">
      <details v-if="setup.type === 'triplette'" class="roles-drawer app-card">
        <summary class="roles-drawer-summary">{{ t('play.roles.title') }}</summary>
        <div class="roles-drawer-body">
          <p class="roles-drawer-hint">{{ t('play.roles.hint') }}</p>
          <div class="roles-teams">
            <div class="roles-team">
              <span class="roles-team-label">{{ teamALabel }}</span>
              <div class="roles-chips">
                <button
                  v-for="slot in teamASlots"
                  :key="`role-a-${slot.activePlayerId}`"
                  type="button"
                  class="role-chip"
                  :disabled="!canEditRoles"
                  @click="cyclePlayerRole(slot.activePlayerId)"
                >
                  <span class="role-chip-name">{{ nameFor(slot.activePlayerId) }}</span>
                  <span class="role-chip-role">{{ roleLabel(roleFor(slot.activePlayerId)) }}</span>
                </button>
              </div>
            </div>
            <div class="roles-team">
              <span class="roles-team-label">{{ teamBLabel }}</span>
              <div class="roles-chips">
                <button
                  v-for="slot in teamBSlots"
                  :key="`role-b-${slot.activePlayerId}`"
                  type="button"
                  class="role-chip"
                  :disabled="!canEditRoles"
                  @click="cyclePlayerRole(slot.activePlayerId)"
                >
                  <span class="role-chip-name">{{ nameFor(slot.activePlayerId) }}</span>
                  <span class="role-chip-role">{{ roleLabel(roleFor(slot.activePlayerId)) }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </details>

      <footer class="play-actions">
        <Button
          v-if="canMakeSubstitution"
          class="substitution-btn"
          size="small"
          :label="t('play.substitution.action')"
          icon="pi pi-sync"
          severity="secondary"
          outlined
          @click="openSubstitutionDialog"
        />
        <div v-if="canValidateEnd && !scoreDialog" class="play-actions-primary">
          <Button class="validate-end-btn" size="small" :label="t('play.actions.validateEnd')" icon="pi pi-check" @click="reopenEndDialog" />
          <Button class="cancel-end-btn" size="small" :label="t('play.actions.cancelEnd')" icon="pi pi-times" severity="secondary" outlined @click="openCancelDialog" />
        </div>
        <Button
          class="live-btn"
          size="small"
          :label="t('live.action')"
          icon="pi pi-eye"
          :severity="liveIsActive ? 'success' : 'secondary'"
          outlined
          @click="openLiveShareDialog"
        />
        <Button class="finish-btn" size="small" :label="t('play.actions.finish')" icon="pi pi-flag" severity="secondary" text @click="openFinishDialog" />
      </footer>
    </div>

    <Dialog
      v-model:visible="substitutionDialog"
      :modal="true"
      :header="t('play.substitution.title')"
      :dismissableMask="true"
      class="play-dialog substitution-dialog"
    >
      <div class="substitution-content">
        <p class="substitution-hint">{{ t('play.substitution.hint') }}</p>

        <div v-if="substitutionTeamOptions.length > 1" class="substitution-field">
          <span class="substitution-label">{{ t('play.substitution.team') }}</span>
          <SelectButton
            v-model="substitutionTeam"
            :options="substitutionTeamOptions"
            option-label="label"
            option-value="value"
          />
        </div>

        <div class="substitution-field">
          <span class="substitution-label">{{ t('play.substitution.playerOut') }}</span>
          <SelectButton
            v-model="substitutionOutPlayerId"
            :options="substitutionOutOptions"
            option-label="label"
            option-value="value"
          />
        </div>

        <PlayerSearchSelect
          v-model="substitutionInPlayer"
          :label="t('play.substitution.playerIn')"
          :placeholder="t('play.substitution.searchPlaceholder')"
          :empty-hint="t('play.substitution.searchEmpty')"
        />

        <p v-if="substitutionError" class="substitution-error">{{ substitutionError }}</p>

        <div class="actions">
          <Button :label="t('play.substitution.abort')" severity="secondary" @click="substitutionDialog = false" />
          <Button
            :label="t('play.substitution.confirm')"
            icon="pi pi-check"
            :disabled="!canConfirmSubstitution"
            @click="confirmSubstitution"
          />
        </div>
      </div>
    </Dialog>

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

    <Dialog
      v-model:visible="liveShareDialog"
      :modal="true"
      :header="t('live.share.title')"
      :dismissableMask="true"
      class="play-dialog live-share-dialog"
    >
      <div class="live-share-content">
        <p>{{ t('live.share.hint') }}</p>
        <InputText v-if="liveUrl" :model-value="liveUrl" readonly class="live-share-url" />
        <div class="actions">
          <Button
            v-if="canNativeShare"
            :label="t('live.share.native')"
            icon="pi pi-share-alt"
            @click="shareLiveLink"
          />
          <Button
            :label="liveLinkCopied ? t('live.share.copied') : t('live.share.copy')"
            icon="pi pi-copy"
            severity="secondary"
            outlined
            @click="copyLiveLink"
          />
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
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import type { TeamSide } from '../models/MatchPlay'
import type { Player } from '../models/Player'
import { DEFAULT_TARGET_SCORE, type MatchType, type PlayerRole, type ShotType, type StatisticsMode } from '../models/Match'
import { inferStartingRoles, totalBallsInEnd } from '../utils/matchRoles'
import { useMatchPlay } from '../composables/useMatchPlay'
import { useMatchTimer } from '../composables/useMatchTimer'
import { useMatchTeamLabels } from '../composables/useMatchTeamLabels'
import { formatFormAvg, formatMasters, usePlayerEndFormChart } from '../composables/usePlayerEndFormChart'
import { avgSeverity } from '../composables/usePlayerStatsCharts'
import type { MatchContext } from '../models/MatchContext'
import { clampEndPoints, maxPointsForWinner, maxPointsPerEnd, suggestEndScore } from '../models/EndScoreSuggestion'
import { matchesService } from '../services/matches'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import { playersService } from '../services/players'
import { useAuthStore } from '../stores/auth'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { LiveMatchData } from '../models/LiveMatch'
import { clearMatchDraft, loadMatchDraft, saveMatchDraft } from '../services/matchDraftStorage'
import { useLiveMatchSync } from '../composables/useLiveMatchSync'

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
    startingRoles: inferStartingRoles(type, teamA, teamB, defaultShotTypes),
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
    startingRoles: draft.startingRoles,
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
        currentRoles: draftForMatch.currentRoles,
        substitutions: draftForMatch.substitutions,
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
  startingRoles: {},
}

const initialPlayState = session?.initial
const latestPlayState = ref<MatchPlayState | null>(initialPlayState ?? null)
let syncLiveOnPersist: (() => Promise<void>) | null = null

function persistPlayState(state: MatchPlayState): void {
  if (!session) return
  latestPlayState.value = state
  saveMatchDraft(session.setup, state, auth.user?.id ?? null)
  void syncLiveOnPersist?.()
}

const context = ref<MatchContext | null>(null)
const { teamALabel, teamBLabel } = useMatchTeamLabels(context, t)

const {
  currentEndIndex,
  currentEnd,
  ends,
  scoreA,
  scoreB,
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
  showRoles,
  roleFor,
  shotDefaultFor,
  cyclePlayerRole,
  canEditRoles,
  canMakeSubstitution,
  canSubstituteTeamA,
  canSubstituteTeamB,
  teamASlots,
  teamBSlots,
  applySubstitution,
  hasPlayedBallsInEnd,
  isTracked,
  substitutions,
} = useMatchPlay(setup, initialPlayState, persistPlayState)

const {
  display: timerDisplay,
  running: timerRunning,
  toggle: toggleTimer,
  startIfIdle: startTimerIfIdle,
} = useMatchTimer()

watch(
  () => (ends[0] ? totalBallsInEnd(ends[0]) : 0),
  (count, previous) => {
    if ((previous ?? 0) === 0 && count > 0) {
      startTimerIfIdle()
    }
  },
)

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
  if (currentEndIndex.value === 0 && totalBallsInEnd(currentEnd.value) === 0) {
    startTimerIfIdle()
  }
  noteCtx.value = { playerId, noteIndex }
  // initialize shot type from existing note or default map
  const existing = shotAt(playerId, noteIndex)
  shotType.value = existing ?? shotDefaultFor(playerId)
  op.value?.toggle(event)
}

function roleLabel(role: PlayerRole): string {
  return t(`matches.roles.${role}`)
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

const substitutionDialog = ref(false)
const substitutionTeam = ref<TeamSide | null>(null)
const substitutionOutPlayerId = ref<number | null>(null)
const substitutionInPlayer = ref<Player | null>(null)
const substitutionError = ref('')

const substitutionTeamOptions = computed(() => {
  const options: { label: string; value: TeamSide }[] = []
  if (canSubstituteTeamA.value) {
    options.push({ label: teamALabel.value, value: 'A' })
  }
  if (canSubstituteTeamB.value) {
    options.push({ label: teamBLabel.value, value: 'B' })
  }
  return options
})

const substitutionOutOptions = computed(() => {
  if (!substitutionTeam.value) {
    return []
  }
  const teamIds = substitutionTeam.value === 'A' ? setup.teamA : setup.teamB
  return teamIds.map((playerId) => ({
    label: nameFor(playerId),
    value: playerId,
  }))
})

const canConfirmSubstitution = computed(() => {
  return substitutionTeam.value !== null && substitutionOutPlayerId.value !== null && substitutionInPlayer.value !== null
})

function resetSubstitutionForm(): void {
  substitutionTeam.value = substitutionTeamOptions.value[0]?.value ?? null
  substitutionOutPlayerId.value = substitutionOutOptions.value[0]?.value ?? null
  substitutionInPlayer.value = null
  substitutionError.value = ''
}

function openSubstitutionDialog(): void {
  resetSubstitutionForm()
  substitutionDialog.value = true
}

watch(substitutionTeam, () => {
  substitutionOutPlayerId.value = substitutionOutOptions.value[0]?.value ?? null
  substitutionInPlayer.value = null
  substitutionError.value = ''
})

async function loadPlayerName(playerId: number): Promise<void> {
  if (names.value[playerId]) {
    return
  }
  try {
    const player = await playersService.getById(playerId)
    rememberPlayerName(player)
  } catch {
    // keep fallback label
  }
}

async function confirmSubstitution(): Promise<void> {
  if (!substitutionTeam.value || substitutionOutPlayerId.value === null || !substitutionInPlayer.value) {
    return
  }

  const ok = applySubstitution(substitutionTeam.value, substitutionOutPlayerId.value, substitutionInPlayer.value.id)
  if (!ok) {
    substitutionError.value = t('play.substitution.error')
    return
  }

  await loadPlayerName(substitutionInPlayer.value.id)
  substitutionDialog.value = false
}

const scoreDialog = ref(false)
const cancelDialog = ref(false)
const winner = ref<TeamSide | null>(null)
const points = ref<number | null>(null)

const winnerOptions = computed(() => [
  { label: teamALabel.value, value: 'A' as TeamSide },
  { label: teamBLabel.value, value: 'B' as TeamSide },
])

const scoreForEndCap = computed(() => {
  const end = currentEnd.value
  let a = scoreA.value
  let b = scoreB.value
  if (end && end.canceled !== true && end.winner && end.points !== undefined) {
    if (end.winner === 'A') a -= end.points
    else b -= end.points
  }
  return { scoreA: Math.max(0, a), scoreB: Math.max(0, b) }
})

const pointsMin = computed(() => (currentEndComplete.value ? 0 : 1))

const pointsMax = computed(() => {
  if (!winner.value) {
    return maxPointsPerEnd(setup.type)
  }
  return maxPointsForWinner(
    winner.value,
    scoreForEndCap.value.scoreA,
    scoreForEndCap.value.scoreB,
    setup.targetScore,
    setup.type,
  )
})

const canSaveEndScore = computed(() => {
  if (points.value === null || !Number.isFinite(points.value)) {
    return false
  }
  if (points.value === 0) {
    return currentEndComplete.value
  }
  return winner.value !== null && points.value >= 1
})

function playersForTeamEndScore(team: TeamSide): number[] {
  const slots = team === 'A' ? teamASlots.value : teamBSlots.value
  const ids = new Set<number>()
  for (const slot of slots) {
    if (hasPlayedBallsInEnd(slot.activePlayerId, currentEnd.value)) {
      ids.add(slot.activePlayerId)
    }
    if (hasPlayedBallsInEnd(slot.originalPlayerId, currentEnd.value)) {
      ids.add(slot.originalPlayerId)
    }
  }
  return Array.from(ids)
}

function applyScoreDialogDefaults(): void {
  const end = currentEnd.value
  if (end.canceled !== true && end.winner && end.points !== undefined) {
    winner.value = end.winner
    points.value = end.points
    return
  }

  const suggestion = suggestEndScore({
    end,
    teamA: playersForTeamEndScore('A'),
    teamB: playersForTeamEndScore('B'),
    scoreA: scoreForEndCap.value.scoreA,
    scoreB: scoreForEndCap.value.scoreB,
    targetScore: setup.targetScore,
    type: setup.type,
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
  if (complete && currentEnd.value.points === undefined && currentEnd.value.canceled !== true) {
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
    scoreForEndCap.value.scoreA,
    scoreForEndCap.value.scoreB,
    setup.targetScore,
    setup.type,
    pointsMin.value,
  )
})

function canEnterBall(playerId: number, noteIndex: number): boolean {
  return canPlayBallSlot(currentEnd.value, playerId, noteIndex)
}

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
  if (points.value === null || !Number.isFinite(points.value)) return

  if (points.value === 0) {
    if (!currentEndComplete.value) return
    setEndScore(winner.value ?? 'A', 0)
    scoreDialog.value = false
    return
  }

  if (!winner.value) return
  const clamped = clampEndPoints(
    winner.value,
    points.value,
    scoreForEndCap.value.scoreA,
    scoreForEndCap.value.scoreB,
    setup.targetScore,
    setup.type,
    pointsMin.value,
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
  await syncLiveMatch()
  await finishLiveMatch()
  await matchesService.complete(matchId, payload)
  clearMatchDraft()
  router.push({ name: 'matchSummary', params: { id: matchId } })
}

const names = ref<Record<number, string>>({})
const shortNames = ref<Record<number, string>>({})

function buildLiveMatchData(state: MatchPlayState): LiveMatchData {
  return {
    type: setup.type,
    targetScore: setup.targetScore,
    statisticsMode: setup.statisticsMode,
    teamA: setup.teamA,
    teamB: setup.teamB,
    trackedPlayers: setup.trackedPlayers,
    defaultShotTypes: setup.defaultShotTypes,
    startingRoles: setup.startingRoles,
    currentEndIndex: state.currentEndIndex,
    ends: state.ends,
    distanceEstimate: state.distanceEstimate,
    currentRoles: state.currentRoles,
    substitutions: state.substitutions,
    playerNames: names.value,
    shortPlayerNames: shortNames.value,
    teamALabel: teamALabel.value,
    teamBLabel: teamBLabel.value,
  }
}

const {
  isActive: liveIsActive,
  liveUrl,
  startLive,
  sync: syncLiveMatch,
  finishLive: finishLiveMatch,
  verifyRemoteStatus: verifyLiveMatchStatus,
} = useLiveMatchSync(matchId, () => {
  const state = latestPlayState.value ?? {
    currentEndIndex: currentEndIndex.value,
    ends,
    distanceEstimate: distanceEstimate.value,
    currentRoles: {},
    substitutions,
  }
  return buildLiveMatchData(state)
})
syncLiveOnPersist = syncLiveMatch

const liveShareDialog = ref(false)
const liveLinkCopied = ref(false)
const canNativeShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function'

async function openLiveShareDialog(): Promise<void> {
  liveLinkCopied.value = false
  if (!liveIsActive.value) {
    await startLive()
  }
  if (liveUrl.value) {
    liveShareDialog.value = true
  }
}

async function copyLiveLink(): Promise<void> {
  if (!liveUrl.value) return
  try {
    await navigator.clipboard.writeText(liveUrl.value)
    liveLinkCopied.value = true
  } catch {
    liveLinkCopied.value = false
  }
}

async function shareLiveLink(): Promise<void> {
  if (!liveUrl.value || !canNativeShare) return
  try {
    await navigator.share({
      title: t('live.share.title'),
      url: liveUrl.value,
    })
  } catch {
    // User cancelled or share failed — no blocking error.
  }
}

function rememberPlayerName(player: Player): void {
  const full = `${player.firstName} ${player.lastName}`.trim()
  names.value[player.id] = player.nickname ? `${player.nickname} (${full})` : full
  shortNames.value[player.id] = (player.nickname || player.firstName || full).trim()
}

function nameFor(pid: number): string {
  return names.value[pid] ?? `#${pid}`
}

function shortNameFor(pid: number): string {
  return shortNames.value[pid] ?? nameFor(pid)
}

onMounted(async () => {
  if (!session) return

  const ids = Array.from(new Set([...setup.teamA, ...setup.teamB, ...substitutions.map((sub) => sub.inPlayerId)]))
  try {
    const [contextData, ...playerResults] = await Promise.all([
      matchesService.getContext(matchId),
      ...ids.map((id) => playersService.getById(id)),
    ])
    context.value = contextData
    for (const p of playerResults) {
      rememberPlayerName(p)
    }
  } catch {
    // ignore errors; fallback labels remain
  }

  if (liveIsActive.value) {
    void verifyLiveMatchStatus().then(() => {
      if (liveIsActive.value) {
        void syncLiveMatch()
      }
    })
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
  width: 100%;
  margin: 0 auto;
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  gap: var(--app-space-sm);
  padding: var(--app-space-sm) var(--app-space-sm) var(--app-space-sm);
  overflow: hidden;
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
  padding: var(--app-space-sm) var(--app-space-sm) var(--app-space-xs);
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

.score-heading {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.5rem;
}

.score-label {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--app-text-subtle);
}

.match-timer {
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  font: inherit;
  font-size: 0.75rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
  opacity: 0.75;
}

.match-timer--running {
  opacity: 1;
}

.score-values {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
}

.score-value {
  font-size: clamp(1.75rem, 8vw, 2.5rem);
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
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.125rem 0 0.125rem;
  border-top: 1px solid var(--app-border);
  margin-top: 0.125rem;
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

.roles-drawer {
  padding: 0;
  overflow: hidden;
}

.roles-drawer-summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
  user-select: none;
}

.roles-drawer-summary::-webkit-details-marker {
  display: none;
}

.roles-drawer-summary::after {
  content: '';
  width: 0.35rem;
  height: 0.35rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.15s ease;
  opacity: 0.6;
}

.roles-drawer[open] .roles-drawer-summary::after {
  transform: rotate(-135deg);
}

.roles-drawer-body {
  display: grid;
  gap: 0.5rem;
  padding: 0 0.75rem 0.75rem;
  border-top: 1px solid var(--app-border);
}

.roles-drawer-hint {
  margin: 0.5rem 0 0;
  font-size: 0.6875rem;
  color: var(--app-text-muted);
}

.roles-teams {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.5rem;
}

.roles-team {
  display: grid;
  gap: 0.25rem;
}

.roles-team-label {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-muted);
}

.roles-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.role-chip {
  border: 1px solid var(--app-border);
  border-radius: 999px;
  background: #fff;
  padding: 0.1875rem 0.5rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font: inherit;
  cursor: pointer;
  min-height: 1.75rem;
}

.role-chip-name {
  font-size: 0.75rem;
  font-weight: 600;
}

.role-chip-role {
  font-size: 0.625rem;
  font-weight: 700;
  color: var(--app-primary);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.player-role {
  font-size: 0.5rem;
  font-weight: 700;
  line-height: 1.1;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-primary);
  opacity: 0.8;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.player-sub-badge {
  font-size: 0.625rem;
  font-weight: 600;
  line-height: 1.2;
  color: var(--app-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.player--out {
  opacity: 0.72;
}

.player--out .player-name {
  font-weight: 600;
}

.player--sub .player-name {
  color: var(--app-primary-dark);
}

.balls--readonly :deep(.ball.p-button) {
  opacity: 0.85;
}

.substitution-content {
  display: grid;
  gap: var(--app-space-md);
}

.substitution-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.substitution-field {
  display: grid;
  gap: var(--app-space-xs);
}

.substitution-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-muted);
}

.substitution-field :deep(.p-selectbutton) {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.substitution-error {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-danger, #b91c1c);
}

.substitution-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.substitution-content .actions :deep(.p-button) {
  width: 100%;
  min-height: var(--app-touch-min);
}

.role-chip:disabled {
  opacity: 0.55;
  cursor: default;
}

.play-bottom {
  position: sticky;
  bottom: 0;
  z-index: 15;
  display: grid;
  gap: 0.375rem;
  padding-top: var(--app-space-xs);
  padding-bottom: 0.5rem;
  background: linear-gradient(to top, var(--app-bg) 85%, transparent);
}

.play-actions {
  display: grid;
  gap: 0.25rem;
}

.teams {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.5rem;
  align-items: stretch;
  min-height: 0;
  overflow: auto;
}

.team {
  min-width: 0;
  padding: 0.5rem 0.5rem 0.375rem;
  border-radius: var(--app-radius);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  display: grid;
  align-content: start;
  gap: 0;
}

.team--a {
  border-top: 3px solid var(--play-team-a);
  background: linear-gradient(180deg, var(--play-team-a-soft) 0%, var(--app-surface) 2.5rem);
}

.team--b {
  border-top: 3px solid var(--play-team-b);
  background: linear-gradient(180deg, var(--play-team-b-soft) 0%, var(--app-surface) 2.5rem);
}

.team-title {
  margin: 0 0 0.375rem;
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--app-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.team--a .team-title {
  color: var(--play-team-a);
}

.team--b .team-title {
  color: var(--play-team-b);
  text-align: right;
}

.player {
  display: grid;
  gap: 0.25rem;
  min-width: 0;
  padding: 0.375rem 0;
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
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.125rem;
  min-width: 0;
  font: inherit;
  font-weight: 700;
  font-size: 0.8125rem;
  letter-spacing: -0.01em;
  text-align: left;
  color: var(--app-text);
}

.player-name-line {
  min-width: 0;
  max-width: 100%;
}

.player-name-main {
  display: block;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.player-name--clickable {
  cursor: pointer;
}

.player-name--clickable .player-name-main {
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
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.3rem;
}

.balls :deep(.ball.p-button) {
  width: 2.7rem;
  height: 2.7rem;
  min-height: 2.7rem;
  max-height: 2.7rem;
  flex: 0 0 2.7rem;
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
  font-size: 0.95rem;
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
  font-size: 0.875rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.play-actions-primary {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 0.25rem;
}

.play-actions :deep(.p-button) {
  width: 100%;
  height: 1.75rem;
  min-height: 1.75rem;
  padding: 0 0.5rem;
  font-size: 0.75rem;
}

.play-actions :deep(.p-button-label),
.play-actions :deep(.p-button-icon) {
  font-size: 0.75rem;
}

.validate-end-btn {
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

.live-share-content {
  display: grid;
  gap: var(--app-space-md);
}

.live-share-content p {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.live-share-url {
  width: 100%;
}

.live-share-content .actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem;
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

.form-chart-stat-values {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.form-chart-masters {
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  color: var(--app-text-muted);
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

@media (max-width: 48rem) {
  .p-dialog-mask:has(.end-score-dialog) {
    padding-bottom: max(3rem, env(safe-area-inset-bottom, 0px));
  }
}

html.native-app .p-dialog-mask:has(.end-score-dialog) {
  padding-bottom: max(3rem, env(safe-area-inset-bottom, 0px));
}
</style>
