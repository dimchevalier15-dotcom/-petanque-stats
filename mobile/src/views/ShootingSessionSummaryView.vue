<template>
  <AppPage>
    <PageHeader :title="t('shooting.summary.title')" :back-to="{ name: 'shootingHome' }" />

    <section v-if="summary" class="summary">
      <div class="hero-banner app-card">
        <span class="hero-badge">{{ t('shooting.summary.sessionFinished') }}</span>
        <strong class="hero-score">{{ summary.totalScore }}<span class="hero-max">/100</span></strong>
        <span class="hero-date">{{ formatDate(summary.finishedAt) }}</span>
      </div>

      <section class="workshops">
        <article v-for="w in summary.workshops" :key="w.workshop" class="workshop-panel app-card">
          <div class="workshop-panel-head">
            <h3>{{ workshopLabel(w.workshop) }}</h3>
            <Tag :value="`+${w.totalScore}`" severity="info" />
          </div>
          <div class="shots">
            <div v-for="shot in w.shots" :key="shot.distance" class="shot-row">
              <span class="shot-distance">{{ t('shooting.distanceMeters', { n: shot.distance }) }}</span>
              <Tag :value="resultLabel(shot.result)" :severity="severityFor(shot.result)" />
              <span class="shot-score">+{{ shot.score }}</span>
            </div>
          </div>
        </article>
      </section>

      <div class="app-actions">
        <Button class="w-full" :label="t('shooting.summary.actions.backHome')" @click="goHome" />
      </div>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { shootingSessionsService } from '../services/shootingSessions'
import type { ShootingSessionSummary, ShootingShotResult } from '../models/Shooting'

const { t, d } = useI18n()
const route = useRoute()
const router = useRouter()

const sessionId = Number(route.params.id)
const summary = ref<ShootingSessionSummary | null>(null)

function workshopLabel(workshop: number): string {
  const keys = ['ballAlone', 'ballBehindJack', 'betweenTwoBalls', 'jumpedBall', 'jack']
  return t(`shooting.workshops.${keys[workshop - 1]}`)
}

function resultLabel(result: string): string {
  return t(`shooting.results.${result}`)
}

function severityFor(result: ShootingShotResult): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
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

function formatDate(iso: string | null): string {
  if (!iso) return ''
  try {
    return d(new Date(iso), 'short') as string
  } catch {
    return new Date(iso).toLocaleDateString()
  }
}

function goHome(): void {
  router.push({ name: 'shootingHome' })
}

onMounted(async () => {
  if (!sessionId) {
    router.replace({ name: 'shootingHome' })
    return
  }
  try {
    summary.value = await shootingSessionsService.getSummary(sessionId)
  } catch {
    router.replace({ name: 'shootingHome' })
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

.workshops {
  display: grid;
  gap: var(--app-space-sm);
}

.workshop-panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.workshop-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.workshop-panel-head h3 {
  margin: 0;
  font-size: 0.9375rem;
}

.shots {
  display: grid;
  gap: 0.375rem;
}

.shot-row {
  display: grid;
  grid-template-columns: 3.5rem 1fr auto;
  align-items: center;
  gap: var(--app-space-sm);
  font-size: 0.875rem;
}

.shot-distance {
  font-weight: 700;
  color: var(--app-text-muted);
}

.shot-score {
  font-weight: 700;
  text-align: right;
}

.w-full {
  width: 100%;
}
</style>
