<template>
  <AppPage>
    <PageHeader :title="t('rings.summary.title')" :subtitle="t(config.titleKey)" :back-to="{ name: config.routes.home }" />

    <section v-if="summary" class="summary">
      <div class="hero-banner app-card">
        <span class="hero-badge">{{ t('rings.summary.sessionFinished') }}</span>
        <strong class="hero-score">{{ summary.totalScore }}<span class="hero-max">/{{ maxScore }}</span></strong>
        <span class="hero-meta">
          {{ t('rings.play.successCount', { count: successCount, total: summary.throws.length }) }}
        </span>
        <span v-if="allowsCarreau" class="hero-meta">
          {{ t('ringsShooting.play.carreauCount', { count: carreauCount }) }}
        </span>
        <span class="hero-date">{{ formatShortDate(summary.playedAt) }}</span>
      </div>

      <section class="panel app-card">
        <h3>{{ t('rings.summary.byRing') }}</h3>
        <ul class="breakdown">
          <li v-for="row in breakdown" :key="row.target">
            <span class="ring-distance">{{ distanceLabel(row.target) }}</span>
            <span class="ring-attempts">
              {{ t('rings.summary.attempts', { successes: row.successes, attempts: row.attempts }) }}
              <template v-if="allowsCarreau && row.carreaux > 0">
                · {{ t('ringsShooting.play.carreauCount', { count: row.carreaux }) }}
              </template>
            </span>
            <strong class="ring-points">+{{ row.points }}</strong>
          </li>
        </ul>
      </section>

      <section class="panel app-card">
        <h3>{{ t('rings.summary.throws') }}</h3>
        <RingsThrowTrack :throws="summary.throws" />
      </section>

      <Button class="w-full" :label="t('rings.summary.backHome')" @click="goHome" />
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import RingsThrowTrack from '../components/rings/RingsThrowTrack.vue'
import { useDateFormat } from '../composables/useDateFormat'
import { useRingsTargetLabels } from '../composables/useRingsTargetLabels'
import {
  RINGS_MODE_CONFIG,
  ringsAllowsCarreau,
  ringsMaxSessionScore,
  ringsTargetBreakdown,
  type RingsMode,
  type RingsSessionSummary,
} from '../models/Rings'
import { ringsSessionsService } from '../services/ringsSessions'

const props = withDefaults(defineProps<{ mode?: RingsMode }>(), { mode: 'point' })

const { t } = useI18n()
const { formatShortDate } = useDateFormat()
const { distanceLabel } = useRingsTargetLabels()
const route = useRoute()
const router = useRouter()

const config = computed(() => RINGS_MODE_CONFIG[props.mode])
const allowsCarreau = computed(() => ringsAllowsCarreau(props.mode))
const maxScore = computed(() => ringsMaxSessionScore(props.mode))

const sessionId = Number(route.params.id)
const summary = ref<RingsSessionSummary | null>(null)

const breakdown = computed(() => ringsTargetBreakdown(summary.value?.throws ?? []))
const successCount = computed(() => summary.value?.throws.filter((th) => th.success).length ?? 0)
const carreauCount = computed(() => summary.value?.throws.filter((th) => th.carreau).length ?? 0)

function goHome(): void {
  void router.push({ name: config.value.routes.home })
}

onMounted(async () => {
  try {
    summary.value = await ringsSessionsService.getSummary(sessionId)
  } catch {
    void router.replace({ name: config.value.routes.home })
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

.hero-meta,
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

.breakdown {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-xs);
}

.breakdown li {
  display: grid;
  grid-template-columns: 4rem 1fr auto;
  align-items: center;
  gap: var(--app-space-sm);
  font-size: 0.9375rem;
}

.ring-distance {
  font-weight: 800;
}

.ring-attempts {
  color: var(--app-text-muted);
}

.ring-points {
  color: var(--app-primary-dark);
  font-variant-numeric: tabular-nums;
}

.w-full {
  width: 100%;
}
</style>
