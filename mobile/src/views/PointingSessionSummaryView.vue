<template>
  <AppPage>
    <PageHeader :title="t('pointing.summary.title')" :back-to="{ name: 'trainingPointPrecision' }" />

    <section v-if="summary" class="summary">
      <div class="hero-banner app-card">
        <span class="hero-badge">{{ t('pointing.summary.sessionFinished') }}</span>
        <strong class="hero-score">{{ summary.totalScore }}<span class="hero-max">/300</span></strong>
        <span class="hero-date">{{ formatDate(summary.playedAt) }}</span>
      </div>

      <section v-if="summary.contextNature || summary.title || summary.description" class="panel app-card context-panel">
        <Tag
          v-if="summary.contextNature"
          :value="t(`pointing.context.nature.${summary.contextNature}`)"
          severity="info"
        />
        <h3 v-if="summary.title">{{ summary.title }}</h3>
        <p v-if="summary.description" class="context-description">{{ summary.description }}</p>
      </section>

      <section class="panel app-card heatmap-panel">
        <h3>{{ t('pointing.summary.performanceMap.title') }}</h3>
        <p class="panel-hint">{{ t('pointing.summary.performanceMap.hint') }}</p>
        <div class="heatmap">
          <div class="heatmap-corner" />
          <div v-for="distance in distances" :key="distance" class="heatmap-col-label">
            {{ t('pointing.distanceMeters', { n: distance }) }}
          </div>
          <template v-for="workshop in workshops" :key="workshop">
            <div class="heatmap-row-label">
              <span class="row-name">{{ workshopLabel(workshop) }}</span>
              <span class="row-total">+{{ workshopTotal(workshop) }}</span>
            </div>
            <div
              v-for="distance in distances"
              :key="`${workshop}-${distance}`"
              class="heatmap-cell"
              :style="{ backgroundColor: pointingHeatmapCellColor(shotAt(workshop, distance)?.score ?? 0) }"
            >
              <span class="cell-score">+{{ shotAt(workshop, distance)?.score ?? 0 }}</span>
              <span class="cell-result">{{ resultLabel(workshop, shotAt(workshop, distance)?.result) }}</span>
            </div>
          </template>
        </div>
      </section>

      <div class="app-actions">
        <Button
          class="w-full"
          severity="secondary"
          outlined
          :label="contextActionLabel"
          @click="openContext"
        />
        <Button class="w-full" :label="t('pointing.summary.actions.backHome')" @click="goHome" />
      </div>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { useDateFormat } from '../composables/useDateFormat'
import { pointingSessionsService } from '../services/pointingSessions'
import {
  POINTING_DISTANCES,
  POINTING_WORKSHOPS,
  pointingHeatmapCellColor,
  pointingResultI18nKey,
  pointingWorkshopI18nKey,
  type PointingSessionSummary,
  type PointingShotResult,
  type PointingShotSummary,
  type PointingWorkshop,
} from '../models/Pointing'

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)
const summary = ref<PointingSessionSummary | null>(null)

const contextActionLabel = computed(() =>
  summary.value && (summary.value.contextNature || summary.value.title || summary.value.description)
    ? t('pointing.context.actions.edit')
    : t('pointing.context.actions.add'),
)

const workshops = POINTING_WORKSHOPS
const distances = POINTING_DISTANCES

function workshopLabel(workshop: PointingWorkshop): string {
  return t(`pointing.workshops.${pointingWorkshopI18nKey(workshop)}`)
}

function shotAt(workshop: PointingWorkshop, distance: number): PointingShotSummary | undefined {
  if (!summary.value) return undefined
  const workshopData = summary.value.workshops.find((w) => w.workshop === workshop)
  return workshopData?.shots.find((s) => s.distance === distance)
}

function workshopTotal(workshop: PointingWorkshop): number {
  if (!summary.value) return 0
  const workshopData = summary.value.workshops.find((w) => w.workshop === workshop)
  return workshopData?.totalScore ?? 0
}

function resultLabel(workshop: PointingWorkshop, result: PointingShotResult | undefined): string {
  if (!result) return '—'
  return t(pointingResultI18nKey(workshop, result))
}

function formatDate(iso: string | null): string {
  return iso ? formatShortDate(iso) : ''
}

function goHome(): void {
  router.push({ name: 'trainingPointPrecision' })
}

function openContext(): void {
  router.push({ name: 'pointingSessionContext', params: { id: sessionId } })
}

onMounted(async () => {
  if (!sessionId) {
    router.replace({ name: 'trainingPointPrecision' })
    return
  }
  try {
    summary.value = await pointingSessionsService.getSummary(sessionId)
  } catch {
    router.replace({ name: 'trainingPointPrecision' })
  }
})
</script>

<style scoped>
.summary {
  display: grid;
  gap: var(--app-space-md);
}

.hero-banner {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-xs);
  justify-items: center;
  text-align: center;
  border-color: #fde68a;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.hero-badge {
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-muted);
}

.hero-score {
  font-size: 2.5rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--app-primary-dark);
}

.hero-max {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--app-text-subtle);
}

.hero-date {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.panel h3 {
  margin: 0;
  font-size: 0.9375rem;
}

.panel-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.heatmap {
  display: grid;
  grid-template-columns: minmax(4.5rem, 1.2fr) repeat(4, 1fr);
  gap: 0.375rem;
  align-items: stretch;
}

.heatmap-corner {
  min-height: 1.5rem;
}

.heatmap-col-label,
.heatmap-row-label {
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--app-text-muted);
  display: flex;
  align-items: center;
}

.heatmap-col-label {
  justify-content: center;
  text-align: center;
}

.heatmap-row-label {
  flex-direction: column;
  align-items: flex-start;
  gap: 0.125rem;
  line-height: 1.2;
}

.row-name {
  font-size: 0.6875rem;
}

.row-total {
  font-size: 0.75rem;
  font-weight: 800;
  color: var(--app-primary-dark);
}

.heatmap-cell {
  border-radius: 8px;
  min-height: 3.25rem;
  display: grid;
  place-items: center;
  gap: 0.125rem;
  color: #fff;
  text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
  padding: 0.25rem;
}

.cell-score {
  font-size: 0.9375rem;
  font-weight: 800;
}

.cell-result {
  font-size: 0.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  opacity: 0.95;
  text-align: center;
  line-height: 1.1;
  max-width: 100%;
}

.context-panel {
  gap: 0.375rem;
}

.context-panel h3 {
  margin: 0;
  font-size: 0.9375rem;
}

.context-description {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
  line-height: 1.45;
  white-space: pre-wrap;
}

.w-full {
  width: 100%;
}
</style>
