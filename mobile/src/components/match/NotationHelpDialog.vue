<template>
  <Dialog
    v-model:visible="visible"
    :header="t('notationHelp.title')"
    :modal="true"
    :dismissableMask="true"
    class="notation-help-dialog"
  >
    <ul class="notation-help-list" role="list">
      <li
        v-for="level in levels"
        :key="level.value"
        class="notation-help-item"
        :class="`notation-help-item--${level.value}`"
      >
        <span class="notation-help-value" :aria-label="level.label">{{ level.label }}</span>
        <p class="notation-help-meaning">{{ t(level.textKey) }}</p>
      </li>
    </ul>
    <div class="notation-help-actions">
      <Button
        class="notation-help-close"
        :label="t('notationHelp.close')"
        @click="visible = false"
      />
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

const visible = defineModel<boolean>('visible', { default: false })

const { t } = useI18n()

const levels = [
  { value: 'minus2', label: '-2', textKey: 'notationHelp.levels.minus2' },
  { value: 'minus1', label: '-1', textKey: 'notationHelp.levels.minus1' },
  { value: 'zero', label: '0', textKey: 'notationHelp.levels.zero' },
  { value: 'plus1', label: '+1', textKey: 'notationHelp.levels.plus1' },
  { value: 'plus2', label: '+2', textKey: 'notationHelp.levels.plus2' },
] as const
</script>

<style scoped>
.notation-help-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-md);
}

.notation-help-item {
  display: grid;
  grid-template-columns: 3rem 1fr;
  gap: var(--app-space-sm);
  align-items: start;
  padding: var(--app-space-sm) var(--app-space-md);
  border-radius: var(--app-radius-md);
  border-left: 4px solid var(--notation-accent, var(--app-border));
  background: var(--app-surface-muted, #f8fafc);
}

.notation-help-item--minus2 {
  --notation-accent: #ef4444;
}

.notation-help-item--minus1 {
  --notation-accent: #f97316;
}

.notation-help-item--zero {
  --notation-accent: #94a3b8;
}

.notation-help-item--plus1 {
  --notation-accent: #22c55e;
}

.notation-help-item--plus2 {
  --notation-accent: #8b5cf6;
}

.notation-help-value {
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1.3;
  color: var(--app-text);
  text-align: center;
}

.notation-help-meaning {
  margin: 0;
  font-size: 0.9375rem;
  line-height: 1.45;
  color: var(--app-text);
}

.notation-help-actions {
  display: flex;
  justify-content: center;
  margin-top: var(--app-space-lg);
}

.notation-help-close {
  min-width: 8rem;
  min-height: var(--app-touch-min);
  font-weight: 700;
}
</style>

<style>
.notation-help-dialog.p-dialog {
  width: min(92vw, 24rem);
  border-radius: var(--app-radius-lg);
  overflow: hidden;
  box-shadow: var(--app-shadow-md);
}

.notation-help-dialog .p-dialog-header {
  padding: var(--app-space-md) var(--app-space-lg);
  border-bottom: 1px solid var(--app-border);
  text-align: center;
}

.notation-help-dialog .p-dialog-title {
  width: 100%;
  font-size: 1rem;
  font-weight: 700;
}

.notation-help-dialog .p-dialog-content {
  padding: var(--app-space-lg);
}
</style>
