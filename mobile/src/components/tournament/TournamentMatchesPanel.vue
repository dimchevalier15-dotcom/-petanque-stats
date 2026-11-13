<template>
  <section class="matches">
    <p v-if="championshipDays.length === 0 && playoffDays.length === 0" class="empty">{{ t('tournaments.matches.empty') }}</p>

    <!-- Avant tirage des play-offs : championnat (et play-offs vides) comme aujourd’hui -->
    <template v-if="!splitByPhase">
      <template v-if="playoffDays.length">
        <p class="section-label playoff-label">{{ t('tournaments.matches.playoffs') }}</p>
        <nav class="coach-subsegments" role="tablist" :aria-label="t('tournaments.matches.playoffs')">
          <div ref="playoffTrack" class="coach-subsegments-track">
            <button
              v-for="day in visiblePlayoffDays"
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
        <TournamentMatchesDaySection
          v-if="selectedPlayoffDay"
          :day="selectedPlayoffDay"
          :teams="teams"
          :admin="admin"
          :deleting-day="deletingDay"
          :save-score="saveScore"
          :save-lane="saveLane"
          :save-teams="saveTeams"
          :show-pdf="true"
          @export-pdf="exportDayPdf"
        />
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
        <TournamentMatchesDaySection
          v-if="selectedChampionshipDay"
          :day="selectedChampionshipDay"
          :teams="teams"
          :admin="admin"
          :deleting-day="deletingDay"
          :save-score="saveScore"
          :save-lane="saveLane"
          :save-teams="saveTeams"
          :show-pdf="true"
          :show-admin-day-actions="true"
          @export-pdf="exportDayPdf"
          @close-day="emit('close', $event)"
          @delete-day="removeDay"
        />
      </template>
    </template>

    <!-- Après tirage : un seul bloc avec sous-menu Championnat / Play-offs -->
    <template v-else>
      <nav class="phase-nav" role="tablist" :aria-label="t('tournaments.matches.phaseNav')">
        <div class="coach-subsegments-track">
          <button
            type="button"
            role="tab"
            class="coach-subsegment phase-tab"
            :class="{ 'coach-subsegment--active': phaseView === 'playoff' }"
            :aria-selected="phaseView === 'playoff'"
            @click="phaseView = 'playoff'"
          >
            {{ t('tournaments.matches.playoffs') }}
          </button>
          <button
            type="button"
            role="tab"
            class="coach-subsegment phase-tab"
            :class="{ 'coach-subsegment--active': phaseView === 'championship' }"
            :aria-selected="phaseView === 'championship'"
            @click="phaseView = 'championship'"
          >
            {{ t('tournaments.matches.championship') }}
          </button>
        </div>
      </nav>

      <template v-if="phaseView === 'playoff'">
        <nav class="coach-subsegments" role="tablist" :aria-label="t('tournaments.matches.playoffs')">
          <div ref="playoffTrack" class="coach-subsegments-track">
            <button
              v-for="day in visiblePlayoffDays"
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
        <TournamentMatchesDaySection
          v-if="selectedPlayoffDay"
          :day="selectedPlayoffDay"
          :teams="teams"
          :admin="admin"
          :deleting-day="deletingDay"
          :save-score="saveScore"
          :save-lane="saveLane"
          :save-teams="saveTeams"
          :show-pdf="true"
          @export-pdf="exportDayPdf"
        />
      </template>

      <template v-else>
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
        <TournamentMatchesDaySection
          v-if="selectedChampionshipDay"
          :day="selectedChampionshipDay"
          :teams="teams"
          :admin="admin"
          :deleting-day="deletingDay"
          :save-score="saveScore"
          :save-lane="saveLane"
          :save-teams="saveTeams"
          :show-pdf="true"
          :show-admin-day-actions="true"
          @export-pdf="exportDayPdf"
          @close-day="emit('close', $event)"
          @delete-day="removeDay"
        />
      </template>
    </template>
  </section>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import type { TournamentDay } from "../../models/TournamentDay";
import type { TournamentTeam } from "../../models/TournamentTeam";
import { downloadTournamentDayPdf } from "../../utils/exportTournamentDayPdf";
import { formatTournamentMatchSideName } from "../../utils/tournamentMatchSideName";
import {
	currentChampionshipDayId,
	currentPlayoffDayId,
} from "../../utils/tournamentDays";

import TournamentMatchesDaySection from "./TournamentMatchesDaySection.vue";

type PhaseView = "championship" | "playoff";

const props = defineProps<{
	tournamentName: string;
	days: TournamentDay[];
	teams: TournamentTeam[];
	playoffsGenerated: boolean;
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
const phaseView = ref<PhaseView>("playoff");

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
const visiblePlayoffDays = computed(() =>
	playoffDays.value.filter((day) => day.matches.length > 0),
);
const splitByPhase = computed(
	() => props.playoffsGenerated && visiblePlayoffDays.value.length > 0,
);

const currentChampionshipId = computed(() =>
	currentChampionshipDayId(props.days),
);
const currentPlayoffId = computed(() =>
	currentPlayoffDayId(props.days),
);

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
	visiblePlayoffDays.value.find(
		(day) => day.id === selectedPlayoffDayId.value,
	),
);

watch(
	() => props.playoffsGenerated,
	(generated) => {
		if (generated) {
			phaseView.value = "playoff";
		}
	},
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
	[visiblePlayoffDays, currentPlayoffId],
	() => {
		const ids = visiblePlayoffDays.value.map((day) => day.id);
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
				preferred !== null && ids.includes(preferred)
					? preferred
					: ids[ids.length - 1];
		}
	},
	{ immediate: true },
);

watch(phaseView, () => {
	void scrollActiveTab(
		phaseView.value === "playoff" ? playoffTrack : championshipTrack,
	);
});

watch(selectedChampionshipDayId, () => {
	if (!splitByPhase.value || phaseView.value === "championship") {
		void scrollActiveTab(championshipTrack);
	}
});

watch(selectedPlayoffDayId, () => {
	if (!splitByPhase.value || phaseView.value === "playoff") {
		void scrollActiveTab(playoffTrack);
	}
});

onMounted(() => {
	if (splitByPhase.value) {
		void scrollActiveTab(playoffTrack);
		return;
	}
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

function dayPdfTitle(day: TournamentDay): string {
	if (day.phase === "playoff") {
		return playoffTitle(day);
	}
	return t("tournaments.matches.day", { number: day.number });
}

async function exportDayPdf(day: TournamentDay): Promise<void> {
	await downloadTournamentDayPdf(
		`${props.tournamentName}-${dayPdfTitle(day)}`,
		props.tournamentName,
		dayPdfTitle(day),
		{
			lane: t("tournaments.pdf.lane"),
			team1: t("tournaments.pdf.team1"),
			team2: t("tournaments.pdf.team2"),
			score: t("tournaments.pdf.score"),
		},
		day.matches.map((match) => ({
			lane: match.laneNumber,
			team1: formatTournamentMatchSideName(match.team1, t),
			team2: formatTournamentMatchSideName(match.team2, t),
		})),
	);
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

.phase-nav .coach-subsegments-track {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.375rem;
}

.phase-tab {
  width: 100%;
  justify-content: center;
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
</style>
