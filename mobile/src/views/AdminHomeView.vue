<template>
  <AppPage>
    <PageHeader :title="t('admin.home.title')" :back-to="{ name: 'home' }" />

    <div class="quick-grid">
      <button type="button" class="quick-item app-card" @click="goImpersonate">
        <i class="pi pi-user" aria-hidden="true" />
        <span>{{ t('admin.home.impersonate') }}</span>
      </button>
      <button type="button" class="quick-item app-card" @click="goCompetitions">
        <i class="pi pi-trophy" aria-hidden="true" />
        <span>{{ t('admin.home.competitions') }}</span>
      </button>
      <button type="button" class="quick-item app-card" @click="goClubs">
        <i class="pi pi-building" aria-hidden="true" />
        <span>{{ t('admin.home.clubs') }}</span>
      </button>
      <button type="button" class="quick-item app-card" @click="goCoachAdmin">
        <i class="pi pi-users" aria-hidden="true" />
        <span>{{ t('admin.home.coach') }}</span>
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

function goImpersonate(): void {
  router.push({ name: 'adminImpersonate' })
}

function goCompetitions(): void {
  router.push({ name: 'adminCompetitions' })
}

function goClubs(): void {
  router.push({ name: 'adminClubs' })
}

function goCoachAdmin(): void {
  router.push({ name: 'adminCoach' })
}
</script>

<style scoped>
.quick-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.quick-item {
  min-height: 5.5rem;
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  justify-items: center;
  align-content: center;
  text-align: center;
  border: none;
  cursor: pointer;
  font: inherit;
  color: var(--app-text);
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.quick-item i {
  font-size: 1.25rem;
  color: var(--app-primary);
}

.quick-item span {
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.25;
}

.quick-item:active {
  transform: scale(0.98);
}
</style>
