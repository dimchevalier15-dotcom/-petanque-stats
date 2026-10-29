<template>
  <AppPage>
    <PageHeader
      :title="t('jumpShot.summary.title')"
      :subtitle="summary?.won ? t('jumpShot.summary.subtitleWon') : undefined"
      :back-to="{ name: 'jumpShotHome' }"
    />

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <template v-else-if="summary">
      <div class="hero-banner app-card">
        <span v-if="summary.won" class="hero-badge">{{ t('jumpShot.summary.won') }}</span>
        <strong class="hero-series">{{ t('jumpShot.summary.seriesCount', { n: summary.seriesCount }) }}</strong>
        <span v-if="summary.finishedAt" class="hero-date">{{ formatDate(summary.finishedAt) }}</span>
      </div>

      <section class="series-stack">
        <article
          v-for="series in summary.series"
          :key="series.seriesNumber"
          class="series-card app-card"
        >
          <div class="series-head">
            <h3>{{ t('jumpShot.summary.seriesTitle', { n: series.seriesNumber, distance: series.distance }) }}</h3>
            <div class="series-tags">
              <Tag :value="t('jumpShot.victory.successTag', { count: series.successCount })" severity="info" />
              <Tag
                v-if="series.isSpecialSeries"
                :value="t('jumpShot.summary.specialBadge')"
                severity="warn"
              />
            </div>
          </div>
          <p class="series-consequence">{{ consequenceLabel(series.consequence, series.successCount) }}</p>
          <ul class="throws-list">
            <li v-for="throwEntry in series.throws" :key="throwEntry.position">
              <span>{{ t('jumpShot.play.throwLabel', { n: throwEntry.position }) }}</span>
              <Tag :value="t(`jumpShot.results.${throwEntry.result}`)" :severity="throwSeverity(throwEntry.result)" />
            </li>
          </ul>
        </article>
      </section>

      <Button class="home-btn" :label="t('jumpShot.summary.backHome')" @click="goHome" />
    </template>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { useDateFormat } from '../composables/useDateFormat'
import type { JumpShotSeriesConsequence, JumpShotSessionSummary, JumpShotThrowResult } from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

const { t } = useI18n()
const { formatDate } = useDateFormat()
const route = useRoute()
const router = useRouter()

const sessionId = computed(() => Number(route.params.id))
const loading = ref(true)
const summary = ref<JumpShotSessionSummary | null>(null)

function throwSeverity(result: JumpShotThrowResult): 'secondary' | 'success' | 'warn' | 'danger' | 'info' {
  if (result === 'missed') return 'danger'
  if (result === 'carreau') return 'success'
  if (result === 'hit') return 'warn'
  return 'info'
}

function consequenceLabel(consequence: JumpShotSeriesConsequence, count: number): string {
  return t(`jumpShot.consequence.${consequence}`, { count })
}

async function load(): Promise<void> {
  loading.value = true
  try {
    summary.value = await jumpShotSessionsService.getSummary(sessionId.value)
  } finally {
    loading.value = false
  }
}

function goHome(): void {
  void router.push({ name: 'jumpShotHome' })
}

onMounted(() => {
  void load()
})
</script>

<style scoped>
.loading {
  display: grid;
  place-items: center;
  min-height: 10rem;
}

.hero-banner {
  padding: var(--app-space-lg) var(--app-space-md);
  display: grid;
  gap: 0.375rem;
  justify-items: center;
  text-align: center;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #fde68a;
}

.hero-badge {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #92400e;
}

.hero-series {
  font-size: 1.75rem;
  line-height: 1.1;
}

.hero-date {
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.series-stack {
  display: grid;
  gap: var(--app-space-sm);
}

.series-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.series-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
}

.series-head h3 {
  margin: 0;
  font-size: 0.9375rem;
}

.series-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  justify-content: flex-end;
}

.series-consequence {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.throws-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.375rem;
}

.throws-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.home-btn {
  width: 100%;
}
</style>
