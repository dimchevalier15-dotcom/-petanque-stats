<template>
  <div class="day-body">
    <DayMatches
      :day="day"
      :teams="teams"
      :admin="admin"
      :save-score="saveScore"
      :save-lane="saveLane"
      :save-teams="saveTeams"
    />
    <div v-if="showPdf || showAdminDayActions" class="day-actions">
      <Button
        v-if="showPdf"
        type="button"
        outlined
        icon="pi pi-file-pdf"
        :label="t('tournaments.actions.exportDayPdf')"
        @click="emit('exportPdf', day)"
      />
      <Button
        v-if="showAdminDayActions && admin && day.deletable"
        class="delete-day"
        severity="danger"
        outlined
        :label="t('tournaments.actions.deleteDay')"
        :loading="deletingDay"
        @click="emit('deleteDay', day.id)"
      />
      <Button
        v-if="showAdminDayActions && admin && day.readyToClose"
        class="close"
        :label="t('tournaments.actions.closeDay')"
        @click="emit('closeDay', day.id)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import { useI18n } from "vue-i18n";
import type { TournamentDay } from "../../models/TournamentDay";
import type { TournamentTeam } from "../../models/TournamentTeam";
import DayMatches from "./TournamentDayMatches.vue";

defineProps<{
	day: TournamentDay;
	teams: TournamentTeam[];
	admin: boolean;
	deletingDay: boolean;
	showPdf?: boolean;
	showAdminDayActions?: boolean;
	saveScore: (
		payload:
			| { matchId: number; score1: number; score2: number }
			| { matchId: number; clear: true },
	) => Promise<boolean>;
	saveLane: (payload: {
		matchId: number;
		laneNumber: number;
	}) => Promise<boolean>;
	saveTeams: (payload: {
		matchId: number;
		team1Id: number;
		team2Id: number;
	}) => Promise<boolean>;
}>();

const emit = defineEmits<{
	exportPdf: [day: TournamentDay];
	closeDay: [dayId: number];
	deleteDay: [dayId: number];
}>();

const { t } = useI18n();
</script>

<style scoped>
.day-body {
  display: grid;
  gap: var(--app-space-sm);
  min-width: 0;
}

.day-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.delete-day,
.close {
  justify-self: start;
}
</style>
