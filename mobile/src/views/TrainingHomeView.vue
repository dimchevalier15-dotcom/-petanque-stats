<template>
  <AppPage>
    <PageHeader
      :title="t('training.menu.title')"
      :subtitle="t('training.menu.subtitle')"
      :back-to="{ name: 'home' }"
    />
    <div class="exercise-grid">
      <button type="button" class="exercise-card app-card" @click="goBoules">
        <i class="pi pi-flag exercise-icon" aria-hidden="true" />
        <span class="exercise-title">{{ t('training.menu.boules.title') }}</span>
        <span class="exercise-desc">{{ t('training.menu.boules.description') }}</span>
      </button>

      <button type="button" class="exercise-card app-card" @click="goPrecisionShooting">
        <i class="pi pi-bullseye exercise-icon" aria-hidden="true" />
        <span class="exercise-title">{{ t('training.menu.precisionShooting.title') }}</span>
        <span class="exercise-desc">{{ t('training.menu.precisionShooting.description') }}</span>
      </button>

      <button type="button" class="exercise-card app-card" @click="goPointPrecision">
        <i class="pi pi-map-marker exercise-icon" aria-hidden="true" />
        <span class="exercise-title">{{ t('training.menu.pointPrecision.title') }}</span>
        <span class="exercise-desc">{{ t('training.menu.pointPrecision.description') }}</span>
      </button>

      <button type="button" class="exercise-card app-card" @click="goTirSautee">
        <i class="pi pi-arrow-up-right exercise-icon" aria-hidden="true" />
        <span class="exercise-title">{{ t('training.menu.tirSautee.title') }}</span>
        <span class="exercise-desc">{{ t('training.menu.tirSautee.description') }}</span>
      </button>
    </div>
  </AppPage>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'

const { t } = useI18n()
const router = useRouter()

function goBoules(): void {
  router.push({ name: 'trainingBoules' })
}

function goPrecisionShooting(): void {
  router.push({ name: 'shootingHome' })
}

function goPointPrecision(): void {
  router.push({ name: 'trainingPointPrecision' })
}

function goTirSautee(): void {
  router.push({ name: 'jumpShotHome' })
}
</script>

<style scoped>
.exercise-grid {
  display: grid;
  gap: var(--app-space-sm);
}

.exercise-card {
  width: 100%;
  padding: var(--app-space-md);
  display: grid;
  gap: 0.375rem;
  justify-items: start;
  text-align: left;
  border: none;
  cursor: pointer;
  font: inherit;
  color: inherit;
  transition: transform 0.12s ease;
}

.exercise-card:active {
  transform: scale(0.99);
}

.exercise-icon {
  font-size: 1.25rem;
  color: var(--app-primary);
}

.exercise-title {
  font-size: 1rem;
  font-weight: 700;
}

.exercise-desc {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  line-height: 1.35;
}
</style>
