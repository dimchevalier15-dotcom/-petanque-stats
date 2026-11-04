<template>
  <div class="rings-rules">
    <RingsShootingFigure />

    <section class="rules-section">
      <h4>{{ t('ringsShooting.rules.sections.setup.title') }}</h4>
      <ul>
        <li v-for="n in 5" :key="'setup-' + n">{{ ruleText(`ringsShooting.rules.sections.setup.items.${n - 1}`) }}</li>
      </ul>
      <RingsShootingDetailFigure class="detail" />
    </section>

    <section class="rules-section">
      <h4>{{ t('ringsShooting.rules.sections.play.title') }}</h4>
      <ul>
        <li v-for="n in 3" :key="'play-' + n">{{ ruleText(`ringsShooting.rules.sections.play.items.${n - 1}`) }}</li>
      </ul>
    </section>

    <section class="rules-section carreau-section">
      <h4>{{ t('ringsShooting.rules.sections.carreau.title') }}</h4>
      <p class="carreau-lead">{{ t('ringsShooting.rules.sections.carreau.lead') }}</p>
      <ul>
        <li v-for="n in 4" :key="'carreau-' + n">
          {{ ruleText(`ringsShooting.rules.sections.carreau.items.${n - 1}`) }}
        </li>
      </ul>
    </section>

    <section class="rules-section">
      <h4>{{ t('ringsShooting.rules.sections.invalid.title') }}</h4>
      <ul>
        <li v-for="n in 3" :key="'invalid-' + n">
          {{ ruleText(`ringsShooting.rules.sections.invalid.items.${n - 1}`) }}
        </li>
      </ul>
    </section>

    <section class="rules-section">
      <h4>{{ t('ringsShooting.rules.sections.scale.title') }}</h4>
      <p class="scale-intro">{{ t('ringsShooting.rules.sections.scale.intro') }}</p>
      <ul class="scale">
        <li class="scale-head">
          <span>{{ t('ringsShooting.rules.sections.scale.hit') }}</span>
          <span>{{ t('ringsShooting.rules.sections.scale.carreau') }}</span>
          <span />
        </li>
        <li v-for="target in RINGS_TARGETS" :key="target">
          <Tag class="scale-points" :value="pointsLabel(target)" severity="success" />
          <Tag class="scale-points" :value="pointsText(ringsThrowScore(target, true, true))" severity="help" />
          <span>{{ t('ringsShooting.rules.sections.scale.ring', { distance: distanceLabel(target) }) }}</span>
        </li>
        <li>
          <Tag class="scale-points scale-span" :value="pointsText(0)" severity="danger" />
          <span>{{ t('ringsShooting.rules.sections.scale.missed') }}</span>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Tag from 'primevue/tag'
import RingsShootingDetailFigure from './RingsShootingDetailFigure.vue'
import RingsShootingFigure from './RingsShootingFigure.vue'
import { useRingsTargetLabels } from '../../composables/useRingsTargetLabels'
import {
  RINGS_RING_DIAMETER_CM,
  RINGS_SHOOTING_JACK_GAP_CM,
  RINGS_SHOOTING_SIDE_GAP_CM,
  RINGS_TARGETS,
  RINGS_THROWS_PER_SESSION,
  ringsMaxSessionScore,
  ringsThrowScore,
} from '../../models/Rings'

const { t } = useI18n()
const { distanceLabel, pointsLabel, pointsText } = useRingsTargetLabels()

const i18nParams = computed(() => ({
  near: distanceLabel('near'),
  middle: distanceLabel('middle'),
  far: distanceLabel('far'),
  diameter: RINGS_RING_DIAMETER_CM,
  balls: RINGS_THROWS_PER_SESSION,
  jackGap: RINGS_SHOOTING_JACK_GAP_CM,
  sideGap: RINGS_SHOOTING_SIDE_GAP_CM,
  maxScore: ringsMaxSessionScore('shooting'),
}))

function ruleText(key: string): string {
  return t(key, i18nParams.value)
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

.carreau-section {
  padding: var(--app-space-sm);
  border-radius: var(--app-radius-md);
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
}

.carreau-lead {
  margin: 0 0 var(--app-space-xs);
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.45;
}

.scale-intro {
  margin: 0 0 var(--app-space-sm);
  font-size: 0.9375rem;
  line-height: 1.45;
}

.detail {
  margin-top: var(--app-space-sm);
}

.scale li {
  display: grid;
  grid-template-columns: 4rem 4rem 1fr;
  align-items: center;
  gap: var(--app-space-sm);
}

.scale li.scale-head {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
  text-align: center;
}

.scale-span {
  grid-column: 1 / 3;
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
