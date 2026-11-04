<template>
  <div class="rings-rules">
    <RingsFigure />

    <section class="rules-section">
      <h4>{{ t('rings.rules.sections.setup.title') }}</h4>
      <ul>
          <li v-for="n in 3" :key="'setup-' + n">{{ ruleText(`rings.rules.sections.setup.items.${n - 1}`) }}</li>
      </ul>
    </section>

    <section class="rules-section">
      <h4>{{ t('rings.rules.sections.play.title') }}</h4>
      <ul>
          <li v-for="n in 4" :key="'play-' + n">{{ ruleText(`rings.rules.sections.play.items.${n - 1}`) }}</li>
      </ul>
    </section>

    <section class="rules-section">
      <h4>{{ t('rings.rules.sections.scale.title') }}</h4>
      <ul class="scale">
        <li v-for="target in RINGS_TARGETS" :key="target">
          <Tag class="scale-points" :value="pointsLabel(target)" severity="success" />
          <span>{{ t('rings.rules.sections.scale.ring', { distance: distanceLabel(target) }) }}</span>
        </li>
        <li>
          <Tag class="scale-points" :value="pointsText(0)" severity="danger" />
          <span>{{ t('rings.rules.sections.scale.missed') }}</span>
        </li>
      </ul>
      <p class="max-score">{{ t('rings.rules.sections.scale.max', { n: RINGS_MAX_SESSION_SCORE }) }}</p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Tag from 'primevue/tag'
import RingsFigure from './RingsFigure.vue'
import { useRingsTargetLabels } from '../../composables/useRingsTargetLabels'
import {
  RINGS_MAX_SESSION_SCORE,
  RINGS_RING_DIAMETER_CM,
  RINGS_TARGETS,
  RINGS_THROWS_PER_SESSION,
} from '../../models/Rings'

const { t } = useI18n()
const { distanceLabel, pointsLabel, pointsText } = useRingsTargetLabels()

const ruleParams = computed(() => ({
  near: distanceLabel('near'),
  middle: distanceLabel('middle'),
  far: distanceLabel('far'),
  diameter: RINGS_RING_DIAMETER_CM,
  balls: RINGS_THROWS_PER_SESSION,
}))

function ruleText(key: string): string {
  return t(key, ruleParams.value)
}
</script>

<style scoped>
.rings-rules {
  display: grid;
  gap: var(--app-space-md);
}

.rules-section h4 {
  margin: 0 0 var(--app-space-xs);
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
}

.rules-section ul {
  margin: 0;
  padding-left: 1.25rem;
  display: grid;
  gap: 0.375rem;
  font-size: 0.9375rem;
  line-height: 1.45;
}

.rules-section ul.scale {
  padding-left: 0;
  list-style: none;
}

.scale li {
  display: grid;
  grid-template-columns: 4rem 1fr;
  align-items: center;
  gap: var(--app-space-sm);
}

.scale-points {
  justify-content: center;
  font-variant-numeric: tabular-nums;
}

.max-score {
  margin: var(--app-space-sm) 0 0;
  font-size: 0.9375rem;
  font-weight: 600;
}
</style>
