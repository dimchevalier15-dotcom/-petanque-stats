<template>
  <AppPage>
    <PageHeader :title="t('jumpShot.victory.title')" :subtitle="t('jumpShot.victory.subtitle')" :back-to="{ name: 'jumpShotHome' }" />

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <template v-else-if="summary">
      <div class="victory-card app-card">
        <i class="pi pi-trophy victory-icon" aria-hidden="true" />
        <p class="victory-meta">
          {{ t('jumpShot.victory.seriesCount', { n: summary.seriesCount }) }}
        </p>
      </div>

      <section class="recap app-card">
        <h3>{{ t('jumpShot.victory.recapTitle') }}</h3>
        <ul class="series-list">
          <li v-for="series in summary.series" :key="series.seriesNumber">
            <span>{{ t('jumpShot.victory.seriesLine', { n: series.seriesNumber, distance: series.distance }) }}</span>
            <Tag
              :value="t('jumpShot.victory.successTag', { count: series.successCount })"
              :severity="series.isSpecialSeries ? 'warn' : 'info'"
            />
          </li>
        </ul>
      </section>

      <Button class="home-btn" :label="t('jumpShot.victory.backHome')" @click="goHome" />
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
import type { JumpShotSessionSummary } from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

const { t } = useI18n()
const router = useRouter()
const vueRoute = useRoute()

const sessionId = computed(() => Number(vueRoute.params.id))
const loading = ref(true)
const summary = ref<JumpShotSessionSummary | null>(null)

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

.victory-card {
  padding: var(--app-space-xl) var(--app-space-md);
  text-align: center;
  display: grid;
  gap: var(--app-space-sm);
  justify-items: center;
}

.victory-icon {
  font-size: 3rem;
  color: #ca8a04;
}

.victory-meta {
  margin: 0;
  font-size: 1rem;
}

.recap {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.recap h3 {
  margin: 0;
  font-size: 1rem;
}

.series-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.5rem;
}

.series-list li {
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
