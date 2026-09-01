<template>
  <button
    type="button"
    class="notation-help-btn"
    :class="{ 'notation-help-btn--compact': compact }"
    :aria-label="t('notationHelp.ariaLabel')"
    :title="t('notationHelp.ariaLabel')"
    @click="$emit('click')"
  >
    <i class="pi pi-question-circle" aria-hidden="true" />
  </button>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  compact?: boolean
}>()

defineEmits<{
  click: []
}>()

const { t } = useI18n()
</script>

<style scoped>
.notation-help-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: 1px solid var(--app-border);
  border-radius: 999px;
  background: var(--app-surface, #fff);
  color: var(--app-text-muted);
  cursor: pointer;
  transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease;
}

.notation-help-btn:hover,
.notation-help-btn:focus-visible {
  color: var(--app-primary);
  border-color: var(--app-primary);
  background: color-mix(in srgb, var(--app-primary) 8%, transparent);
  outline: none;
}

.notation-help-btn--compact {
  width: 1.625rem;
  height: 1.625rem;
  font-size: 0.875rem;
}

.notation-help-btn .pi {
  font-size: 1rem;
}

.notation-help-btn--compact .pi {
  font-size: 0.875rem;
}
</style>
