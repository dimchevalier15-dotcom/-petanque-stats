<template>
  <section v-if="appUpdate.showRecommendedBanner" class="app-update-banner app-card">
    <div class="banner-text">
      <div class="banner-title">
        <span>
          <span aria-hidden="true">🚀</span>
          <span>{{ t('app.update.recommendedTitle') }}</span>
        </span>
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
      <p>{{ t('app.update.recommendedSubtitle') }}</p>
    </div>
    <div class="banner-actions">
      <Button
        type="button"
        size="small"
        :label="t('app.update.action')"
        @click="appUpdate.openStore()"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import { useAppUpdateStore } from '../stores/appUpdate'

const { t } = useI18n()
const appUpdate = useAppUpdateStore()
</script>

<style scoped>
.app-update-banner {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-md);
  margin-bottom: var(--app-space-md);
  border-color: #bfdbfe;
  background: #eff6ff;
  color: #1e3a8a;
}

.banner-text {
  min-width: 0;
}

.banner-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
}

.banner-text p {
  margin: 0.25rem 0 0;
  font-size: 0.8125rem;
  line-height: 1.4;
}

.banner-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
</style>
