<template>
  <Dialog
    v-model:visible="visible"
    :header="t('rings.rules.title')"
    :modal="true"
    :dismissableMask="true"
    class="rings-rules-dialog"
  >
    <RingsRules />
    <Button class="close-btn" :label="t('rings.rules.close')" @click="visible = false" />
  </Dialog>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import RingsRules from './RingsRules.vue'

const visible = defineModel<boolean>('visible', { default: false })

const { t } = useI18n()
</script>

<style scoped>
.close-btn {
  width: 100%;
  min-height: var(--app-touch-min);
  margin-top: var(--app-space-lg);
  font-weight: 700;
}
</style>

<style>
.rings-rules-dialog.p-dialog {
  width: min(92vw, 26rem);
  max-height: 90dvh;
  border-radius: var(--app-radius-lg);
  overflow: hidden;
  box-shadow: var(--app-shadow-md);
}

.rings-rules-dialog .p-dialog-header {
  padding: var(--app-space-md) var(--app-space-lg);
  border-bottom: 1px solid var(--app-border);
}

.rings-rules-dialog .p-dialog-title {
  font-size: 1rem;
  font-weight: 700;
}

.rings-rules-dialog .p-dialog-content {
  padding: var(--app-space-lg);
  overflow-y: auto;
}
</style>
