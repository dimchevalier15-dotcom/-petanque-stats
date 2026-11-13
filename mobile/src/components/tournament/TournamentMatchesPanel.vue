<template>
  <section class="matches">
    <p v-if="championshipDays.length === 0 && playoffDays.length === 0" class="empty">{{ t('tournaments.matches.empty') }}</p>

    <div v-if="championshipDays.length">
      <h3 class="group-title">{{ t('tournaments.matches.championship') }}</h3>
      <details
        v-for="day in championshipDays"
        :key="day.id"
        class="day app-card"
        :open="day.id === currentDayId"
      >
        <summary>
          <span>{{ t('tournaments.matches.day', { number: day.number }) }}</span>
          <span class="day-status">{{ dayStatus(day) }}</span>
        </summary>
        <DayMatches :day="day" :teams="teams" :admin="admin" @score="emit('score', $event)" @lane="emit('lane', $event)" @teams="emit('teams', $event)" />
        <Button
          v-if="admin && day.readyToClose"
          class="close"
          :label="t('tournaments.actions.closeDay')"
          @click="emit('close', day.id)"
        />
      </details>
    </div>

    <div v-if="playoffDays.length">
      <h3 class="group-title">{{ t('tournaments.matches.playoffs') }}</h3>
      <details v-for="day in playoffDays" :key="day.id" class="day app-card" open>
        <summary>
          <span>{{ playoffTitle(day) }}</span>
          <span class="day-status">{{ dayStatus(day) }}</span>
        </summary>
        <DayMatches :day="day" :teams="teams" :admin="admin" @score="emit('score', $event)" @lane="emit('lane', $event)" @teams="emit('teams', $event)" />
      </details>
    </div>
  </section>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import type { TournamentDay } from "../../models/TournamentDay";
import type { TournamentTeam } from "../../models/TournamentTeam";
import { currentChampionshipDayId } from "../../utils/tournamentDays";
import DayMatches from "./TournamentDayMatches.vue";

const props = defineProps<{
	days: TournamentDay[];
	teams: TournamentTeam[];
	admin: boolean;
}>();

const emit = defineEmits<{
	score: [
		payload:
			| { matchId: number; score1: number; score2: number }
			| { matchId: number; clear: true },
	];
	lane: [payload: { matchId: number; laneNumber: number }];
	teams: [payload: { matchId: number; team1Id: number; team2Id: number }];
	close: [dayId: number];
}>();

const { t } = useI18n();
const championshipDays = computed(() =>
	props.days.filter((day) => day.phase === "championship"),
);
const playoffDays = computed(() =>
	props.days.filter((day) => day.phase === "playoff"),
);
const currentDayId = computed(() => currentChampionshipDayId(props.days));

function dayStatus(day: TournamentDay): string {
	if (day.status === "closed") {
		return t("tournaments.matches.closed");
	}
	return t(`tournaments.matches.${day.status}`);
}

function playoffTitle(day: TournamentDay): string {
	if (!day.playoffRound) {
		return t("tournaments.matches.playoffs");
	}
	return t(`tournaments.matches.rounds.${day.playoffRound}`);
}
</script>

<style scoped>
.matches,
.day {
  display: grid;
  gap: var(--app-space-sm);
}

.group-title {
  margin: 0;
  font-size: 0.95rem;
}

.day {
  padding: var(--app-space-sm) var(--app-space-md) var(--app-space-md);
}

summary {
  display: flex;
  justify-content: space-between;
  gap: var(--app-space-sm);
  min-height: var(--app-touch-min);
  align-items: center;
  font-weight: 700;
  cursor: pointer;
}

.day-status {
  color: var(--app-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
}

.empty {
  margin: 0;
}

.close {
  justify-self: start;
}
</style>
