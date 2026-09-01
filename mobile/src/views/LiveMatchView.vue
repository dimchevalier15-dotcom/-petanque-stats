<template>
  <section class="live-view">
    <header class="live-header">
      <div class="live-header-main">
        <h1>{{ pageTitle }}</h1>
        <p v-if="isLiveActive && lastUpdatedLabel" class="live-updated">{{ lastUpdatedLabel }}</p>
        <p v-else-if="isFinished" class="live-finished-badge">{{ t('live.view.finished') }}</p>
      </div>
      <LanguageSwitcher class="live-lang-switcher" />
    </header>

    <div v-if="loading" class="live-state">
      <p>{{ t('live.view.loading') }}</p>
    </div>

    <div v-else-if="notFound" class="live-state live-state--error">
      <p>{{ t('live.view.notFound') }}</p>
    </div>

    <template v-else-if="matchData">
      <section v-if="isFinished" class="recap-banner">
        <p v-if="winnerLabel" class="recap-winner">{{ winnerLabel }}</p>
        <div class="recap-score">
          <span class="recap-team">{{ teamALabel }}</span>
          <strong class="recap-score-values">
            <span class="score-value--a">{{ scoreA }}</span>
            <span class="score-sep">–</span>
            <span class="score-value--b">{{ scoreB }}</span>
          </strong>
          <span class="recap-team">{{ teamBLabel }}</span>
        </div>
      </section>

      <template v-if="isFinished">
        <div class="teams teams--recap">
          <section class="team team--a">
            <h3 class="team-title" :title="teamALabel">{{ teamALabel }}</h3>
            <article v-for="playerId in recapTeamAPlayers" :key="`recap-a-${playerId}`" class="recap-player">
              <span class="recap-player-name" :title="nameFor(playerId)">{{ shortNameFor(playerId) }}</span>
              <div class="recap-player-stats">
                <span v-if="pointMastersLabel(playerId)" class="recap-stat recap-stat--point">
                  <span class="recap-stat-label">{{ t('play.shots.point') }}</span>
                  <span class="recap-stat-value">{{ pointMastersLabel(playerId) }}</span>
                </span>
                <span v-if="tirMastersLabel(playerId)" class="recap-stat recap-stat--tir">
                  <span class="recap-stat-label">{{ t('play.shots.tir') }}</span>
                  <span class="recap-stat-value">{{ tirMastersLabel(playerId) }}</span>
                </span>
                <span v-if="cochonnetMastersLabel(playerId)" class="recap-stat recap-stat--cochonnet">
                  <span class="recap-stat-label">{{ t('play.shots.cochonnet') }}</span>
                  <span class="recap-stat-value">{{ cochonnetMastersLabel(playerId) }}</span>
                </span>
              </div>
            </article>
          </section>

          <section class="team team--b">
            <h3 class="team-title" :title="teamBLabel">{{ teamBLabel }}</h3>
            <article v-for="playerId in recapTeamBPlayers" :key="`recap-b-${playerId}`" class="recap-player recap-player--right">
              <span class="recap-player-name" :title="nameFor(playerId)">{{ shortNameFor(playerId) }}</span>
              <div class="recap-player-stats">
                <span v-if="pointMastersLabel(playerId)" class="recap-stat recap-stat--point">
                  <span class="recap-stat-label">{{ t('play.shots.point') }}</span>
                  <span class="recap-stat-value">{{ pointMastersLabel(playerId) }}</span>
                </span>
                <span v-if="tirMastersLabel(playerId)" class="recap-stat recap-stat--tir">
                  <span class="recap-stat-label">{{ t('play.shots.tir') }}</span>
                  <span class="recap-stat-value">{{ tirMastersLabel(playerId) }}</span>
                </span>
                <span v-if="cochonnetMastersLabel(playerId)" class="recap-stat recap-stat--cochonnet">
                  <span class="recap-stat-label">{{ t('play.shots.cochonnet') }}</span>
                  <span class="recap-stat-value">{{ cochonnetMastersLabel(playerId) }}</span>
                </span>
              </div>
            </article>
          </section>
        </div>
      </template>

      <template v-else>
      <header class="scoreboard">
        <Button
          class="scoreboard-nav"
          icon="pi pi-chevron-left"
          text
          rounded
          :disabled="viewEndIndex === 0"
          :aria-label="t('play.nav.prevEnd')"
          @click="viewEndIndex--"
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
          :disabled="viewEndIndex >= matchData.ends.length - 1"
          :aria-label="t('play.nav.nextEnd')"
          @click="viewEndIndex++"
        />
        <div v-if="displayDistance !== null" class="distance-estimate">
          <span class="distance-estimate-label">{{ t('play.distanceEstimate') }}</span>
          <span class="distance-estimate-value">{{ formatDistance(displayDistance) }}</span>
          <span class="distance-estimate-unit">m</span>
        </div>
      </header>

      <p v-if="currentEnd.canceled" class="end-canceled">{{ t('play.endCanceled') }}</p>

      <div class="teams">
        <section class="team team--a">
          <h3 class="team-title" :title="teamALabel">{{ teamALabel }}</h3>
          <template v-for="slot in teamASlots" :key="`slot-a-${slot.originalPlayerId}`">
            <article
              v-if="slot.isSubstitutedOut && hasPlayedBallsInEnd(slot.originalPlayerId, currentEnd)"
              class="player player--out"
            >
              <div class="player-name" :title="nameFor(slot.originalPlayerId)">
                <span class="player-name-line">
                  <span class="player-name-main">{{ shortNameFor(slot.originalPlayerId) }}</span>
                  <span v-if="mastersLabel(slot.originalPlayerId)" class="player-masters">
                    {{ mastersLabel(slot.originalPlayerId) }}
                  </span>
                </span>
                <span class="player-sub-badge">{{ t('play.substitution.playedBefore') }}</span>
              </div>
              <div class="balls balls--readonly">
                <div
                  v-for="i in ballsPerPlayer"
                  :key="`out-a-${slot.originalPlayerId}-${i}`"
                  class="ball-wrap"
                >
                  <Button
                    :severity="severityFor(noteAt(slot.originalPlayerId, i - 1))"
                    :label="ballLabel(slot.originalPlayerId, i - 1)"
                    text
                    rounded
                    class="ball"
                    :class="ballStateClass(slot.originalPlayerId, i - 1)"
                    disabled
                  />
                  <span v-if="distanceLabel(slot.originalPlayerId, i - 1)" class="ball-distance">
                    {{ distanceLabel(slot.originalPlayerId, i - 1) }}
                  </span>
                </div>
              </div>
            </article>
            <article class="player" :class="{ 'player--sub': slot.isSubstitutedOut }">
              <div class="player-name" :title="nameFor(slot.activePlayerId)">
                <span class="player-name-line">
                  <span class="player-name-main">{{ shortNameFor(slot.activePlayerId) }}</span>
                  <span v-if="mastersLabel(slot.activePlayerId)" class="player-masters">
                    {{ mastersLabel(slot.activePlayerId) }}
                  </span>
                </span>
                <span v-if="showRoles" class="player-role">{{ roleLabel(roleFor(slot.activePlayerId)) }}</span>
                <span v-if="slot.isSubstitutedOut" class="player-sub-badge">
                  {{ t('play.substitution.replaces', { name: shortNameFor(slot.originalPlayerId) }) }}
                </span>
              </div>
              <div class="balls balls--readonly">
                <div
                  v-for="i in ballsPerPlayer"
                  :key="`a-${slot.activePlayerId}-${i}`"
                  class="ball-wrap"
                >
                  <Button
                    :severity="severityFor(noteAt(slot.activePlayerId, i - 1))"
                    :label="ballLabel(slot.activePlayerId, i - 1)"
                    text
                    rounded
                    class="ball"
                    :class="ballStateClass(slot.activePlayerId, i - 1)"
                    disabled
                  />
                  <span v-if="distanceLabel(slot.activePlayerId, i - 1)" class="ball-distance">
                    {{ distanceLabel(slot.activePlayerId, i - 1) }}
                  </span>
                </div>
              </div>
            </article>
          </template>
        </section>

        <section class="team team--b">
          <h3 class="team-title" :title="teamBLabel">{{ teamBLabel }}</h3>
          <template v-for="slot in teamBSlots" :key="`slot-b-${slot.originalPlayerId}`">
            <article
              v-if="slot.isSubstitutedOut && hasPlayedBallsInEnd(slot.originalPlayerId, currentEnd)"
              class="player player--out"
            >
              <div class="player-name player-name--right" :title="nameFor(slot.originalPlayerId)">
                <span class="player-name-line">
                  <span class="player-name-main">{{ shortNameFor(slot.originalPlayerId) }}</span>
                  <span v-if="mastersLabel(slot.originalPlayerId)" class="player-masters">
                    {{ mastersLabel(slot.originalPlayerId) }}
                  </span>
                </span>
                <span class="player-sub-badge">{{ t('play.substitution.playedBefore') }}</span>
              </div>
              <div class="balls balls--readonly">
                <div
                  v-for="i in ballsPerPlayer"
                  :key="`out-b-${slot.originalPlayerId}-${i}`"
                  class="ball-wrap"
                >
                  <Button
                    :severity="severityFor(noteAt(slot.originalPlayerId, i - 1))"
                    :label="ballLabel(slot.originalPlayerId, i - 1)"
                    text
                    rounded
                    class="ball"
                    :class="ballStateClass(slot.originalPlayerId, i - 1)"
                    disabled
                  />
                  <span v-if="distanceLabel(slot.originalPlayerId, i - 1)" class="ball-distance">
                    {{ distanceLabel(slot.originalPlayerId, i - 1) }}
                  </span>
                </div>
              </div>
            </article>
            <article class="player" :class="{ 'player--sub': slot.isSubstitutedOut }">
              <div class="player-name player-name--right" :title="nameFor(slot.activePlayerId)">
                <span class="player-name-line">
                  <span class="player-name-main">{{ shortNameFor(slot.activePlayerId) }}</span>
                  <span v-if="mastersLabel(slot.activePlayerId)" class="player-masters">
                    {{ mastersLabel(slot.activePlayerId) }}
                  </span>
                </span>
                <span v-if="showRoles" class="player-role">{{ roleLabel(roleFor(slot.activePlayerId)) }}</span>
                <span v-if="slot.isSubstitutedOut" class="player-sub-badge">
                  {{ t('play.substitution.replaces', { name: shortNameFor(slot.originalPlayerId) }) }}
                </span>
              </div>
              <div class="balls balls--readonly">
                <div
                  v-for="i in ballsPerPlayer"
                  :key="`b-${slot.activePlayerId}-${i}`"
                  class="ball-wrap"
                >
                  <Button
                    :severity="severityFor(noteAt(slot.activePlayerId, i - 1))"
                    :label="ballLabel(slot.activePlayerId, i - 1)"
                    text
                    rounded
                    class="ball"
                    :class="ballStateClass(slot.activePlayerId, i - 1)"
                    disabled
                  />
                  <span v-if="distanceLabel(slot.activePlayerId, i - 1)" class="ball-distance">
                    {{ distanceLabel(slot.activePlayerId, i - 1) }}
                  </span>
                </div>
              </div>
            </article>
          </template>
        </section>
      </div>
      </template>

      <section v-if="isFinished && scoredEnds.length > 0" class="ends-recap">
        <h2 class="ends-recap-title">{{ t('live.view.endsRecap') }}</h2>
        <ul class="ends-recap-list">
          <li v-for="end in scoredEnds" :key="end.index" class="ends-recap-item">
            <span class="ends-recap-label">{{ t('play.end') }} {{ end.index }}</span>
            <span v-if="end.canceled" class="ends-recap-result ends-recap-result--canceled">
              {{ t('summary.endGrid.canceled') }}
            </span>
            <span v-else class="ends-recap-result">
              {{ endResultLabel(end) }}
            </span>
          </li>
        </ul>
      </section>

      <LiveInstallAppPromo v-if="isFinished" />
    </template>
  </section>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import LanguageSwitcher from '../components/layout/LanguageSwitcher.vue'
import LiveInstallAppPromo from '../components/live/LiveInstallAppPromo.vue'
import type { PlayerRole } from '../models/Match'
import type { LiveMatchData } from '../models/LiveMatch'
import type { EndRecord } from '../models/MatchPlay'
import { liveMatchesService } from '../services/liveMatches'
import { formatMasters, playerCochonnetMastersFromEnds, playerMastersFromEnds, playerShotMastersFromEnds } from '../composables/matchSuccessRate'
import { teamSlotsForEnd } from '../utils/matchSubstitutions'

const POLL_INTERVAL_MS = 5000

const { t, locale } = useI18n()
const route = useRoute()
const uuid = String(route.params.uuid)

const loading = ref(true)
const notFound = ref(false)
const matchStatus = ref<'active' | 'finished' | null>(null)
const matchData = ref<LiveMatchData | null>(null)
const lastUpdatedAt = ref<string | null>(null)
const viewEndIndex = ref(0)
let pollTimer: ReturnType<typeof setInterval> | null = null

const isLiveActive = computed(() => matchStatus.value === 'active')
const isFinished = computed(() => matchStatus.value === 'finished')
const pageTitle = computed(() => (isFinished.value ? t('live.view.recapTitle') : t('live.view.title')))

const substitutions = computed(() => matchData.value?.substitutions ?? [])
const showRoles = computed(() => matchData.value?.type === 'triplette' || matchData.value?.type === 'doublette')
const ballsPerPlayer = computed(() => (matchData.value?.type === 'triplette' ? 2 : 3))

const currentEnd = computed<EndRecord>(() => {
  const ends = matchData.value?.ends ?? []
  return ends[viewEndIndex.value] ?? { index: 1, balls: [] }
})

const isViewingCurrentEnd = computed(() => viewEndIndex.value === (matchData.value?.currentEndIndex ?? 0))

const displayDistance = computed<number | null>(() => {
  if (!matchData.value) return null
  if (isViewingCurrentEnd.value && matchData.value.distanceEstimate !== null) {
    return matchData.value.distanceEstimate
  }
  const endDistance = endDistanceEstimate(currentEnd.value)
  return endDistance ?? (isViewingCurrentEnd.value ? matchData.value.distanceEstimate : null)
})

const teamALabel = computed(() => matchData.value?.teamALabel || t('live.view.teamA'))
const teamBLabel = computed(() => matchData.value?.teamBLabel || t('live.view.teamB'))

const teamASlots = computed(() => {
  if (!matchData.value) return []
  return teamSlotsForEnd(matchData.value.teamA, 'A', substitutions.value, currentEnd.value.index)
})

const teamBSlots = computed(() => {
  if (!matchData.value) return []
  return teamSlotsForEnd(matchData.value.teamB, 'B', substitutions.value, currentEnd.value.index)
})

const recapTeamAPlayers = computed(() => recapPlayersForTeam('A'))
const recapTeamBPlayers = computed(() => recapPlayersForTeam('B'))

const scoreA = computed(
  () => (matchData.value?.openingScoreA ?? 0) + computeScore('A', matchData.value?.ends ?? []),
)
const scoreB = computed(
  () => (matchData.value?.openingScoreB ?? 0) + computeScore('B', matchData.value?.ends ?? []),
)

const scoredEnds = computed(() =>
  (matchData.value?.ends ?? []).filter((end) => end.canceled || end.points !== undefined),
)

const winnerSide = computed<'A' | 'B' | null>(() => {
  if (scoreA.value === scoreB.value) return null
  return scoreA.value > scoreB.value ? 'A' : 'B'
})

const winnerLabel = computed(() => {
  if (!winnerSide.value) return null
  const team = winnerSide.value === 'A' ? teamALabel.value : teamBLabel.value
  return t('live.view.winner', { team })
})

const lastUpdatedLabel = computed(() => {
  if (!lastUpdatedAt.value) return ''
  const date = new Date(lastUpdatedAt.value)
  if (Number.isNaN(date.getTime())) return ''
  return t('live.view.lastUpdate', {
    time: new Intl.DateTimeFormat(locale.value, { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(date),
  })
})

watch(
  () => matchData.value?.currentEndIndex,
  (index) => {
    if (typeof index === 'number' && isLiveActive.value) {
      viewEndIndex.value = index
    }
  },
)

function computeScore(team: 'A' | 'B', ends: EndRecord[]): number {
  let total = 0
  for (const end of ends) {
    if (end.canceled || end.points === undefined) continue
    if (end.winner === team) total += end.points
  }
  return total
}

function endDistanceEstimate(end: EndRecord): number | null {
  const values = end.balls
    .flatMap((ball) => ball.distances ?? [])
    .filter((distance): distance is number => distance !== null && distance !== undefined)
  if (values.length === 0) return null
  const last = values[values.length - 1]
  return last ?? null
}

function formatDistance(distance: number): string {
  if (Number.isInteger(distance)) return String(distance)
  return distance.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')
}

function endResultLabel(end: EndRecord): string {
  if (!end.winner || end.points === undefined) return '–'
  const winner = end.winner === 'A' ? teamALabel.value : teamBLabel.value
  return t('live.view.endResult', { winner, points: end.points })
}

function hasPlayedBallsInEnd(playerId: number, end: EndRecord): boolean {
  const entry = end.balls.find((ball) => ball.playerId === playerId)
  return (entry?.notes.length ?? 0) > 0
}

function nameFor(playerId: number): string {
  return matchData.value?.playerNames[playerId] ?? `#${playerId}`
}

function shortNameFor(playerId: number): string {
  return matchData.value?.shortPlayerNames[playerId] ?? nameFor(playerId)
}

function mastersLabel(playerId: number): string | null {
  if (!matchData.value) return null
  const score = playerMastersFromEnds(matchData.value.ends, playerId)
  return score ? `(${formatMasters(score)})` : null
}

function pointMastersLabel(playerId: number): string | null {
  if (!matchData.value) return null
  const score = playerShotMastersFromEnds(matchData.value.ends, playerId, 'point')
  return score ? formatMasters(score) : null
}

function tirMastersLabel(playerId: number): string | null {
  if (!matchData.value) return null
  const score = playerShotMastersFromEnds(matchData.value.ends, playerId, 'tir')
  return score ? formatMasters(score) : null
}

function cochonnetMastersLabel(playerId: number): string | null {
  if (!matchData.value) return null
  const score = playerCochonnetMastersFromEnds(matchData.value.ends, playerId)
  return score ? formatMasters(score) : null
}

function recapPlayersForTeam(team: 'A' | 'B'): number[] {
  if (!matchData.value) return []
  const base = team === 'A' ? matchData.value.teamA : matchData.value.teamB
  const ids = new Set(base)
  for (const sub of substitutions.value) {
    if (sub.team === team) {
      ids.add(sub.inPlayerId)
    }
  }
  return Array.from(ids)
    .filter((playerId) => hasPlayedInMatch(playerId))
    .sort((a, b) => shortNameFor(a).localeCompare(shortNameFor(b), undefined, { sensitivity: 'base' }))
}

function hasPlayedInMatch(playerId: number): boolean {
  if (!matchData.value) return false
  return matchData.value.ends.some((end) =>
    end.balls.some((ball) => ball.playerId === playerId && ball.notes.length > 0),
  )
}

function roleFor(playerId: number): PlayerRole {
  const endRoles = currentEnd.value.roles
  if (endRoles?.[playerId]) return endRoles[playerId]
  return matchData.value?.currentRoles[playerId] ?? matchData.value?.startingRoles[playerId] ?? 'pointeur'
}

function roleLabel(role: PlayerRole): string {
  return t(`matches.roles.${role}`)
}

function noteAt(playerId: number, idx: number): number | undefined {
  const entry = currentEnd.value.balls.find((ball) => ball.playerId === playerId)
  return entry?.notes[idx]
}

function distanceAt(playerId: number, idx: number): number | null | undefined {
  const entry = currentEnd.value.balls.find((ball) => ball.playerId === playerId)
  return entry?.distances?.[idx]
}

function distanceLabel(playerId: number, idx: number): string | null {
  const distance = distanceAt(playerId, idx)
  if (distance === null || distance === undefined) return null
  return `${formatDistance(distance)} m`
}

function formatNote(n: number): string {
  return n > 0 ? `+${n}` : String(n)
}

function ballLabel(playerId: number, idx: number): string {
  const note = noteAt(playerId, idx)
  if (note === undefined) return '⚪'
  return formatNote(note)
}

function ballStateClass(playerId: number, idx: number): Record<string, boolean> {
  const note = noteAt(playerId, idx)
  return {
    'ball--played': note !== undefined,
    'ball--empty': note === undefined,
  }
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

function stopPolling(): void {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

function startPolling(): void {
  stopPolling()
  pollTimer = setInterval(() => {
    void fetchLiveMatch()
  }, POLL_INTERVAL_MS)
}

async function fetchLiveMatch(): Promise<void> {
  try {
    const response = await liveMatchesService.getPublic(uuid)
    matchData.value = response.data
    matchStatus.value = response.status
    lastUpdatedAt.value = response.updatedAt
    notFound.value = false

    if (response.status === 'finished') {
      stopPolling()
    }
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) {
      notFound.value = true
      matchData.value = null
      matchStatus.value = null
      stopPolling()
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchLiveMatch()
  if (isLiveActive.value) {
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.live-view {
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
  padding: var(--app-space-sm) var(--app-space-sm) var(--app-space-lg);
  display: grid;
  gap: var(--app-space-sm);
  background:
    radial-gradient(circle at 0% 0%, rgba(31, 107, 88, 0.07), transparent 38%),
    radial-gradient(circle at 100% 0%, rgba(184, 146, 58, 0.06), transparent 34%),
    var(--app-bg);
}

.live-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--app-space-sm);
}

.live-header-main {
  min-width: 0;
  flex: 1;
}

.live-header h1 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.live-lang-switcher {
  flex-shrink: 0;
  opacity: 0.82;
  transform: scale(0.92);
  transform-origin: top right;
}

.live-lang-switcher :deep(.lang-switcher) {
  box-shadow: none;
  border-color: var(--app-border);
  background: rgba(255, 255, 255, 0.72);
}

.live-lang-switcher :deep(.lang-btn) {
  min-width: 2rem;
  min-height: 1.65rem;
  font-size: 0.6875rem;
}

.live-updated,
.live-finished-badge {
  margin: 0.25rem 0 0;
  font-size: 0.8125rem;
}

.live-updated {
  color: var(--app-text-muted);
}

.live-finished-badge {
  color: var(--app-primary);
  font-weight: 600;
}

.live-state {
  text-align: center;
  color: var(--app-text-muted);
  padding: 2rem 0;
}

.live-state--error {
  color: var(--app-text);
}

.recap-banner {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-md);
  background: var(--app-primary-soft);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-lg);
  text-align: center;
}

.recap-winner {
  margin: 0;
  font-weight: 700;
  color: var(--app-primary-dark);
}

.recap-score {
  display: grid;
  gap: 0.375rem;
}

.recap-team {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.recap-score-values {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 2rem;
  font-variant-numeric: tabular-nums;
}

.scoreboard {
  display: grid;
  grid-template-columns: 2.5rem 1fr 2.5rem;
  align-items: center;
  gap: var(--app-space-xs);
  padding: var(--app-space-sm);
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
  font-size: clamp(1.75rem, 8vw, 2.25rem);
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
  padding: 0.25rem 0 0.125rem;
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

.distance-estimate-value {
  font-size: 0.875rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--app-text);
}

.distance-estimate-unit {
  font-size: 0.75rem;
  font-weight: 600;
}

.end-canceled {
  margin: 0;
  text-align: center;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.teams {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.5rem;
  align-items: stretch;
}

.team {
  min-width: 0;
  padding: 0.5rem 0.5rem 0.375rem;
  border-radius: var(--app-radius);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  display: grid;
  align-content: start;
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
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.125rem;
  min-width: 0;
  font-weight: 700;
  font-size: 0.8125rem;
  letter-spacing: -0.01em;
  color: var(--app-text);
}

.player-name--right {
  text-align: right;
  align-items: flex-end;
}

.player-name-line {
  min-width: 0;
  max-width: 100%;
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.player-name--right .player-name-line {
  justify-content: flex-end;
}

.player-masters {
  flex-shrink: 0;
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--app-text-muted);
  font-variant-numeric: tabular-nums;
}

.player-name-main {
  display: block;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.player-role {
  font-size: 0.5rem;
  font-weight: 700;
  line-height: 1.1;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-primary);
  opacity: 0.8;
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

.player--sub .player-name-main {
  color: var(--app-primary-dark);
}

.balls {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 0.3rem;
}

.ball-wrap {
  display: grid;
  justify-items: center;
  gap: 0.125rem;
  min-width: 2.7rem;
}

.ball-distance {
  font-size: 0.5625rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--app-text-subtle);
  line-height: 1;
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
}

.balls--readonly :deep(.ball.p-button) {
  opacity: 0.92;
}

.balls :deep(.ball--empty.p-button) {
  border-style: dashed;
  border-color: var(--app-border);
  background: transparent;
  color: var(--app-text-subtle);
  font-size: 0.95rem;
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

.ends-recap {
  padding: var(--app-space-md);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-lg);
}

.ends-recap-title {
  margin: 0 0 var(--app-space-sm);
  font-size: 0.9375rem;
}

.ends-recap-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.5rem;
}

.ends-recap-item {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  font-size: 0.875rem;
}

.ends-recap-label {
  color: var(--app-text-muted);
}

.ends-recap-result {
  font-weight: 600;
}

.ends-recap-result--canceled {
  color: var(--app-text-muted);
  font-weight: 500;
}

.teams--recap {
  align-items: stretch;
}

.recap-player {
  display: grid;
  gap: 0.375rem;
  padding: 0.5rem 0;
  border-top: 1px solid var(--app-border);
}

.recap-player:first-of-type {
  border-top: none;
  padding-top: 0;
}

.recap-player--right {
  text-align: right;
}

.recap-player--right .recap-player-stats {
  justify-content: flex-end;
}

.recap-player-name {
  display: block;
  font-weight: 700;
  font-size: 0.875rem;
  letter-spacing: -0.01em;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.recap-player-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.recap-stat {
  display: inline-flex;
  align-items: baseline;
  gap: 0.25rem;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-variant-numeric: tabular-nums;
  background: var(--app-surface-muted);
  border: 1px solid var(--app-border);
}

.recap-stat-label {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-muted);
}

.recap-stat-value {
  font-weight: 800;
  color: var(--app-text);
}

.recap-stat--point .recap-stat-value {
  color: var(--play-team-a);
}

.recap-stat--tir .recap-stat-value {
  color: var(--play-team-b);
}
</style>
