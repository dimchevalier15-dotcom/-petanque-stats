<template>
  <div class="success-rate">
    <span class="success-rate-label">{{ t('stats.successRate.label') }}</span>
    <strong v-if="rate !== null">{{ t('stats.successRate.value', { rate }) }}</strong>
    <span v-else class="success-rate-empty">{{ t('stats.successRate.empty') }}</span>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  rate: number | null
}>()

const { t } = useI18n()
</script>

<style scoped>
.success-rate {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.8125rem;
}

.success-rate-label {
  font-weight: 600;
  color: var(--app-text-subtle, #64748b);
}

.success-rate strong {
  font-weight: 800;
}

.success-rate-empty {
  color: var(--app-text-muted, #94a3b8);
  font-weight: 600;
}
</style>
