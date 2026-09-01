<template>
  <div v-if="appUpdate.showRequiredBlock" class="app-update-block" role="alertdialog" aria-modal="true">
    <div class="app-update-panel">
      <div class="app-update-heading">
        <span aria-hidden="true">⚠️</span>
        <h2>{{ t('app.update.requiredTitle') }}</h2>
      </div>
      <p>{{ t('app.update.requiredSubtitle') }}</p>
      <Button
        type="button"
        :label="t('app.update.action')"
        @click="appUpdate.openStore()"
      />
    </div>
  </div>

  <div v-else-if="appUpdate.showRecommendedBanner" class="app-update-banner">
    <div class="banner-content">
      <div class="banner-text">
        <div class="banner-title">
          <span aria-hidden="true">🚀</span>
          <span>{{ t('app.update.recommendedTitle') }}</span>
        </div>
        <p>{{ t('app.update.recommendedSubtitle') }}</p>
      </div>
      <div class="banner-actions">
        <Button
          type="button"
          size="small"
          :label="t('app.update.action')"
          @click="appUpdate.openStore()"
        />
        <Button
          type="button"
          size="small"
          severity="secondary"
          text
          :aria-label="t('app.update.dismiss')"
          icon="pi pi-times"
          @click="appUpdate.dismissRecommended()"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import { useAppUpdateStore } from '../stores/appUpdate'

const { t } = useI18n()
const appUpdate = useAppUpdateStore()

onMounted(() => {
  void appUpdate.checkOnce()
})
</script>

<style scoped>
.app-update-banner {
  padding: 0.75rem var(--app-space-md);
  background: #eff6ff;
  border-bottom: 1px solid #bfdbfe;
  color: #1e3a8a;
}

.banner-content {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--app-space-sm);
}

.banner-text {
  min-width: 0;
}

.banner-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.banner-text p {
  margin: 0.25rem 0 0;
  font-size: 0.8125rem;
}

.banner-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-shrink: 0;
}

.app-update-block {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--app-space-md);
  background: rgba(15, 23, 42, 0.72);
}

.app-update-panel {
  width: min(100%, 24rem);
  padding: 1.5rem;
  border-radius: var(--app-radius-lg, 0.75rem);
  background: #ffffff;
  color: #0f172a;
  text-align: center;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
}

.app-update-heading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.app-update-heading h2 {
  margin: 0;
  font-size: 1.125rem;
}

.app-update-panel p {
  margin: 0.75rem 0 1.25rem;
  color: #475569;
  font-size: 0.9375rem;
}
</style>
