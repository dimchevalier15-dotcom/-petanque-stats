<template>
  <div class="success-rate">
    <span class="success-rate-label">{{ t('stats.successRate.label') }}</span>
    <strong v-if="rate !== null">
      {{ t('stats.successRate.value', { rate }) }}
      <span v-if="masters" class="success-rate-masters">({{ formatMasters(masters) }})</span>
    </strong>
    <span v-else class="success-rate-empty">{{ t('stats.successRate.empty') }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { MatchSummaryShotBreakdown } from '../../models/MatchSummary'
import { formatMasters, shotMasters, shotSuccessRate } from '../../composables/matchSuccessRate'

const props = defineProps<{
  breakdown?: MatchSummaryShotBreakdown | null
}>()

const { t } = useI18n()

const rate = computed(() => shotSuccessRate(props.breakdown))
const masters = computed(() => shotMasters(props.breakdown))
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

.success-rate-masters {
  font-weight: 700;
  color: var(--app-text-muted, #94a3b8);
}

.success-rate-empty {
  color: var(--app-text-muted, #94a3b8);
  font-weight: 600;
}
</style>
