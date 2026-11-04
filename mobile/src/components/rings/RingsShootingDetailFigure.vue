<template>
  <svg class="detail-figure" viewBox="0 0 300 172" role="img" :aria-label="t('ringsShooting.rules.detailAlt')">
    <rect x="0" y="0" width="300" height="172" rx="14" class="field" />

    <g v-for="panel in PANELS" :key="panel.target">
      <text :x="panel.cx" y="16" class="label">
        {{ distanceLabel(panel.target) }} · {{ pointsLabel(panel.target) }}
      </text>
      <circle :cx="panel.cx" :cy="CY" :r="RING_R" class="ring" />
    </g>

    <circle :cx="PANELS[0].cx" :cy="CY" :r="BALL_R" class="target" />

    <circle :cx="PANELS[1].cx" :cy="CY" :r="BALL_R" class="target" />
    <circle :cx="PANELS[1].cx" :cy="JACK_CY" :r="JACK_R" class="jack" />
    <line :x1="PANELS[1].cx - 12" :y1="CY + BALL_R" :x2="PANELS[1].cx - 12" :y2="JACK_CY - JACK_R" class="dim" />
    <line :x1="PANELS[1].cx - 15" :y1="CY + BALL_R" :x2="PANELS[1].cx - 9" :y2="CY + BALL_R" class="dim" />
    <line :x1="PANELS[1].cx - 15" :y1="JACK_CY - JACK_R" :x2="PANELS[1].cx - 9" :y2="JACK_CY - JACK_R" class="dim" />
    <text :x="PANELS[1].cx - 8" :y="(CY + BALL_R + JACK_CY - JACK_R) / 2 + 3" class="dim-label dim-label-start">
      {{ t('ringsShooting.rules.cm', { n: RINGS_SHOOTING_JACK_GAP_CM }) }}
    </text>

    <circle :cx="PANELS[2].cx - SIDE_OFFSET" :cy="CY" :r="BALL_R" class="neighbour" />
    <circle :cx="PANELS[2].cx" :cy="CY" :r="BALL_R" class="target" />
    <circle :cx="PANELS[2].cx + SIDE_OFFSET" :cy="CY" :r="BALL_R" class="neighbour" />
    <line :x1="PANELS[2].cx + BALL_R" :y1="CY - 13" :x2="PANELS[2].cx + SIDE_OFFSET - BALL_R" :y2="CY - 13" class="dim" />
    <line :x1="PANELS[2].cx + BALL_R" :y1="CY - 16" :x2="PANELS[2].cx + BALL_R" :y2="CY - 10" class="dim" />
    <line
      :x1="PANELS[2].cx + SIDE_OFFSET - BALL_R"
      :y1="CY - 16"
      :x2="PANELS[2].cx + SIDE_OFFSET - BALL_R"
      :y2="CY - 10"
      class="dim"
    />
    <text :x="PANELS[2].cx + SIDE_OFFSET / 2" :y="CY - 20" class="dim-label">
      {{ t('ringsShooting.rules.cm', { n: RINGS_SHOOTING_SIDE_GAP_CM }) }}
    </text>

    <g class="legend">
      <circle cx="22" cy="136" :r="BALL_R" class="target" />
      <text x="33" y="140" class="legend-label">{{ t('ringsShooting.rules.legend.target') }}</text>
      <circle cx="122" cy="136" :r="BALL_R" class="neighbour" />
      <text x="133" y="140" class="legend-label">{{ t('ringsShooting.rules.legend.neighbour') }}</text>
      <circle cx="222" cy="136" :r="JACK_R + 0.5" class="jack" />
      <text x="232" y="140" class="legend-label">{{ t('ringsShooting.rules.legend.jack') }}</text>
    </g>

    <text x="150" y="162" class="caption">{{ t('ringsShooting.rules.detailCaption') }}</text>
  </svg>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRingsTargetLabels } from '../../composables/useRingsTargetLabels'
import {
  RINGS_RING_DIAMETER_CM,
  RINGS_SHOOTING_JACK_GAP_CM,
  RINGS_SHOOTING_SIDE_GAP_CM,
  type RingsTarget,
} from '../../models/Rings'

const RING_R = 40
const PX_PER_CM = RING_R / (RINGS_RING_DIAMETER_CM / 2)
const BALL_R = 3.75 * PX_PER_CM
const JACK_R = 1.5 * PX_PER_CM
const CY = 66
const JACK_CY = CY + BALL_R + RINGS_SHOOTING_JACK_GAP_CM * PX_PER_CM + JACK_R
const SIDE_OFFSET = 2 * BALL_R + RINGS_SHOOTING_SIDE_GAP_CM * PX_PER_CM

const PANELS: ReadonlyArray<{ target: RingsTarget; cx: number }> = [
  { target: 'near', cx: 50 },
  { target: 'middle', cx: 150 },
  { target: 'far', cx: 250 },
]

const { t } = useI18n()
const { distanceLabel, pointsLabel } = useRingsTargetLabels()
</script>

<style scoped>
.detail-figure {
  display: block;
  width: 100%;
  max-width: 24rem;
  height: auto;
  margin: 0 auto;
}

.field {
  fill: #fde3bf;
}

.ring {
  fill: none;
  stroke: #1f1f1f;
  stroke-width: 2;
}

.target {
  fill: #5c6670;
  stroke: #1f1f1f;
  stroke-width: 1;
}

.neighbour {
  fill: #b4b4b4;
}

.jack {
  fill: #e53935;
}

.dim {
  stroke: #4a2e1f;
  stroke-width: 1;
}

.label,
.dim-label,
.legend-label,
.caption {
  fill: #4a2e1f;
  font-family: inherit;
}

.label {
  font-size: 11px;
  font-weight: 700;
  text-anchor: middle;
}

.dim-label {
  font-size: 8px;
  font-weight: 700;
  text-anchor: middle;
}

.dim-label-start {
  text-anchor: start;
}

.legend-label {
  font-size: 10px;
  font-weight: 600;
}

.caption {
  font-size: 9px;
  font-style: italic;
  text-anchor: middle;
}
</style>
