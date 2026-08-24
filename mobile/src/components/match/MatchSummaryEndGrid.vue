<template>
  <details v-if="endIndexes.length > 0" class="end-grid app-card">
    <summary class="end-grid-summary">{{ t('summary.sections.endGrid') }}</summary>
    <div class="end-grid-body">
      <p class="end-grid-hint">{{ t('summary.endGrid.hint') }}</p>
      <div class="end-grid-scroll">
        <table class="end-grid-table">
          <thead>
            <tr>
              <th scope="col" class="sticky-col">{{ t('summary.endGrid.player') }}</th>
              <th v-for="endIndex in endIndexes" :key="endIndex" scope="col">
                {{ t('play.formChart.endLabel', { n: endIndex }) }}
              </th>
              <th scope="col" class="total-col">{{ t('summary.endGrid.total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="player in orderedPlayers" :key="player.playerId">
              <th scope="row" class="sticky-col" :class="`player-cell--${player.team}`">
                {{ playerShortName(player) }}
              </th>
              <td v-for="endIndex in endIndexes" :key="`${player.playerId}-${endIndex}`">
                <span
                  v-if="endTotalByIndex(player, endIndex) !== null"
                  class="cell"
                  :class="`cell--${endTotalTone(endTotalByIndex(player, endIndex)!)}`"
                >
                  {{ formatSignedTotal(endTotalByIndex(player, endIndex)!) }}
                </span>
                <span v-else class="cell cell--empty">–</span>
              </td>
              <td class="total-col">
                <span
                  v-if="playerEndTotalsSum(player) !== null"
                  class="cell cell--total"
                  :class="`cell--${endTotalTone(playerEndTotalsSum(player)!)}`"
                >
                  {{ formatSignedTotal(playerEndTotalsSum(player)!) }}
                </span>
                <span v-else class="cell cell--empty">–</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </details>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { MatchSummaryPlayer } from '../../models/MatchSummary'
import {
  endTotalByIndex,
  endTotalTone,
  formatSignedTotal,
  playerEndTotalsSum,
} from '../../models/MatchEndGrid'
import { playerShortName } from '../../composables/useMatchSummaryCharts'

const props = defineProps<{
  players: MatchSummaryPlayer[]
  endIndexes: number[]
}>()

const { t } = useI18n()

const orderedPlayers = computed(() => {
  const teamA = props.players.filter((player) => player.team === 'A')
  const teamB = props.players.filter((player) => player.team === 'B')
  return [...teamA, ...teamB]
})
</script>

<style scoped>
.end-grid {
  padding: 0;
  overflow: hidden;
}

.end-grid-summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.375rem;
  padding: var(--app-space-md);
  font-size: 1rem;
  font-weight: 700;
  user-select: none;
}

.end-grid-summary::-webkit-details-marker {
  display: none;
}

.end-grid-summary::after {
  content: '';
  width: 0.4rem;
  height: 0.4rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.15s ease;
  opacity: 0.55;
  flex-shrink: 0;
}

.end-grid[open] .end-grid-summary::after {
  transform: rotate(-135deg);
}

.end-grid-body {
  display: grid;
  gap: 0.5rem;
  padding: 0 var(--app-space-md) var(--app-space-md);
  border-top: 1px solid var(--app-border);
}

.end-grid-hint {
  margin: 0.5rem 0 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.end-grid-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 0 -0.25rem;
  padding: 0.125rem 0.25rem 0.25rem;
}

.end-grid-table {
  width: max-content;
  min-width: 100%;
  border-collapse: separate;
  border-spacing: 0.25rem 0.375rem;
  font-size: 0.75rem;
}

.end-grid-table th,
.end-grid-table td {
  text-align: center;
  font-weight: 600;
  padding: 0;
}

.end-grid-table thead th {
  color: var(--app-text-muted);
  font-size: 0.6875rem;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.sticky-col {
  position: sticky;
  left: 0;
  z-index: 1;
  min-width: 4.75rem;
  max-width: 6.5rem;
  text-align: left !important;
  padding-right: 0.5rem !important;
  background: var(--app-surface);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

thead .sticky-col {
  z-index: 2;
}

.player-cell--A {
  color: #15803d;
}

.player-cell--B {
  color: #1d4ed8;
}

.total-col {
  padding-left: 0.25rem;
}

.cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.25rem;
  height: 1.85rem;
  padding: 0 0.35rem;
  border-radius: 0.5rem;
  font-variant-numeric: tabular-nums;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.cell--p2 {
  background: #dbeafe;
  color: #1d4ed8;
}

.cell--p1 {
  background: #dcfce7;
  color: #15803d;
}

.cell--p0 {
  background: #f1f5f9;
  color: #475569;
}

.cell--m1 {
  background: #fef3c7;
  color: #b45309;
}

.cell--m2 {
  background: #fee2e2;
  color: #b91c1c;
}

.cell--total {
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
}

.cell--empty {
  background: transparent;
  color: var(--app-text-subtle);
  font-weight: 500;
}
</style>
