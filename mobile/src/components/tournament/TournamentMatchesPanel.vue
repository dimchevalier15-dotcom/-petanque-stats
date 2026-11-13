<template>
  <section class="matches">
    <p v-if="championshipDays.length === 0 && playoffDays.length === 0" class="empty">{{ t('tournaments.matches.empty') }}</p>

    <template v-if="playoffDays.length">
      <p class="section-label playoff-label">{{ t('tournaments.matches.playoffs') }}</p>
      <nav class="coach-subsegments" role="tablist" :aria-label="t('tournaments.matches.playoffs')">
        <div ref="playoffTrack" class="coach-subsegments-track">
          <button
            v-for="day in playoffDays"
            :key="day.id"
            type="button"
            role="tab"
            class="coach-subsegment"
            :class="{ 'coach-subsegment--active': day.id === selectedPlayoffDayId }"
            :aria-selected="day.id === selectedPlayoffDayId"
            @click="selectPlayoffDay(day.id)"
          >
            {{ playoffTitle(day) }}
          </button>
        </div>
      </nav>

      <div v-if="selectedPlayoffDay" class="day-body">
        <DayMatches
          :day="selectedPlayoffDay"
          :teams="teams"
          :admin="admin"
          :save-score="props.saveScore"
          :save-lane="props.saveLane"
          :save-teams="props.saveTeams"
        />
      </div>
    </template>

    <template v-if="championshipDays.length">
      <p v-if="playoffDays.length" class="section-label championship-label">{{ t('tournaments.matches.championship') }}</p>
      <nav class="coach-subsegments" role="tablist" :aria-label="t('tournaments.matches.championship')">
        <div ref="championshipTrack" class="coach-subsegments-track">
          <button
            v-for="day in championshipDays"
            :key="day.id"
            type="button"
            role="tab"
            class="coach-subsegment"
            :class="{ 'coach-subsegment--active': day.id === selectedChampionshipDayId }"
            :aria-selected="day.id === selectedChampionshipDayId"
            @click="selectChampionshipDay(day.id)"
          >
            {{ t('tournaments.matches.day', { number: day.number }) }}
          </button>
        </div>
      </nav>

      <div v-if="selectedChampionshipDay" class="day-body">
        <DayMatches
          :day="selectedChampionshipDay"
          :teams="teams"
          :admin="admin"
          :save-score="props.saveScore"
          :save-lane="props.saveLane"
          :save-teams="props.saveTeams"
        />
        <div v-if="admin" class="day-actions">
          <Button
            v-if="selectedChampionshipDay.deletable"
            class="delete-day"
            severity="danger"
            outlined
            :label="t('tournaments.actions.deleteDay')"
            :loading="deletingDay"
            @click="removeDay(selectedChampionshipDay.id)"
          />
          <Button
            v-if="selectedChampionshipDay.readyToClose"
            class="close"
            :label="t('tournaments.actions.closeDay')"
            @click="emit('close', selectedChampionshipDay.id)"
          />
        </div>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import type { TournamentDay } from "../../models/TournamentDay";
import type { TournamentTeam } from "../../models/TournamentTeam";
import {
	currentChampionshipDayId,
	currentPlayoffDayId,
} from "../../utils/tournamentDays";
import DayMatches from "./TournamentDayMatches.vue";

const props = defineProps<{
	days: TournamentDay[];
	teams: TournamentTeam[];
	admin: boolean;
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
	deleteDay: (dayId: number) => Promise<void>;
}>();

const emit = defineEmits<{
	close: [dayId: number];
}>();

const deletingDay = ref(false);

const { t } = useI18n();
const championshipTrack = ref<HTMLElement | null>(null);
const playoffTrack = ref<HTMLElement | null>(null);
const selectedChampionshipDayId = ref<number | null>(null);
const selectedPlayoffDayId = ref<number | null>(null);

const championshipDays = computed(() =>
	props.days.filter((day) => day.phase === "championship"),
);
const playoffDays = computed(() =>
	props.days.filter((day) => day.phase === "playoff"),
);
const currentChampionshipId = computed(() =>
	currentChampionshipDayId(props.days),
);
const currentPlayoffId = computed(() => currentPlayoffDayId(props.days));

async function removeDay(dayId: number): Promise<void> {
	deletingDay.value = true;
	try {
		await props.deleteDay(dayId);
	} finally {
		deletingDay.value = false;
	}
}

const selectedChampionshipDay = computed(() =>
	championshipDays.value.find(
		(day) => day.id === selectedChampionshipDayId.value,
	),
);
const selectedPlayoffDay = computed(() =>
	playoffDays.value.find((day) => day.id === selectedPlayoffDayId.value),
);

watch(
	[championshipDays, currentChampionshipId],
	() => {
		const ids = championshipDays.value.map((day) => day.id);
		if (ids.length === 0) {
			selectedChampionshipDayId.value = null;
			return;
		}
		if (
			selectedChampionshipDayId.value === null ||
			!ids.includes(selectedChampionshipDayId.value)
		) {
			const preferred = currentChampionshipId.value;
			selectedChampionshipDayId.value =
				preferred !== null && ids.includes(preferred) ? preferred : ids[0];
		}
	},
	{ immediate: true },
);

watch(
	[playoffDays, currentPlayoffId],
	() => {
		const ids = playoffDays.value.map((day) => day.id);
		if (ids.length === 0) {
			selectedPlayoffDayId.value = null;
			return;
		}
		if (
			selectedPlayoffDayId.value === null ||
			!ids.includes(selectedPlayoffDayId.value)
		) {
			const preferred = currentPlayoffId.value;
			selectedPlayoffDayId.value =
				preferred !== null && ids.includes(preferred) ? preferred : ids[0];
		}
	},
	{ immediate: true },
);

watch(selectedChampionshipDayId, () => {
	void scrollActiveTab(championshipTrack);
});

watch(selectedPlayoffDayId, () => {
	void scrollActiveTab(playoffTrack);
});

onMounted(() => {
	if (playoffDays.value.length > 0) {
		void scrollActiveTab(playoffTrack);
		return;
	}
	void scrollActiveTab(championshipTrack);
});

function selectChampionshipDay(id: number): void {
	selectedChampionshipDayId.value = id;
}

function selectPlayoffDay(id: number): void {
	selectedPlayoffDayId.value = id;
}

async function scrollActiveTab(track: {
	value: HTMLElement | null;
}): Promise<void> {
	await nextTick();
	const active = track.value?.querySelector<HTMLElement>(
		".coach-subsegment--active",
	);
	active?.scrollIntoView({
		inline: "center",
		block: "nearest",
		behavior: "smooth",
	});
}

function playoffTitle(day: TournamentDay): string {
	if (!day.playoffRound) {
		return t("tournaments.matches.playoffs");
	}
	return t(`tournaments.matches.rounds.${day.playoffRound}`);
}
</script>

<style scoped>
.matches {
  display: grid;
  gap: var(--app-space-sm);
  min-width: 0;
}

.coach-subsegments {
  min-width: 0;
}

.coach-subsegments-track {
  display: flex;
  gap: 0.375rem;
  overflow-x: auto;
  padding: 0.125rem 0.125rem 0.25rem;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.coach-subsegments-track::-webkit-scrollbar {
  display: none;
}

.coach-subsegment {
  flex: 0 0 auto;
  min-height: 2rem;
  padding: 0.375rem 0.75rem;
  border: 1px solid var(--app-border);
  border-radius: 999px;
  background: var(--app-surface);
  color: var(--app-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
}

.coach-subsegment--active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
}

.day-body {
  display: grid;
  gap: var(--app-space-sm);
  min-width: 0;
}

.section-label {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--app-text-muted);
}

.championship-label {
  margin-top: var(--app-space-md);
  padding-top: var(--app-space-sm);
  border-top: 1px solid var(--app-border);
}

.empty {
  margin: 0;
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
