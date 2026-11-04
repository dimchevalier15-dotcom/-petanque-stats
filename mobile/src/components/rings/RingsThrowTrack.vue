<template>
  <ol class="track" :aria-label="t('rings.play.trackLabel')">
    <li v-for="slot in slots" :key="slot.number" class="track-slot" :class="slot.className">
      <template v-if="slot.played">
        <span>{{ distanceLabel(slot.played.target) }}</span>
        <span class="slot-score">+{{ ringsThrowScore(slot.played.target, slot.played.success) }}</span>
      </template>
      <span v-else>{{ slot.number }}</span>
    </li>
  </ol>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRingsTargetLabels } from '../../composables/useRingsTargetLabels'
import { RINGS_THROWS_PER_SESSION, ringsThrowScore, type RingsThrow } from '../../models/Rings'

const props = withDefaults(defineProps<{ throws: readonly RingsThrow[]; highlightNext?: boolean }>(), {
  highlightNext: false,
})

const { t } = useI18n()
const { distanceLabel } = useRingsTargetLabels()

const slots = computed(() =>
  Array.from({ length: RINGS_THROWS_PER_SESSION }, (_, index) => {
    const played = props.throws[index]
    let className = ''
    if (played) className = played.success ? 'is-success' : 'is-missed'
    else if (props.highlightNext && index === props.throws.length) className = 'is-current'
    return { number: index + 1, played, className }
  }),
)
</script>

<style scoped>
.track {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.375rem;
}

.track-slot {
  min-height: 2.5rem;
  border-radius: var(--app-radius-sm);
  border: 1px dashed var(--app-border);
  display: grid;
  place-items: center;
  align-content: center;
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--app-text-subtle);
  line-height: 1.15;
}

.track-slot.is-current {
  border: 2px solid var(--app-primary);
  color: var(--app-primary-dark);
}

.track-slot.is-success {
  border: none;
  background: #22c55e;
  color: #fff;
}

.track-slot.is-missed {
  border: none;
  background: #ef4444;
  color: #fff;
}

.slot-score {
  font-size: 0.8125rem;
  font-weight: 800;
}
</style>
