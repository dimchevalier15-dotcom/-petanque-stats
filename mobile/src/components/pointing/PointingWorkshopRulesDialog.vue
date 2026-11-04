<template>
  <Dialog
    v-model:visible="visible"
    :header="t('pointing.rules.title')"
    :modal="true"
    :dismissableMask="true"
    class="pointing-rules-dialog"
  >
    <PointingWorkshopRules :workshop="workshop" />
    <div class="actions">
      <Button
        :label="t('pointing.rules.allRules')"
        icon="pi pi-book"
        severity="secondary"
        outlined
        @click="emit('showAll')"
      />
      <Button :label="t('pointing.rules.close')" @click="visible = false" />
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import PointingWorkshopRules from './PointingWorkshopRules.vue'
import type { PointingWorkshop } from '../../models/Pointing'

defineProps<{ workshop: PointingWorkshop }>()
const emit = defineEmits<{ showAll: [] }>()
const visible = defineModel<boolean>('visible', { default: false })

const { t } = useI18n()
</script>

<style scoped>
.actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
  margin-top: var(--app-space-lg);
}

.actions :deep(.p-button) {
  min-height: var(--app-touch-min);
  font-weight: 700;
}
</style>

<style>
.pointing-rules-dialog.p-dialog {
  width: min(92vw, 26rem);
  max-height: 90dvh;
  border-radius: var(--app-radius-lg);
  overflow: hidden;
  box-shadow: var(--app-shadow-md);
}

.pointing-rules-dialog .p-dialog-header {
  padding: var(--app-space-md) var(--app-space-lg);
  border-bottom: 1px solid var(--app-border);
}

.pointing-rules-dialog .p-dialog-title {
  font-size: 1rem;
  font-weight: 700;
}

.pointing-rules-dialog .p-dialog-content {
  padding: var(--app-space-lg);
  overflow-y: auto;
}
</style>
