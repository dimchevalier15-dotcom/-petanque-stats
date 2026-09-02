<template>
  <section class="tactical-insights">
    <header class="tactical-header">
      <h2>{{ t('stats.tactical.title') }}</h2>
      <p class="tactical-subtitle">{{ t('stats.tactical.subtitle') }}</p>
    </header>

    <article class="tactical-block tactical-block--hero">
      <h3>{{ t('summary.insights.marking.title') }}</h3>
      <p class="tactical-hint">{{ t('summary.insights.marking.hint') }}</p>
      <div class="marking-overall">
        <div v-for="shot in shotTypes" :key="`marking-overall-${shot}`" class="marking-row">
          <div class="marking-row-head">
            <span>{{ t(`summary.insights.marking.${shot}`) }}</span>
            <strong>{{ rateLabel(insights.markingOverall?.[shot]) }}</strong>
          </div>
          <div class="marking-bar" aria-hidden="true">
            <div class="marking-bar-fill" :style="{ width: `${rateValue(insights.markingOverall?.[shot])}%` }" />
          </div>
        </div>
      </div>
      <div v-if="insights.markingByDistance.length > 0" class="distance-rows">
        <div
          v-for="row in insights.markingByDistance"
          :key="`marking-${row.bucket}`"
          class="distance-card"
        >
          <span class="distance-card-label">{{ bucketLabel(row.bucket) }}</span>
          <div class="marking-rows">
            <div v-for="shot in shotTypes" :key="`marking-${row.bucket}-${shot}`" class="marking-row">
              <div class="marking-row-head">
                <span>{{ t(`summary.insights.marking.${shot}`) }}</span>
                <strong>{{ rateLabel(row[shot]) }}</strong>
              </div>
              <div class="marking-bar" aria-hidden="true">
                <div class="marking-bar-fill" :style="{ width: `${rateValue(row[shot])}%` }" />
              </div>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="tactical-hint">{{ t('stats.tactical.noDistanceData') }}</p>
    </article>

    <article class="tactical-block">
      <h3>{{ t('summary.insights.rajout.title') }}</h3>
      <p class="tactical-hint">{{ t('summary.insights.rajout.hint') }}</p>
      <div class="marking-overall">
        <div v-for="shot in shotTypes" :key="`rajout-overall-${shot}`" class="marking-row">
          <div class="marking-row-head">
            <span>{{ t(`summary.insights.rajout.${shot}`) }}</span>
            <strong>{{ rateLabel(insights.rajoutOverall?.[shot]) }}</strong>
          </div>
          <div class="marking-bar" aria-hidden="true">
            <div
              class="marking-bar-fill marking-bar-fill--rajout"
              :style="{ width: `${rateValue(insights.rajoutOverall?.[shot])}%` }"
            />
          </div>
        </div>
      </div>
      <div v-if="insights.rajoutByDistance.length > 0" class="distance-rows">
        <div
          v-for="row in insights.rajoutByDistance"
          :key="`rajout-${row.bucket}`"
          class="distance-card"
        >
          <span class="distance-card-label">{{ bucketLabel(row.bucket) }}</span>
          <div class="marking-rows">
            <div v-for="shot in shotTypes" :key="`rajout-${row.bucket}-${shot}`" class="marking-row">
              <div class="marking-row-head">
                <span>{{ t(`summary.insights.rajout.${shot}`) }}</span>
                <strong>{{ rateLabel(row[shot]) }}</strong>
              </div>
              <div class="marking-bar" aria-hidden="true">
                <div
                  class="marking-bar-fill marking-bar-fill--rajout"
                  :style="{ width: `${rateValue(row[shot])}%` }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="tactical-hint">{{ t('stats.tactical.noDistanceData') }}</p>
    </article>

    <article class="tactical-block">
      <h3>{{ t('summary.insights.heldEndError.title') }}</h3>
      <p class="tactical-hint">{{ t('summary.insights.heldEndError.hint') }}</p>
      <div class="marking-overall">
        <div class="marking-row">
          <div class="marking-row-head">
            <span>{{ t('summary.insights.heldEndError.label') }}</span>
            <strong>{{ heldEndErrorLabel(insights.heldEndError) }}</strong>
          </div>
          <div class="marking-bar marking-bar--error" aria-hidden="true">
            <div
              class="marking-bar-fill marking-bar-fill--error"
              :style="{ width: `${heldEndErrorRate(insights.heldEndError)}%` }"
            />
          </div>
        </div>
      </div>
    </article>

    <p v-if="insights.coverage" class="tactical-footnote">
      {{
        t('stats.tactical.coverage', {
          matches: insights.coverage.matchesAnalyzed,
          eligible: insights.coverage.matchesEligible,
          ends: insights.coverage.endsAnalyzed,
          rate: Math.round(insights.coverage.distanceSampleRate * 100),
        })
      }}
    </p>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { MatchInsightsHeldEndError, MatchInsightsMarkingRate } from '../../models/MatchInsights'
import type { PlayerTacticalInsights } from '../../models/PlayerTacticalInsights'
import { distanceBucketLabel } from '../../composables/usePlayerStatsCharts'

defineProps<{
  insights: PlayerTacticalInsights
}>()

const { t } = useI18n()
const shotTypes = ['point', 'tir'] as const

function bucketLabel(bucket: string): string {
  return distanceBucketLabel(t, bucket as import('../../models/PlayerStats').DistanceBucketKey)
}

function rateValue(data?: MatchInsightsMarkingRate | null): number {
  return data?.rate ?? 0
}

function rateLabel(data?: MatchInsightsMarkingRate | null): string {
  if (!data || data.attempts === 0) {
    return t('summary.insights.marking.noData')
  }
  if (data.rate === null) {
    return `${data.made}/${data.attempts}`
  }
  return t('summary.insights.marking.rate', {
    rate: data.rate,
    made: data.made,
    total: data.attempts,
  })
}

function heldEndErrorRate(data?: MatchInsightsHeldEndError | null): number {
  return data?.rate ?? 0
}

function heldEndErrorLabel(data?: MatchInsightsHeldEndError | null): string {
  if (!data || data.ballsPlayed === 0) {
    return t('summary.insights.heldEndError.noData')
  }
  if (data.rate === null) {
    return `${data.minusTwoCount}/${data.ballsPlayed}`
  }
  return t('summary.insights.heldEndError.rate', {
    rate: data.rate,
    errors: data.minusTwoCount,
    balls: data.ballsPlayed,
  })
}
</script>

<style scoped>
.tactical-insights {
  display: grid;
  gap: var(--app-space-md);
}

.tactical-header {
  display: grid;
  gap: 0.25rem;
}

.tactical-header h2 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
}

.tactical-subtitle {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.tactical-block {
  padding: var(--app-space-md);
  border-radius: var(--app-radius-md, 0.75rem);
  border: 1px solid #e2e8f0;
  background: #fff;
  display: grid;
  gap: var(--app-space-sm);
}

.tactical-block--hero {
  border-color: #cbd5e1;
  background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}

.tactical-block h3 {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
}

.tactical-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  line-height: 1.4;
}

.marking-overall,
.marking-rows {
  display: grid;
  gap: 0.625rem;
}

.distance-rows {
  display: grid;
  gap: var(--app-space-sm);
}

.distance-card {
  padding: var(--app-space-sm);
  border-radius: 0.625rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  display: grid;
  gap: 0.5rem;
}

.distance-card-label {
  font-size: 0.8125rem;
  font-weight: 700;
}

.marking-row-head {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.8125rem;
}

.marking-row-head strong {
  font-weight: 700;
  text-align: right;
}

.marking-bar {
  margin-top: 0.25rem;
  height: 0.375rem;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

.marking-bar-fill {
  height: 100%;
  border-radius: inherit;
  background: var(--app-primary, #2563eb);
  min-width: 0;
  transition: width 0.3s ease;
}

.marking-bar-fill--rajout {
  opacity: 0.75;
}

.marking-bar--error {
  height: 0.5rem;
}

.marking-bar-fill.marking-bar-fill--error {
  background: #dc2626;
  opacity: 1;
}

.tactical-footnote {
  margin: 0;
  font-size: 0.75rem;
  color: var(--app-text-muted);
  text-align: center;
}
</style>
