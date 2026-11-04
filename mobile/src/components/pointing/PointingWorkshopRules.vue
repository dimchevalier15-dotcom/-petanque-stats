<template>
  <div class="workshop-rules">
    <header class="workshop-head">
      <span class="workshop-pill">{{ t('pointing.rules.workshopNumber', { n: workshop }) }}</span>
      <h3>{{ t(`pointing.workshops.${pointingWorkshopI18nKey(workshop)}`) }}</h3>
    </header>

    <figure class="workshop-figure">
      <img :src="WORKSHOP_FIGURES[workshop]" :alt="t('pointing.rules.figureAlt', { n: workshop })" />
    </figure>

    <section class="rules-section">
      <h4>{{ t('pointing.rules.labels.setup') }}</h4>
      <p>{{ t(`pointing.rules.workshops.w${workshop}.setup`) }}</p>
    </section>

    <section class="rules-section">
      <h4>{{ t('pointing.rules.labels.objective') }}</h4>
      <p class="objective">{{ t(`pointing.rules.workshops.w${workshop}.objective`) }}</p>
    </section>

    <section class="rules-section">
      <h4>{{ t('pointing.rules.labels.scale') }}</h4>
      <ul class="scale">
        <li v-for="result in resultsForWorkshop(workshop)" :key="result">
          <Tag
            class="scale-points"
            :value="t('pointing.rules.points', { n: pointsFor(workshop, result) })"
            :severity="pointingShotScoreSeverity(pointsFor(workshop, result))"
          />
          <span>{{ t(`pointing.rules.workshops.w${workshop}.scale.${result}`) }}</span>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Tag from 'primevue/tag'
import {
  pointingShotScoreSeverity,
  pointingWorkshopI18nKey,
  pointsFor,
  resultsForWorkshop,
  type PointingWorkshop,
} from '../../models/Pointing'
import workshop1 from '../../assets/pointing/workshop-1.png'
import workshop2 from '../../assets/pointing/workshop-2.png'
import workshop3 from '../../assets/pointing/workshop-3.png'
import workshop4 from '../../assets/pointing/workshop-4.png'
import workshop5 from '../../assets/pointing/workshop-5.png'

defineProps<{ workshop: PointingWorkshop }>()

const WORKSHOP_FIGURES: Record<PointingWorkshop, string> = {
  1: workshop1,
  2: workshop2,
  3: workshop3,
  4: workshop4,
  5: workshop5,
}

const { t } = useI18n()
</script>

<style scoped>
.workshop-rules {
  display: grid;
  gap: var(--app-space-md);
}

.workshop-head {
  display: grid;
  gap: 0.375rem;
  justify-items: start;
}

.workshop-head h3 {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 800;
}

.workshop-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.1875rem 0.625rem;
  border-radius: 999px;
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.workshop-figure {
  margin: 0;
  display: flex;
  justify-content: center;
  padding: var(--app-space-sm);
  border-radius: var(--app-radius);
  background: #fff;
  border: 1px solid var(--app-border);
}

.workshop-figure img {
  width: min(100%, 15rem);
  height: auto;
}

.rules-section h4 {
  margin: 0 0 var(--app-space-xs);
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--app-text-subtle);
}

.rules-section p {
  margin: 0;
  font-size: 0.9375rem;
  line-height: 1.45;
}

.rules-section .objective {
  font-weight: 600;
}

.scale {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.375rem;
  font-size: 0.9375rem;
  line-height: 1.45;
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
</style>
