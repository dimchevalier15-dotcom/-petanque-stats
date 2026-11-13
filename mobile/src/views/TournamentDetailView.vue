<template>
  <AppPage compact>
    <PageHeader :title="detail?.name ?? t('tournaments.loading')" :subtitle="detail ? t(`tournaments.status.${detail.status}`) : ''" :back-to="{ name: 'tournaments' }" />

    <p v-if="loading" class="app-section-subtitle">{{ t('tournaments.loading') }}</p>
    <Message v-if="error" severity="error">{{ error }}</Message>

    <template v-if="detail">
      <div class="type-picker" :style="{ '--tab-count': String(tabs.length) }">
        <SelectButton v-model="tab" :options="tabs" option-label="label" option-value="value" />
      </div>

      <TournamentMatchesPanel
        v-if="tab === 'matches'"
        :key="revision"
        :days="detail.days"
        :teams="detail.teams"
        :admin="detail.viewerIsAdmin"
        :save-score="saveScore"
        :save-lane="saveLane"
        :save-teams="saveTeams"
        @close="closeDay"
      />
      <TournamentRankingPanel v-else-if="tab === 'ranking'" :ranking="detail.ranking" />
      <TournamentAdminPanel
        v-else
        :detail="detail"
        @updated="apply"
        @failed="error = $event"
      />
    </template>
  </AppPage>
</template>

<script setup lang="ts">
import Message from "primevue/message";
import SelectButton from "primevue/selectbutton";
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import AppPage from "../components/layout/AppPage.vue";
import PageHeader from "../components/layout/PageHeader.vue";
import TournamentAdminPanel from "../components/tournament/TournamentAdminPanel.vue";
import TournamentMatchesPanel from "../components/tournament/TournamentMatchesPanel.vue";
import TournamentRankingPanel from "../components/tournament/TournamentRankingPanel.vue";
import type { TournamentDetail } from "../models/TournamentDetail";
import { tournamentsService } from "../services/tournaments";
import { tournamentErrorMessage } from "../utils/tournamentError";

const { t, te } = useI18n();
const route = useRoute();
const detail = ref<TournamentDetail | null>(null);
const loading = ref(true);
const error = ref("");
const tab = ref("matches");
const revision = ref(0);

const tabs = computed(() => {
	const items = [
		{ label: t("tournaments.tabs.matches"), value: "matches" },
		{ label: t("tournaments.tabs.ranking"), value: "ranking" },
	];
	if (detail.value?.viewerIsAdmin) {
		items.push({ label: t("tournaments.tabs.admin"), value: "admin" });
	}
	return items;
});

onMounted(load);

async function load(): Promise<void> {
	loading.value = true;
	error.value = "";
	try {
		detail.value = await tournamentsService.get(Number(route.params.id));
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
	} finally {
		loading.value = false;
	}
}

function apply(next: TournamentDetail, options?: { remount?: boolean }): void {
	detail.value = next;
	if (options?.remount !== false) {
		revision.value += 1;
	}
	error.value = "";
}

async function saveScore(payload: {
	matchId: number;
	score1: number;
	score2: number;
}): Promise<boolean> {
	if (!detail.value) {
		return false;
	}
	try {
		apply(
			await tournamentsService.updateScore(detail.value.id, payload.matchId, {
				score1: payload.score1,
				score2: payload.score2,
			}),
			{ remount: false },
		);
		return true;
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
		return false;
	}
}

async function saveTeams(payload: {
	matchId: number;
	team1Id: number;
	team2Id: number;
}): Promise<boolean> {
	if (!detail.value) {
		return false;
	}
	try {
		apply(
			await tournamentsService.updateTeams(
				detail.value.id,
				payload.matchId,
				payload.team1Id,
				payload.team2Id,
			),
			{ remount: false },
		);
		return true;
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
		return false;
	}
}

async function saveLane(payload: {
	matchId: number;
	laneNumber: number;
}): Promise<boolean> {
	if (!detail.value) {
		return false;
	}
	try {
		apply(
			await tournamentsService.updateLane(
				detail.value.id,
				payload.matchId,
				payload.laneNumber,
			),
			{ remount: false },
		);
		return true;
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
		return false;
	}
}

async function closeDay(dayId: number): Promise<void> {
	if (!detail.value) {
		return;
	}
	try {
		apply(await tournamentsService.closeDay(detail.value.id, dayId));
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
	}
}
</script>

<style scoped>
.type-picker {
  width: 100%;
}

.type-picker :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: repeat(var(--tab-count), minmax(0, 1fr));
  width: 100%;
}

.type-picker :deep(.p-togglebutton) {
  justify-content: center;
  min-height: var(--app-touch-min);
  padding: 0.5rem 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: normal;
}
</style>
