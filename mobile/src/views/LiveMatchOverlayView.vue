<template>
  <div v-if="!loading && !notFound && matchData" class="live-overlay">
    <div class="live-overlay-stack">
      <div class="live-overlay-bar">
        <div v-if="!isFinished" class="live-overlay-meta">
          <span class="live-overlay-end">{{ t('live.overlay.end', { n: currentEnd.index }) }}</span>
          <span v-if="timerVisible" class="live-overlay-timer">
            {{ timerDisplay }}
          </span>
          <span v-if="displayDistance !== null" class="live-overlay-distance">
            {{ formatDistance(displayDistance) }} m
          </span>
        </div>

      <div class="live-overlay-main">
        <div class="live-overlay-side live-overlay-side--a">
          <span class="live-overlay-team" :title="displayTeamALabel">{{ displayTeamALabel }}</span>
          <div v-if="!isFinished" class="live-overlay-balls" aria-hidden="true">
            <span
              v-for="(inHand, index) in teamABallStates"
              :key="`ball-a-${index}`"
              class="live-overlay-ball"
              :class="inHand ? 'live-overlay-ball--in-hand' : 'live-overlay-ball--played'"
            />
          </div>
        </div>

        <div class="live-overlay-score">
          <span class="live-overlay-score-value live-overlay-score-value--a">{{ scoreA }}</span>
          <span class="live-overlay-score-sep">–</span>
          <span class="live-overlay-score-value live-overlay-score-value--b">{{ scoreB }}</span>
        </div>

        <div class="live-overlay-side live-overlay-side--b">
          <div v-if="!isFinished" class="live-overlay-balls" aria-hidden="true">
            <span
              v-for="(inHand, index) in teamBBallStates"
              :key="`ball-b-${index}`"
              class="live-overlay-ball"
              :class="inHand ? 'live-overlay-ball--in-hand' : 'live-overlay-ball--played'"
            />
          </div>
          <span class="live-overlay-team" :title="displayTeamBLabel">{{ displayTeamBLabel }}</span>
        </div>
      </div>

      <div v-if="isFinished" class="live-overlay-finished">
        <span v-if="winnerLabel" class="live-overlay-winner">{{ winnerLabel }}</span>
        <span class="live-overlay-badge">{{ t('live.overlay.finished') }}</span>
      </div>
      </div>

      <a
        :href="liveMatchUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="live-overlay-promo"
      >
        {{ t('live.overlay.statsPromo') }}
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import type { EndRecord } from '../models/MatchPlay'
import { useMatchTimerFromSnapshot } from '../composables/useMatchTimer'
import { useLiveMatchPolling } from '../composables/useLiveMatchPolling'
import { buildLiveMatchUrl } from '../services/liveMatchStorage'

const { t } = useI18n()
const route = useRoute()
const uuid = String(route.params.uuid)
const liveMatchUrl = buildLiveMatchUrl(uuid)

const {
  loading,
  notFound,
  matchData,
  isFinished,
  finishedAt,
  timerAccumulatedMs,
  timerRunning,
  timerRunningSince,
  currentEnd,
  teamALabel,
  teamBLabel,
  scoreA,
  scoreB,
  teamABallStates,
  teamBBallStates,
  winnerSide,
} = useLiveMatchPolling(uuid)

const { visible: timerVisible, display: timerDisplay } = useMatchTimerFromSnapshot(
  timerAccumulatedMs,
  timerRunning,
  timerRunningSince,
  computed(() => (isFinished.value ? finishedAt.value : null)),
)

const displayTeamALabel = computed(() => teamALabel.value || t('live.view.teamA'))
const displayTeamBLabel = computed(() => teamBLabel.value || t('live.view.teamB'))

const winnerLabel = computed(() => {
  if (!winnerSide.value) return null
  const team = winnerSide.value === 'A' ? displayTeamALabel.value : displayTeamBLabel.value
  return t('live.overlay.winner', { team })
})

const displayDistance = computed<number | null>(() => {
  if (!matchData.value || isFinished.value) return null
  if (matchData.value.distanceEstimate !== null) {
    return matchData.value.distanceEstimate
  }
  return endDistanceEstimate(currentEnd.value)
})

function endDistanceEstimate(end: EndRecord): number | null {
  const values = end.shots
    .map((shot) => shot.distance)
    .filter((distance): distance is number => distance !== null && distance !== undefined)
  if (values.length === 0) return null
  return values[values.length - 1] ?? null
}

function formatDistance(distance: number): string {
  if (Number.isInteger(distance)) return String(distance)
  return distance.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')
}
</script>

<style scoped>
.live-overlay {
  --play-team-a: #15803d;
  --play-team-b: #1d4ed8;
  --overlay-bg: rgba(28, 36, 48, 0.88);
  --overlay-border: rgba(255, 255, 255, 0.1);
  --overlay-text: rgba(255, 255, 255, 0.92);
  --overlay-muted: rgba(255, 255, 255, 0.55);

  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  pointer-events: none;
}

.live-overlay-stack {
  width: fit-content;
  max-width: 100%;
  display: grid;
}

.live-overlay-bar {
  width: fit-content;
  padding-right: 32px;
  padding-left: 32px;
  border-radius: 0 0 10px 10px;
  background: var(--overlay-bg);
  border: 1px solid var(--overlay-border);
  border-top: none;
  backdrop-filter: blur(6px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
  color: var(--overlay-text);
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}

.live-overlay-promo {
  pointer-events: auto;
  display: block;
  width: fit-content;
  max-width: 100%;
  margin: 0 auto;
  padding: 2px;
  border-radius: 0 0 8px 8px;
  background: rgba(28, 36, 48, 0.72);
  border: 1px solid var(--overlay-border);
  border-top: none;
  backdrop-filter: blur(6px);
  font-size: 0.5625rem;
  font-weight: 600;
  line-height: 1.3;
  letter-spacing: 0.02em;
  text-align: center;
  text-decoration: none;
  color: rgba(255, 255, 255, 0.72);
  transition: color 0.15s ease, background 0.15s ease;
}

.live-overlay-promo:hover {
  color: #a7d4c8;
  background: rgba(28, 36, 48, 0.82);
}

.live-overlay-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0;
  width: fit-content;
  margin: 0 auto 0.3rem;
  font-size: 0.625rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--overlay-muted);
}

.live-overlay-meta > span + span::before {
  content: '·';
  margin: 0 0.35rem;
  opacity: 0.5;
}

.live-overlay-timer {
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.06em;
  text-transform: none;
  color: var(--overlay-text);
}

.live-overlay-distance {
  font-variant-numeric: tabular-nums;
}

.live-overlay-main {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: fit-content;
}

.live-overlay-side {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex: 0 0 auto;
}

.live-overlay-side--a {
  justify-content: flex-end;
}

.live-overlay-side--b {
  justify-content: flex-start;
}

.live-overlay-team {
  font-size: 0.6875rem;
  font-weight: 700;
  line-height: 1.2;
  white-space: nowrap;
  color: var(--overlay-text);
}

.live-overlay-side--a .live-overlay-team {
  text-align: right;
}

.live-overlay-side--b .live-overlay-team {
  text-align: left;
}

.live-overlay-balls {
  display: flex;
  align-items: center;
  gap: 3px;
  flex-shrink: 0;
}

.live-overlay-ball {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.live-overlay-ball--in-hand {
  background: #fff;
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.25);
}

.live-overlay-ball--played {
  background: transparent;
  border: 1.5px solid rgba(255, 255, 255, 0.35);
}

.live-overlay-score {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
  flex-shrink: 0;
  padding: 0 0.15rem;
}

.live-overlay-score-value {
  font-size: 1.375rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.03em;
  font-variant-numeric: tabular-nums;
}

.live-overlay-score-value--a {
  color: #86efac;
}

.live-overlay-score-value--b {
  color: #93c5fd;
}

.live-overlay-score-sep {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--overlay-muted);
}

.live-overlay-finished {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  margin-top: 0.25rem;
  font-size: 0.625rem;
}

.live-overlay-winner {
  font-weight: 700;
  color: #d4cbb8;
}

.live-overlay-badge {
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  background: rgba(31, 107, 88, 0.35);
  color: #a7d4c8;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
</style>
