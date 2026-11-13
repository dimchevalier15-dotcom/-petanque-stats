<template>
  <section class="ranking">
    <Message v-if="ranking.tieUnresolved" severity="warn">{{ t('tournaments.ranking.tie') }}</Message>
    <p v-if="ranking.rows.length === 0">{{ t('tournaments.ranking.empty') }}</p>
    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ t('tournaments.ranking.position') }}</th>
            <th>{{ t('tournaments.ranking.team') }}</th>
            <th>{{ t('tournaments.ranking.played') }}</th>
            <th>{{ t('tournaments.ranking.wins') }}</th>
            <th>{{ t('tournaments.ranking.draws') }}</th>
            <th>{{ t('tournaments.ranking.losses') }}</th>
            <th>{{ t('tournaments.ranking.points') }}</th>
            <th>{{ t('tournaments.ranking.for') }}</th>
            <th>{{ t('tournaments.ranking.against') }}</th>
            <th>{{ t('tournaments.ranking.diff') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in ranking.rows" :key="row.teamId" :class="{ qualified: row.qualifiedForPlayoffs }">
            <td>{{ row.position }}</td>
            <td class="team">
              {{ row.teamName }}
              <small v-if="row.qualifiedForPlayoffs">{{ t('tournaments.ranking.qualified') }}</small>
            </td>
            <td>{{ row.played }}</td>
            <td>{{ row.wins }}</td>
            <td>{{ row.draws }}</td>
            <td>{{ row.losses }}</td>
            <td class="points">{{ row.points }}</td>
            <td>{{ row.pointsFor }}</td>
            <td>{{ row.pointsAgainst }}</td>
            <td>{{ row.pointsDifference }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup lang="ts">
import Message from "primevue/message";
import { useI18n } from "vue-i18n";
import type { TournamentRanking } from "../../models/TournamentStanding";

defineProps<{
	ranking: TournamentRanking;
}>();

const { t } = useI18n();
</script>

<style scoped>
.ranking {
  display: grid;
  gap: var(--app-space-sm);
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.75rem;
}

th,
td {
  padding: 0.4rem 0.3rem;
  border-bottom: 1px solid var(--app-border);
  text-align: right;
  white-space: nowrap;
}

th:nth-child(2),
td.team {
  text-align: left;
}

.team {
  font-weight: 700;
}

.team small {
  display: block;
  color: var(--app-primary);
  font-weight: 600;
}

.points {
  font-weight: 800;
}

.qualified td:first-child {
  color: var(--app-primary);
  font-weight: 800;
}
</style>
