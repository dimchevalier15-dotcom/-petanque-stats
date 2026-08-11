<template>
  <section class="shooting-play">
    <header class="scoreboard">
      <Button
        class="scoreboard-nav"
        icon="pi pi-chevron-left"
        text
        rounded
        @click="goPrev"
        :disabled="currentWorkshopIndex === 0"
        :aria-label="t('shooting.nav.prevWorkshop')"
      />
      <div class="scoreboard-main">
        <span class="workshop-pill">{{ t('shooting.progress', { current: currentWorkshopIndex + 1, total: 5 }) }}</span>
        <h2 class="workshop-title">{{ workshopLabel(currentWorkshop) }}</h2>
        <div class="score-row">
          <span class="score-label">{{ t('shooting.score.total') }}</span>
          <strong class="score-value">{{ totalScore }}<span class="score-max">/100</span></strong>
        </div>
      </div>
      <Button
        class="scoreboard-nav"
        icon="pi pi-chevron-right"
        text
        rounded
        @click="goNext"
        :disabled="isLastWorkshop || !currentWorkshopComplete"
        :aria-label="t('shooting.nav.nextWorkshop')"
      />
    </header>

    <p class="workshop-hint">{{ t('shooting.workshopHint') }}</p>

    <div class="distances">
      <article v-for="distance in distances" :key="distance" class="distance-card app-card">
        <div class="distance-head">
          <span class="distance-label">{{ t('shooting.distanceMeters', { n: distance }) }}</span>
          <Tag v-if="scoreAt(distance) !== undefined" :value="`+${scoreAt(distance)}`" :severity="severityFor(resultAt(distance))" />
        </div>
        <div class="results" :style="{ '--result-count': resultsFor(currentWorkshop).length }">
          <Button
            v-for="result in resultsFor(currentWorkshop)"
            :key="result"
            :label="resultLabel(result)"
            :severity="resultAt(distance) === result ? severityFor(result) : 'secondary'"
            :outlined="resultAt(distance) !== result"
            size="small"
            class="result-btn"
            @click="selectResult(distance, result)"
          />
        </div>
      </article>
    </div>

    <div class="workshop-score app-card">
      <span>{{ t('shooting.score.workshop') }}</span>
      <strong>{{ workshopScore(currentWorkshop) }}</strong>
    </div>

    <footer class="play-actions">
      <Button
        v-if="!isLastWorkshop"
        class="primary-action"
        :label="t('shooting.actions.nextWorkshop')"
        icon="pi pi-arrow-right"
        :disabled="!currentWorkshopComplete"
        @click="goNext"
      />
      <Button
        v-else
        class="primary-action"
        :label="t('shooting.actions.finishSession')"
        icon="pi pi-flag"
        :loading="finishing"
        :disabled="!isSessionComplete"
        @click="finishSession"
      />
      <Button
        class="abandon-action"
        :label="t('shooting.actions.abandon')"
        severity="secondary"
        text
        @click="abandonDialog = true"
      />
    </footer>

    <Dialog v-model:visible="abandonDialog" :modal="true" :header="t('shooting.abandon.title')" :closable="false" class="shooting-dialog">
      <div class="abandon-content">
        <p>{{ t('shooting.abandon.message') }}</p>
        <div class="actions">
          <Button :label="t('shooting.abandon.cancel')" severity="secondary" @click="abandonDialog = false" />
          <Button :label="t('shooting.abandon.confirm')" severity="danger" :loading="abandoning" @click="confirmAbandon" />
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
import { useShootingSessionPlay, clearShootingDraft } from '../composables/useShootingSessionPlay'
import { resultsForWorkshop, SHOOTING_DISTANCES, type ShootingDistance, type ShootingShotResult } from '../models/Shooting'
import { shootingSessionsService } from '../services/shootingSessions'

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
} = useShootingSessionPlay(sessionId)

const distances = SHOOTING_DISTANCES

function resultAt(distance: ShootingDistance): ShootingShotResult | undefined {
  return shotAt(currentWorkshop.value, distance)?.result
}

function scoreAt(distance: ShootingDistance): number | undefined {
  return scoreOf(shotAt(currentWorkshop.value, distance))
}

function selectResult(distance: ShootingDistance, result: ShootingShotResult): void {
  setResult(currentWorkshop.value, distance, result)
}

function resultsFor(workshop: number): ShootingShotResult[] {
  return resultsForWorkshop(workshop as 1 | 2 | 3 | 4 | 5)
}

function workshopLabel(workshop: number): string {
  const keys = ['ballAlone', 'ballBehindJack', 'betweenTwoBalls', 'jumpedBall', 'jack']
  return t(`shooting.workshops.${keys[workshop - 1]}`)
}

function resultLabel(result: ShootingShotResult): string {
  return t(`shooting.results.${result}`)
}

function severityFor(result: ShootingShotResult | undefined): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  switch (result) {
    case 'missed':
      return 'danger'
    case 'touched':
      return 'warn'
    case 'successful':
      return 'success'
    case 'carreau':
      return 'help'
    default:
      return 'secondary'
  }
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
    await shootingSessionsService.complete(sessionId, toCompletionPayload())
    clearShootingDraft(sessionId)
    router.push({ name: 'shootingSessionSummary', params: { id: sessionId } })
  } finally {
    finishing.value = false
  }
}

const abandonDialog = ref(false)
const abandoning = ref(false)
async function confirmAbandon(): Promise<void> {
  abandoning.value = true
  try {
    await shootingSessionsService.abandon(sessionId)
    clearShootingDraft(sessionId)
    router.push({ name: 'shootingHome' })
  } finally {
    abandoning.value = false
    abandonDialog.value = false
  }
}
</script>

<style scoped>
.shooting-play {
  max-width: var(--app-page-max);
  margin: 0 auto;
  min-height: 100dvh;
  display: grid;
  grid-template-rows: auto auto 1fr auto auto;
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

.distances {
  display: grid;
  gap: var(--app-space-sm);
  align-content: start;
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
  grid-template-columns: repeat(var(--result-count, 4), minmax(0, 1fr));
  gap: 0.375rem;
}

.result-btn {
  min-height: 2.5rem;
  font-size: 0.8125rem;
  font-weight: 700;
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
  position: sticky;
  bottom: calc(var(--app-nav-h) + env(safe-area-inset-bottom, 0px));
  z-index: 15;
  display: grid;
  gap: var(--app-space-xs);
  padding-top: var(--app-space-xs);
  background: linear-gradient(to top, var(--app-bg) 78%, transparent);
}

.primary-action {
  width: 100%;
  min-height: 3rem;
  font-weight: 700;
}

.abandon-action {
  width: 100%;
}

.abandon-content,
.resume-content {
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
