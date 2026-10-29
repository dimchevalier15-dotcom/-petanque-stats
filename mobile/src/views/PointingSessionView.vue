<template>
  <section class="pointing-play">
    <header class="scoreboard">
      <Button
        class="scoreboard-nav"
        icon="pi pi-chevron-left"
        text
        rounded
        @click="goPrev"
        :disabled="currentWorkshopIndex === 0"
        :aria-label="t('pointing.nav.prevWorkshop')"
      />
      <div class="scoreboard-main">
        <span class="workshop-pill">{{ t('pointing.progress', { current: currentWorkshopIndex + 1, total: 5 }) }}</span>
        <h2 class="workshop-title">{{ workshopLabel(currentWorkshop) }}</h2>
        <div class="score-row">
          <span class="score-label">{{ t('pointing.score.total') }}</span>
          <strong class="score-value">{{ totalScore }}<span class="score-max">/300</span></strong>
        </div>
      </div>
      <Button
        class="scoreboard-nav"
        icon="pi pi-chevron-right"
        text
        rounded
        @click="goNext"
        :disabled="isLastWorkshop || !currentWorkshopComplete"
        :aria-label="t('pointing.nav.nextWorkshop')"
      />
    </header>

    <p class="workshop-hint">{{ t('pointing.workshopHint') }}</p>

    <div class="play-main">
      <div class="distances">
        <article v-for="distance in distances" :key="distance" class="distance-card app-card">
          <div class="distance-head">
            <span class="distance-label">{{ t('pointing.distanceMeters', { n: distance }) }}</span>
            <Tag v-if="scoreAt(distance) !== undefined" :value="`+${scoreAt(distance)}`" :severity="severityFor(scoreAt(distance))" />
          </div>
          <div class="results" :style="{ '--result-count': resultsFor(currentWorkshop).length }">
            <Button
              v-for="result in resultsFor(currentWorkshop)"
              :key="result"
              :label="resultLabel(currentWorkshop, result)"
              :severity="resultAt(distance) === result ? severityFor(pointsFor(currentWorkshop, result)) : 'secondary'"
              :outlined="resultAt(distance) !== result"
              size="small"
              class="result-btn"
              @click="selectResult(distance, result)"
            />
          </div>
        </article>
      </div>

      <div class="workshop-score app-card">
        <span>{{ t('pointing.score.workshop') }}</span>
        <strong>{{ workshopScore(currentWorkshop) }}</strong>
      </div>
    </div>

    <footer class="play-actions">
      <Button
        v-if="!isLastWorkshop"
        class="primary-action"
        :label="t('pointing.actions.nextWorkshop')"
        icon="pi pi-arrow-right"
        :disabled="!currentWorkshopComplete"
        @click="goNext"
      />
      <Button
        v-else
        class="primary-action"
        :label="t('pointing.actions.finishSession')"
        icon="pi pi-flag"
        :loading="finishing"
        :disabled="!isSessionComplete"
        @click="finishSession"
      />
      <Button
        class="abandon-action"
        :label="t('pointing.actions.abandon')"
        severity="secondary"
        text
        @click="abandonDialog = true"
      />
    </footer>

    <Dialog v-model:visible="abandonDialog" :modal="true" :header="t('pointing.abandon.title')" :closable="false" class="pointing-dialog">
      <div class="abandon-content">
        <p>{{ t('pointing.abandon.message') }}</p>
        <div class="actions">
          <Button :label="t('pointing.abandon.cancel')" severity="secondary" @click="abandonDialog = false" />
          <Button :label="t('pointing.abandon.confirm')" severity="danger" :loading="abandoning" @click="confirmAbandon" />
        </div>
      </div>
    </Dialog>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { usePointingSessionPlay, clearPointingDraft } from '../composables/usePointingSessionPlay'
import {
  POINTING_DISTANCES,
  pointingResultI18nKey,
  pointingShotScoreSeverity,
  pointingWorkshopI18nKey,
  pointsFor,
  resultsForWorkshop,
  type PointingDistance,
  type PointingShotResult,
  type PointingWorkshop,
} from '../models/Pointing'
import { pointingSessionsService } from '../services/pointingSessions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)

const {
  currentWorkshopIndex,
  currentWorkshop,
  currentWorkshopComplete,
  isLastWorkshop,
  isSessionComplete,
  totalScore,
  shotAt,
  scoreOf,
  setResult,
  workshopScore,
  goPrevWorkshop,
  goNextWorkshop,
  toCompletionPayload,
} = usePointingSessionPlay(sessionId)

const distances = POINTING_DISTANCES

function resultAt(distance: PointingDistance): PointingShotResult | undefined {
  return shotAt(currentWorkshop.value, distance)?.result
}

function scoreAt(distance: PointingDistance): number | undefined {
  return scoreOf(shotAt(currentWorkshop.value, distance))
}

function selectResult(distance: PointingDistance, result: PointingShotResult): void {
  setResult(currentWorkshop.value, distance, result)
}

function resultsFor(workshop: PointingWorkshop): PointingShotResult[] {
  return resultsForWorkshop(workshop)
}

function workshopLabel(workshop: PointingWorkshop): string {
  return t(`pointing.workshops.${pointingWorkshopI18nKey(workshop)}`)
}

function resultLabel(workshop: PointingWorkshop, result: PointingShotResult): string {
  return t(pointingResultI18nKey(workshop, result))
}

function severityFor(score: number | undefined): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  if (score === undefined) return 'secondary'
  return pointingShotScoreSeverity(score)
}

function goPrev(): void {
  goPrevWorkshop()
}
function goNext(): void {
  goNextWorkshop()
}

const finishing = ref(false)
async function finishSession(): Promise<void> {
  finishing.value = true
  try {
    await pointingSessionsService.complete(sessionId, toCompletionPayload())
    clearPointingDraft(sessionId)
    router.push({ name: 'pointingSessionSummary', params: { id: sessionId } })
  } finally {
    finishing.value = false
  }
}

const abandonDialog = ref(false)
const abandoning = ref(false)
async function confirmAbandon(): Promise<void> {
  abandoning.value = true
  try {
    await pointingSessionsService.abandon(sessionId)
    clearPointingDraft(sessionId)
    router.push({ name: 'trainingPointPrecision' })
  } finally {
    abandoning.value = false
    abandonDialog.value = false
  }
}
</script>

<style scoped>
.pointing-play {
  display: grid;
  grid-template-rows: auto auto minmax(0, 1fr) auto;
  gap: var(--app-space-sm);
  min-height: calc(
    100dvh - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px) - var(--app-nav-h) -
      var(--app-space-md) - var(--app-space-lg)
  );
  min-width: 0;
  overflow: hidden;
  margin: calc(-1 * var(--app-space-md)) calc(-1 * var(--app-space-lg)) calc(-1 * var(--app-space-lg));
  padding: var(--app-space-sm) var(--app-space-lg) 0;
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
  gap: 0.25rem;
  justify-items: center;
  text-align: center;
}

.workshop-pill {
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

.workshop-title {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.score-row {
  display: flex;
  align-items: baseline;
  gap: 0.375rem;
}

.score-label {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--app-text-subtle);
}

.score-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--app-primary-dark);
  font-variant-numeric: tabular-nums;
}

.score-max {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--app-text-subtle);
}

.workshop-hint {
  margin: 0;
  text-align: center;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.play-main {
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  display: grid;
  gap: var(--app-space-md);
  align-content: start;
  padding-bottom: var(--app-space-xs);
}

.distances {
  display: grid;
  gap: var(--app-space-sm);
}

.distance-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.distance-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.distance-label {
  font-weight: 800;
  font-size: 1rem;
  letter-spacing: -0.01em;
}

.results {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.375rem;
}

@media (min-width: 380px) {
  .results {
    grid-template-columns: repeat(var(--result-count, 4), minmax(0, 1fr));
  }
}

.result-btn {
  min-height: 2.75rem;
  font-size: 0.625rem;
  font-weight: 700;
  line-height: 1.15;
  white-space: normal;
  padding-inline: 0.25rem;
}

.workshop-score {
  padding: var(--app-space-md);
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 700;
}

.workshop-score strong {
  font-size: 1.25rem;
  color: var(--app-primary-dark);
}

.play-actions {
  z-index: 15;
  display: grid;
  gap: var(--app-space-xs);
  padding: var(--app-space-xs) 0 var(--app-space-sm);
  background: linear-gradient(to top, var(--app-bg) 85%, transparent);
}

.primary-action {
  width: 100%;
  min-height: 3rem;
  font-weight: 700;
}

.abandon-action {
  width: 100%;
}

.abandon-content {
  display: grid;
  gap: var(--app-space-md);
}

.abandon-content p {
  margin: 0;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.abandon-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}
</style>
