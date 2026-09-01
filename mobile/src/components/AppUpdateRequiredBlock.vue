<template>
  <div v-if="appUpdate.showRequiredBlock" class="app-update-block" role="alertdialog" aria-modal="true">
    <div class="app-update-panel app-card">
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
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import { useAppUpdateStore } from '../stores/appUpdate'

const { t } = useI18n()
const appUpdate = useAppUpdateStore()
</script>

<style scoped>
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
  text-align: center;
  box-shadow: var(--app-shadow-md);
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
  color: var(--app-text-muted);
  font-size: 0.9375rem;
}
</style>
