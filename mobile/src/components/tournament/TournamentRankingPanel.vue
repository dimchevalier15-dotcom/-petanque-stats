<template>
  <section class="ranking">
    <p v-if="ranking.rows.length === 0" class="empty">{{ t('tournaments.ranking.empty') }}</p>

    <div v-else class="table-wrap app-card">
      <table>
        <colgroup>
          <col class="c-rank" />
          <col class="c-team" />
          <col span="8" class="c-num" />
        </colgroup>
        <thead>
          <tr>
            <th class="col-rank">{{ t('tournaments.ranking.position') }}</th>
            <th class="col-team">{{ t('tournaments.ranking.team') }}</th>
            <th>{{ t('tournaments.ranking.played') }}</th>
            <th>{{ t('tournaments.ranking.wins') }}</th>
            <th>{{ t('tournaments.ranking.draws') }}</th>
            <th>{{ t('tournaments.ranking.losses') }}</th>
            <th class="col-pts">{{ t('tournaments.ranking.points') }}</th>
            <th>{{ t('tournaments.ranking.for') }}</th>
            <th>{{ t('tournaments.ranking.against') }}</th>
            <th>{{ t('tournaments.ranking.diff') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in ranking.rows"
            :key="row.teamId"
            :class="{ 'row--qualified': row.qualifiedForPlayoffs }"
          >
            <td class="col-rank">
              <span class="rank" :class="rankTone(row.position)">{{ row.position }}</span>
            </td>
            <td class="col-team">
              <span class="team-cell">
                <span class="team-name">{{ row.teamName }}</span>
                <span
                  v-if="row.qualifiedForPlayoffs"
                  class="qual-dot"
                  :title="t('tournaments.ranking.qualified')"
                />
              </span>
            </td>
            <td>{{ row.played }}</td>
            <td>{{ row.wins }}</td>
            <td>{{ row.draws }}</td>
            <td>{{ row.losses }}</td>
            <td class="col-pts points">{{ row.points }}</td>
            <td>{{ row.pointsFor }}</td>
            <td>{{ row.pointsAgainst }}</td>
            <td :class="diffClass(row.pointsDifference)">{{ formatDiff(row.pointsDifference) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from "vue-i18n";
import type { TournamentRanking } from "../../models/TournamentStanding";

defineProps<{
	ranking: TournamentRanking;
}>();

const { t } = useI18n();

function rankTone(position: number): string {
	if (position === 1) {
		return "rank--gold";
	}
	if (position === 2) {
		return "rank--silver";
	}
	if (position === 3) {
		return "rank--bronze";
	}
	return "";
}

function formatDiff(value: number): string {
	if (value > 0) {
		return `+${value}`;
	}
	return String(value);
}

function diffClass(value: number): string {
	if (value > 0) {
		return "diff--pos";
	}
	if (value < 0) {
		return "diff--neg";
	}
	return "";
}
</script>

<style scoped>
.ranking {
  display: grid;
  gap: var(--app-space-sm);
  min-width: 0;
}

.empty {
  margin: 0;
  padding: var(--app-space-md);
  text-align: center;
  color: var(--app-text-muted);
  font-size: 0.875rem;
}

.table-wrap {
  padding: 0;
  min-width: 0;
  overflow: hidden;
}

table {
  width: 100%;
  table-layout: fixed;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.625rem;
  background: var(--app-surface);
}

.c-rank {
  width: 5%;
}

.c-team {
  width: 27%;
}

.c-num {
  width: 8.5%;
}

thead th {
  padding: 0.2rem 0.05rem;
  font-size: 0.55rem;
  font-weight: 700;
  line-height: 1.1;
  text-transform: uppercase;
  letter-spacing: 0;
  color: var(--app-text-subtle);
  text-align: right;
  white-space: nowrap;
  background: var(--app-surface-muted);
  border-bottom: 1px solid var(--app-border);
}

thead th:first-child {
  border-top-left-radius: calc(var(--app-radius) - 1px);
}

thead th:last-child {
  border-top-right-radius: calc(var(--app-radius) - 1px);
}

tbody td {
  padding: 0.22rem 0.05rem;
  border-bottom: 1px solid var(--app-border);
  text-align: right;
  font-variant-numeric: tabular-nums;
  color: var(--app-text-muted);
  white-space: nowrap;
  vertical-align: middle;
  overflow: hidden;
  text-overflow: ellipsis;
}

tbody tr:last-child td {
  border-bottom: 0;
}

tbody tr:last-child td:first-child {
  border-bottom-left-radius: calc(var(--app-radius) - 1px);
}

tbody tr:last-child td:last-child {
  border-bottom-right-radius: calc(var(--app-radius) - 1px);
}

thead th:first-child,
tbody td:first-child {
  padding-left: var(--app-space-sm);
}

thead th:last-child,
tbody td:last-child {
  padding-right: var(--app-space-sm);
}

.col-rank,
.col-team,
thead .col-team {
  text-align: left;
}

.col-team {
  overflow: hidden;
}

.team-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
  max-width: 100%;
}

.team-name {
  font-weight: 700;
  font-size: 0.6875rem;
  color: var(--app-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.qual-dot {
  flex-shrink: 0;
  width: 0.3rem;
  height: 0.3rem;
  border-radius: 50%;
  background: var(--app-primary);
}

.rank {
  font-size: 0.625rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  color: var(--app-text-muted);
}

.rank--gold {
  color: #7a5c12;
}

.rank--silver {
  color: #5c6672;
}

.rank--bronze {
  color: #7a4d2a;
}

.points {
  font-weight: 800;
  color: var(--app-text);
}

.row--qualified .col-team .team-name {
  color: var(--app-primary-dark);
}

.row--qualified td:first-child {
  box-shadow: inset 2px 0 0 var(--app-primary);
}

.diff--pos {
  font-weight: 700;
  color: var(--app-primary-dark);
}

.diff--neg {
  font-weight: 700;
  color: #9a3412;
}
</style>
