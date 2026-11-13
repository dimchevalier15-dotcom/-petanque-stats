<template>
  <section class="admin">
    <section class="block app-card">
      <h3>{{ t('tournaments.admin.schedule') }}</h3>
      <p class="hint">{{ t('tournaments.admin.scheduleHint') }}</p>
      <p class="label">{{ t('tournaments.admin.present') }}</p>
      <label v-for="team in detail.teams" :key="team.id" class="check">
        <input v-model="present[team.id]" type="checkbox" />
        <span>{{ team.name }}</span>
      </label>
      <div class="inline">
        <InputText v-model="fakeName" :placeholder="t('tournaments.fields.fakeTeam')" fluid />
        <Button type="button" outlined :label="t('tournaments.actions.add')" @click="addFake" />
      </div>
      <div v-for="(fake, index) in fakes" :key="`${fake}-${index}`" class="chip">
        <span>{{ fake }}</span>
        <Button type="button" icon="pi pi-times" text rounded @click="fakes.splice(index, 1)" />
      </div>
      <p class="label">{{ t('tournaments.admin.lanes') }}</p>
      <div class="lanes">
        <label v-for="lane in laneChoices" :key="lane" class="check">
          <input type="checkbox" :checked="!excluded.includes(lane)" @change="toggleLane(lane)" />
          <span>{{ lane }}</span>
        </label>
      </div>
      <p class="hint">{{ usable.join(', ') }}</p>
      <Button :label="t('tournaments.actions.generate')" :loading="busy" @click="generate" />
    </section>

    <section class="block app-card">
      <h3>{{ t('tournaments.admin.teams') }}</h3>
      <p class="hint">{{ t('tournaments.admin.playerHint') }}</p>
      <label class="app-field">
        <span>{{ t('tournaments.fields.teamName') }}</span>
        <InputText v-model="teamName" fluid />
      </label>
      <PlayerSearchSelect v-model="playerToAdd" :placeholder="t('tournaments.admin.players')" @update:model-value="pushPlayer" />
      <div v-for="player in selectedPlayers" :key="player.id" class="chip">
        <span>{{ player.nickname }}</span>
        <Button type="button" icon="pi pi-times" text rounded @click="selectedPlayers = selectedPlayers.filter((item) => item.id !== player.id)" />
      </div>
      <Button :label="t('tournaments.actions.add')" outlined :disabled="!canCreateTeam" @click="createTeam" />
      <article v-for="team in detail.teams" :key="team.id" class="team">
        <header>
          <InputText v-model="teamNames[team.id]" fluid />
          <Button type="button" :label="t('tournaments.actions.save')" text @click="renameTeam(team.id)" />
          <Button type="button" :label="t('tournaments.actions.remove')" text @click="removeTeam(team.id)" />
        </header>
        <p v-for="player in team.players" :key="player.id" class="player-line">
          <span>{{ player.firstName }} {{ player.lastName }}</span>
          <Button type="button" icon="pi pi-times" text rounded @click="removePlayer(team.id, player.id)" />
        </p>
        <PlayerSearchSelect :model-value="null" :placeholder="t('tournaments.actions.add')" @update:model-value="(player) => player && addPlayer(team.id, player.id)" />
      </article>
      <p v-if="detail.teams.length === 0" class="hint">{{ t('tournaments.admin.noTeams') }}</p>
    </section>

    <section class="block app-card">
      <h3>{{ t('tournaments.admin.members') }}</h3>
      <div class="inline">
        <InputText v-model="memberQuery" :placeholder="t('tournaments.fields.adminSearch')" fluid />
        <Button type="button" outlined :label="t('tournaments.actions.search')" @click="searchMembers" />
      </div>
      <button v-for="user in memberResults" :key="user.id" type="button" class="pick" @click="addMember(user.id, false)">
        {{ user.email }}
      </button>
      <article v-for="member in detail.members ?? []" :key="member.userId" class="member">
        <span>{{ member.email }}</span>
        <label v-if="isMaster" class="check">
          <input type="checkbox" :checked="member.admin" @change="toggleAdmin(member.userId, !member.admin)" />
          <span>{{ t('tournaments.admin.makeAdmin') }}</span>
        </label>
        <span v-else-if="member.admin">{{ t('tournaments.admin.makeAdmin') }}</span>
        <Button type="button" :label="t('tournaments.actions.remove')" text @click="removeMember(member.userId)" />
      </article>
    </section>

    <section class="block app-card">
      <h3>{{ t('tournaments.admin.playoffs') }}</h3>
      <p class="hint">{{ t('tournaments.admin.playoffsHint') }}</p>
      <p class="hint">{{ playoffMessage }}</p>
      <Button
        v-if="!detail.playoffsGenerated"
        :label="t('tournaments.actions.generatePlayoffs')"
        :disabled="!playoffsReady"
        @click="generatePlayoffs"
      />
    </section>

    <form class="block app-card" @submit.prevent="saveSettings">
      <h3>{{ t('tournaments.admin.settings') }}</h3>
      <label class="app-field">
        <span>{{ t('tournaments.fields.name') }}</span>
        <InputText v-model="settings.name" fluid />
      </label>
      <label class="app-field">
        <span>{{ t('tournaments.fields.description') }}</span>
        <InputText v-model="settings.description" fluid />
      </label>
      <div class="pair">
        <label class="app-field">
          <span>{{ t('tournaments.fields.minPlayers') }}</span>
          <InputText v-model.number="settings.minPlayersPerTeam" type="number" min="1" fluid />
        </label>
        <label class="app-field">
          <span>{{ t('tournaments.fields.maxPlayers') }}</span>
          <InputText v-model.number="settings.maxPlayersPerTeam" type="number" min="1" fluid />
        </label>
      </div>
      <div class="pair">
        <label class="app-field">
          <span>{{ t('tournaments.fields.pointsWin') }}</span>
          <InputText v-model.number="settings.pointsForWin" type="number" min="0" fluid />
        </label>
        <label class="app-field">
          <span>{{ t('tournaments.fields.pointsDraw') }}</span>
          <InputText v-model.number="settings.pointsForDraw" type="number" min="0" fluid />
        </label>
      </div>
      <label class="check">
        <input v-model="settings.drawsAllowed" type="checkbox" />
        <span>{{ t('tournaments.fields.draws') }}</span>
      </label>
      <label class="app-field">
        <span>{{ t('tournaments.fields.lanes') }}</span>
        <InputText v-model.number="settings.numberOfLanes" type="number" min="1" fluid />
      </label>
      <label class="app-field">
        <span>{{ t('tournaments.fields.excludedLanes') }}</span>
        <InputText v-model="settings.excludedText" fluid />
      </label>
      <Button type="submit" :label="t('tournaments.actions.save')" />
    </form>
  </section>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import { computed, reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useIsAdmin } from "../../composables/useIsAdmin";
import type { Player } from "../../models/Player";
import type { TournamentDetail } from "../../models/TournamentDetail";
import { tournamentsService } from "../../services/tournaments";
import { tournamentErrorMessage } from "../../utils/tournamentError";
import {
	parseLaneNumbers,
	usableLaneNumbers,
} from "../../utils/tournamentLanes";
import PlayerSearchSelect from "../players/PlayerSearchSelect.vue";

const props = defineProps<{ detail: TournamentDetail }>();
const emit = defineEmits<{
	updated: [detail: TournamentDetail];
	failed: [message: string];
}>();

const { t, te } = useI18n();
const isMaster = useIsAdmin();
const busy = ref(false);
const present = reactive<Record<number, boolean>>({});
const teamNames = reactive<Record<number, string>>({});
const fakeName = ref("");
const fakes = ref<string[]>([]);
const excluded = ref<number[]>([]);
const teamName = ref("");
const selectedPlayers = ref<Player[]>([]);
const playerToAdd = ref<Player | null>(null);
const memberQuery = ref("");
const memberResults = ref<{ id: number; email: string }[]>([]);
const settings = reactive({
	name: "",
	description: "",
	minPlayersPerTeam: 2,
	maxPlayersPerTeam: 3,
	pointsForWin: 1,
	pointsForDraw: 0,
	drawsAllowed: false,
	numberOfLanes: 8,
	excludedText: "",
	startsAt: "",
	endsAt: "",
});

watch(
	() => props.detail,
	(detail) => {
		for (const team of detail.teams) {
			if (present[team.id] === undefined) {
				present[team.id] = true;
			}
			teamNames[team.id] = team.name;
		}
		excluded.value = [...detail.excludedLaneNumbers];
		settings.name = detail.name;
		settings.description = detail.description ?? "";
		settings.minPlayersPerTeam = detail.minPlayersPerTeam;
		settings.maxPlayersPerTeam = detail.maxPlayersPerTeam;
		settings.pointsForWin = detail.pointsForWin;
		settings.pointsForDraw = detail.pointsForDraw;
		settings.drawsAllowed = detail.drawsAllowed;
		settings.numberOfLanes = detail.numberOfLanes;
		settings.excludedText = detail.excludedLaneNumbers.join(", ");
		settings.startsAt = detail.startsAt ?? "";
		settings.endsAt = detail.endsAt ?? "";
	},
	{ immediate: true },
);

const laneChoices = computed(() => {
	const upper = Math.max(
		props.detail.numberOfLanes + excluded.value.length + 4,
		...excluded.value,
		1,
	);
	return Array.from({ length: upper }, (_, index) => index + 1);
});
const usable = computed(() =>
	usableLaneNumbers(props.detail.numberOfLanes, excluded.value),
);
const canCreateTeam = computed(() => {
	const count = selectedPlayers.value.length;
	return (
		teamName.value.trim() !== "" &&
		count >= props.detail.minPlayersPerTeam &&
		count <= props.detail.maxPlayersPerTeam
	);
});
const championshipDays = computed(() =>
	props.detail.days.filter((day) => day.phase === "championship"),
);
const playoffsReady = computed(
	() =>
		championshipDays.value.length > 0 &&
		championshipDays.value.every((day) => day.status === "closed"),
);
const playoffMessage = computed(() => {
	if (props.detail.playoffsGenerated) {
		return t("tournaments.admin.playoffsDone");
	}
	return playoffsReady.value
		? t("tournaments.admin.playoffsReady")
		: t("tournaments.admin.playoffsWaiting");
});

function fail(error: unknown): void {
	emit("failed", tournamentErrorMessage(error, t, te));
}

function addFake(): void {
	const name = fakeName.value.trim();
	if (name !== "") {
		fakes.value.push(name);
		fakeName.value = "";
	}
}

function toggleLane(lane: number): void {
	excluded.value = excluded.value.includes(lane)
		? excluded.value.filter((item) => item !== lane)
		: [...excluded.value, lane].sort((left, right) => left - right);
}

async function generate(): Promise<void> {
	busy.value = true;
	try {
		emit(
			"updated",
			await tournamentsService.generateDay(props.detail.id, {
				absentTeamIds: props.detail.teams
					.filter((team) => present[team.id] === false)
					.map((team) => team.id),
				fakeTeamNames: fakes.value,
				excludedLaneNumbers: excluded.value,
			}),
		);
		fakes.value = [];
	} catch (error) {
		fail(error);
	} finally {
		busy.value = false;
	}
}

function pushPlayer(player: Player | null): void {
	if (player && !selectedPlayers.value.some((item) => item.id === player.id)) {
		selectedPlayers.value.push(player);
	}
	playerToAdd.value = null;
}

async function createTeam(): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.createTeam(
				props.detail.id,
				teamName.value.trim(),
				selectedPlayers.value.map((player) => player.id),
			),
		);
		teamName.value = "";
		selectedPlayers.value = [];
	} catch (error) {
		fail(error);
	}
}

async function addPlayer(teamId: number, playerId: number): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.addPlayer(props.detail.id, teamId, playerId),
		);
	} catch (error) {
		fail(error);
	}
}

async function removePlayer(teamId: number, playerId: number): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.removePlayer(props.detail.id, teamId, playerId),
		);
	} catch (error) {
		fail(error);
	}
}

async function renameTeam(teamId: number): Promise<void> {
	const name = teamNames[teamId]?.trim() ?? "";
	if (name === "") {
		return;
	}
	try {
		emit(
			"updated",
			await tournamentsService.renameTeam(props.detail.id, teamId, name),
		);
	} catch (error) {
		fail(error);
	}
}

async function removeTeam(teamId: number): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.deleteTeam(props.detail.id, teamId),
		);
	} catch (error) {
		fail(error);
	}
}

async function searchMembers(): Promise<void> {
	try {
		memberResults.value = await tournamentsService.searchUsers(
			memberQuery.value,
		);
	} catch (error) {
		fail(error);
	}
}

async function addMember(userId: number, admin: boolean): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.addMember(props.detail.id, userId, admin),
		);
		memberResults.value = [];
		memberQuery.value = "";
	} catch (error) {
		fail(error);
	}
}

async function toggleAdmin(userId: number, admin: boolean): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.updateMember(props.detail.id, userId, admin),
		);
	} catch (error) {
		fail(error);
	}
}

async function removeMember(userId: number): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.removeMember(props.detail.id, userId),
		);
	} catch (error) {
		fail(error);
	}
}

async function generatePlayoffs(): Promise<void> {
	try {
		emit("updated", await tournamentsService.generatePlayoffs(props.detail.id));
	} catch (error) {
		fail(error);
	}
}

async function saveSettings(): Promise<void> {
	try {
		emit(
			"updated",
			await tournamentsService.update(props.detail.id, {
				name: settings.name.trim(),
				description: settings.description.trim() || null,
				minPlayersPerTeam: settings.minPlayersPerTeam,
				maxPlayersPerTeam: settings.maxPlayersPerTeam,
				pointsForWin: settings.pointsForWin,
				pointsForDraw: settings.pointsForDraw,
				drawsAllowed: settings.drawsAllowed,
				numberOfLanes: settings.numberOfLanes,
				excludedLaneNumbers: parseLaneNumbers(settings.excludedText),
				startsAt: settings.startsAt || null,
				endsAt: settings.endsAt || null,
			}),
		);
	} catch (error) {
		fail(error);
	}
}
</script>

<style scoped>
.admin,
.block {
  display: grid;
  gap: var(--app-space-sm);
}

.block {
  padding: var(--app-space-md);
}

h3,
.hint,
.label {
  margin: 0;
}

.hint,
.label {
  color: var(--app-text-muted);
  font-size: 0.8125rem;
}

.inline,
.chip,
.check,
.member,
.player-line,
.team header {
  display: flex;
  gap: var(--app-space-sm);
  align-items: center;
  justify-content: space-between;
}

.pair {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.lanes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem 0.8rem;
}

.pick {
  min-height: var(--app-touch-min);
  text-align: left;
}

.team,
.member {
  padding-top: var(--app-space-sm);
  border-top: 1px solid var(--app-border);
}
</style>
