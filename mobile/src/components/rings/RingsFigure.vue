<template>
  <svg class="rings-figure" viewBox="0 0 300 470" role="img" :aria-label="t('rings.rules.figureAlt')">
    <rect x="0" y="0" width="300" height="470" rx="14" class="field" />

    <g v-for="ring in RINGS" :key="ring.target">
      <text :x="ring.labelX" :y="ring.cy - 58" class="label">{{ distanceLabel(ring.target) }}</text>
      <text :x="ring.labelX" :y="ring.cy - 42" class="label label-points">{{ pointsLabel(ring.target) }}</text>
      <circle :cx="ring.cx" :cy="ring.cy" r="34" class="ring" />
      <circle :cx="ring.cx" :cy="ring.cy" r="3.5" class="jack" />
      <circle v-for="(o, i) in ring.obstacles" :key="i" :cx="o.x" :cy="o.y" r="8.5" class="obstacle" />
    </g>

    <circle cx="150" cy="420" r="25" class="ring" />
    <text x="150" y="459" class="label label-center">{{ t('rings.rules.throwingCircle') }}</text>
  </svg>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRingsTargetLabels } from '../../composables/useRingsTargetLabels'
import type { RingsTarget } from '../../models/Rings'

const RINGS: ReadonlyArray<{
  target: RingsTarget
  cx: number
  cy: number
  labelX: number
  obstacles: ReadonlyArray<{ x: number; y: number }>
}> = [
  { target: 'far', cx: 136, cy: 125, labelX: 104, obstacles: [{ x: 117, y: 174 }, { x: 155, y: 173 }] },
  { target: 'middle', cx: 240, cy: 210, labelX: 210, obstacles: [{ x: 224, y: 258 }] },
  { target: 'near', cx: 56, cy: 292, labelX: 24, obstacles: [] },
]

const { t } = useI18n()
const { distanceLabel, pointsLabel } = useRingsTargetLabels()
</script>

<style scoped>
.rings-figure {
  display: block;
  width: min(100%, 17rem);
  height: auto;
  margin: 0 auto;
}

.field {
  fill: #fde3bf;
}

.ring {
  fill: none;
  stroke: #1f1f1f;
  stroke-width: 3;
}

.jack {
  fill: #e53935;
}

.obstacle {
  fill: #b4b4b4;
}

.label {
  font-size: 13px;
  font-weight: 700;
  fill: #4a2e1f;
  font-family: inherit;
}

.label-points {
  font-weight: 600;
}

.label-center {
  text-anchor: middle;
  font-size: 11px;
  font-weight: 600;
}
</style>
