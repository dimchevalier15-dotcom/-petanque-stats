<template>
  <section class="tactical-insights">
    <header class="tactical-header">
      <h2>{{ t('summary.insights.title') }}</h2>
      <p class="tactical-subtitle">{{ t('summary.insights.subtitle') }}</p>
    </header>

    <!-- Marking balls — primary metric -->
    <article class="tactical-block tactical-block--hero">
      <h3>{{ t('summary.insights.marking.title') }}</h3>
      <p class="tactical-hint">{{ t('summary.insights.marking.hint') }}</p>
      <div class="marking-grid">
        <div v-for="team in teams" :key="team" class="marking-team" :class="`marking-team--${team.toLowerCase()}`">
          <span class="marking-team-label">{{ labelForTeam(team) }}</span>
          <div class="marking-rows">
            <div v-for="shot in shotTypes" :key="`${team}-${shot}`" class="marking-row">
              <div class="marking-row-head">
                <span>{{ t(`summary.insights.marking.${shot}`) }}</span>
                <strong>{{ markingLabel(team, shot) }}</strong>
              </div>
              <div class="marking-bar" aria-hidden="true">
                <div
                  class="marking-bar-fill"
                  :style="{ width: `${markingRate(team, shot)}%` }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </article>

    <!-- Rajout balls -->
    <article class="tactical-block">
      <h3>{{ t('summary.insights.rajout.title') }}</h3>
      <p class="tactical-hint">{{ t('summary.insights.rajout.hint') }}</p>
      <div class="marking-grid">
        <div v-for="team in teams" :key="`rajout-${team}`" class="marking-team" :class="`marking-team--${team.toLowerCase()}`">
          <span class="marking-team-label">{{ labelForTeam(team) }}</span>
          <div class="marking-rows">
            <div v-for="shot in shotTypes" :key="`${team}-rajout-${shot}`" class="marking-row">
              <div class="marking-row-head">
                <span>{{ t(`summary.insights.rajout.${shot}`) }}</span>
                <strong>{{ rajoutLabel(team, shot) }}</strong>
              </div>
              <div class="marking-bar" aria-hidden="true">
                <div
                  class="marking-bar-fill marking-bar-fill--rajout"
                  :style="{ width: `${rajoutRate(team, shot)}%` }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </article>

    <!-- Ends won when team opened (played first) -->
    <article class="tactical-block">
      <h3>{{ t('summary.insights.point.title') }}</h3>
      <p class="tactical-hint">{{ t('summary.insights.point.hint') }}</p>
      <div class="compare-grid">
        <div
          v-for="team in teams"
          :key="`point-${team}`"
          class="compare-card"
          :class="`compare-card--${team.toLowerCase()}`"
        >
          <span class="compare-label">{{ labelForTeam(team) }}</span>
          <strong class="compare-value">{{ pointDominanceText(team) }}</strong>
        </div>
      </div>
    </article>

    <!-- Distance dominance -->
    <article v-if="hasDistanceSection" class="tactical-block">
      <h3>{{ t('summary.insights.distance.title') }}</h3>
      <p v-if="insights.distanceOutlook?.singleDominantTeam" class="distance-single">
        {{
          t('summary.insights.distance.monopoly', {
            team: labelForTeam(insights.distanceOutlook.singleDominantTeam),
          })
        }}
      </p>
      <ul v-else-if="competitiveBuckets.length > 0" class="distance-list">
        <li v-for="row in competitiveBuckets" :key="row.bucket">
          <span class="distance-bucket">{{ bucketLabel(row.bucket) }}</span>
          <span class="distance-winner">{{ labelForTeam(row.dominantTeam!) }}</span>
        </li>
      </ul>
      <p v-else class="tactical-hint">{{ t('summary.insights.distance.none') }}</p>
    </article>

    <p v-if="insights.coverage" class="tactical-footnote">
      {{
        t('summary.insights.coverage', {
          rate: Math.round(insights.coverage.distanceSampleRate * 100),
          ends: insights.coverage.endsAnalyzed,
        })
      }}
    </p>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { MatchContext } from '../../models/MatchContext'
import type { MatchInsights } from '../../models/MatchInsights'
import type { TeamSide } from '../../models/MatchPlay'
import { useMatchTeamLabels } from '../../composables/useMatchTeamLabels'

const props = defineProps<{
  insights: MatchInsights
  context?: MatchContext | null
}>()

const { t } = useI18n()
const { labelForTeam } = useMatchTeamLabels(computed(() => props.context ?? null), t)

const teams: TeamSide[] = ['A', 'B']
const shotTypes = ['point', 'tir'] as const

function markingTeam(team: TeamSide) {
  return team === 'A' ? props.insights.markingTeamA : props.insights.markingTeamB
}

const competitiveBuckets = computed(
  () => props.insights.distanceOutlook?.competitiveBuckets ?? [],
)

const hasDistanceSection = computed(() => {
  const outlook = props.insights.distanceOutlook
  if (!outlook) return false
  return outlook.singleDominantTeam !== null || outlook.competitiveBuckets.length > 0
})

function rajoutTeam(team: TeamSide) {
  return team === 'A' ? props.insights.rajoutTeamA : props.insights.rajoutTeamB
}

function pointDominance(team: TeamSide) {
  return team === 'A' ? props.insights.pointDominanceTeamA : props.insights.pointDominanceTeamB
}

function markingRate(team: TeamSide, shot: 'point' | 'tir'): number {
  const data = markingTeam(team)?.[shot]
  return data?.rate ?? 0
}

function markingLabel(team: TeamSide, shot: 'point' | 'tir'): string {
  const data = markingTeam(team)?.[shot]
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

function rajoutRate(team: TeamSide, shot: 'point' | 'tir'): number {
  const data = rajoutTeam(team)?.[shot]
  return data?.rate ?? 0
}

function rajoutLabel(team: TeamSide, shot: 'point' | 'tir'): string {
  const data = rajoutTeam(team)?.[shot]
  if (!data || data.attempts === 0) {
    return t('summary.insights.rajout.noData')
  }
  if (data.rate === null) {
    return `${data.made}/${data.attempts}`
  }
  return t('summary.insights.rajout.rate', {
    rate: data.rate,
    made: data.made,
    total: data.attempts,
  })
}

function pointDominanceText(team: TeamSide): string {
  const data = pointDominance(team)
  if (!data || data.endsOpened === 0) {
    return '—'
  }
  return t('summary.insights.point.ends', {
    won: data.endsWonWhenOpened,
    opened: data.endsOpened,
  })
}

function bucketLabel(bucket: string): string {
  return t(`stats.byDistance.buckets.${bucket}`)
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

.marking-grid {
  display: grid;
  gap: var(--app-space-sm);
}

@media (min-width: 520px) {
  .marking-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.marking-team {
  padding: var(--app-space-sm);
  border-radius: 0.625rem;
  display: grid;
  gap: 0.5rem;
}

.marking-team--a {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.marking-team--b {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.marking-team-label {
  font-size: 0.8125rem;
  font-weight: 700;
}

.marking-rows {
  display: grid;
  gap: 0.625rem;
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

.marking-team--a .marking-bar-fill {
  background: #16a34a;
}

.marking-team--b .marking-bar-fill {
  background: #2563eb;
}

.marking-bar-fill--rajout {
  opacity: 0.75;
}

.compare-grid {
  display: grid;
  gap: var(--app-space-sm);
}

@media (min-width: 520px) {
  .compare-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.compare-card {
  padding: var(--app-space-sm) var(--app-space-md);
  border-radius: 0.625rem;
  display: grid;
  gap: 0.25rem;
}

.compare-card--a {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.compare-card--b {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.compare-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  opacity: 0.85;
}

.compare-value {
  font-size: 1rem;
  font-weight: 700;
}

.distance-single {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
}

.distance-list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.375rem;
}

.distance-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-sm);
  padding: 0.5rem 0.625rem;
  border-radius: 0.5rem;
  background: #f8fafc;
  font-size: 0.8125rem;
}

.distance-bucket {
  color: var(--app-text-muted);
}

.distance-winner {
  font-weight: 700;
}

.tactical-footnote {
  margin: 0;
  font-size: 0.75rem;
  color: var(--app-text-muted);
  text-align: center;
}
</style>
