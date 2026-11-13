<template>
  <section class="admin">
    <nav class="coach-subsegments" role="tablist" :aria-label="t('tournaments.tabs.admin')">
      <div ref="sectionTrack" class="coach-subsegments-track">
        <button
          v-for="item in sections"
          :key="item.id"
          type="button"
          role="tab"
          class="coach-subsegment"
          :class="{ 'coach-subsegment--active': activeSection === item.id }"
          :aria-selected="activeSection === item.id"
          @click="selectSection(item.id)"
        >
          {{ item.label }}
        </button>
      </div>
    </nav>

    <section v-show="activeSection === 'schedule'" class="block app-card">
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

    <section v-show="activeSection === 'teams'" class="teams-section">
      <header class="teams-toolbar">
        <p class="hint">{{ t('tournaments.admin.playerHint') }}</p>
        <Button
          type="button"
          class="teams-add"
          icon="pi pi-plus"
          rounded
          :aria-label="t('tournaments.admin.addTeam')"
          :aria-expanded="showCreateTeam"
          @click="showCreateTeam = !showCreateTeam"
        />
      </header>

      <div v-if="showCreateTeam" class="team-create app-card">
        <p class="team-create-title">{{ t('tournaments.admin.addTeam') }}</p>
        <div class="team-create-body">
          <label class="app-field">
            <span>{{ t('tournaments.fields.teamName') }}</span>
            <InputText v-model="teamName" fluid />
          </label>
          <PlayerSearchSelect
            v-model="playerToAdd"
            :placeholder="t('tournaments.admin.players')"
            @update:model-value="pushPlayer"
          />
          <div v-for="player in selectedPlayers" :key="player.id" class="chip">
            <span>{{ player.nickname }}</span>
            <Button
              type="button"
              icon="pi pi-times"
              text
              rounded
              @click="selectedPlayers = selectedPlayers.filter((item) => item.id !== player.id)"
            />
          </div>
          <p class="hint">{{ teamSizeHint }}</p>
          <div class="team-create-actions">
            <Button type="button" text :label="t('tournaments.actions.cancel')" @click="cancelCreateTeam" />
            <Button :label="t('tournaments.actions.save')" :disabled="!canCreateTeam" @click="createTeam" />
          </div>
        </div>
      </div>

      <p v-if="detail.teams.length === 0 && !showCreateTeam" class="empty-teams app-card">{{ t('tournaments.admin.noTeams') }}</p>

      <details v-for="team in detail.teams" :key="team.id" class="team-fold app-card">
        <summary class="team-summary">
          <span class="team-summary-name">{{ team.name }}</span>
          <span class="team-summary-meta">{{ t('tournaments.admin.playerCount', { count: team.players.length }) }}</span>
          <i class="pi pi-chevron-down team-chevron" aria-hidden="true" />
        </summary>
        <div class="team-body">
          <label class="app-field rename-field">
            <span>{{ t('tournaments.fields.teamName') }}</span>
            <div class="rename-row">
              <InputText v-model="teamNames[team.id]" fluid />
              <Button type="button" icon="pi pi-check" text rounded :aria-label="t('tournaments.actions.save')" @click="renameTeam(team.id)" />
            </div>
          </label>
          <p v-if="team.players.length === 0" class="hint">{{ t('tournaments.admin.noPlayersInTeam') }}</p>
          <ul v-else class="player-list">
            <li v-for="player in team.players" :key="player.id" class="player-line">
              <span class="player-name">{{ player.firstName }} {{ player.lastName }}</span>
              <span v-if="player.nickname" class="player-nick">{{ player.nickname }}</span>
              <Button
                type="button"
                icon="pi pi-times"
                text
                rounded
                :aria-label="t('tournaments.actions.remove')"
                @click="removePlayer(team.id, player.id)"
              />
            </li>
          </ul>
          <div class="add-player">
            <span class="label">{{ t('tournaments.admin.addPlayer') }}</span>
            <PlayerSearchSelect
              :model-value="null"
              :placeholder="t('tournaments.admin.players')"
              @update:model-value="(player) => player && addPlayer(team.id, player.id)"
            />
          </div>
          <Button
            type="button"
            class="remove-team"
            text
            severity="danger"
            :label="t('tournaments.admin.removeTeam')"
            @click="removeTeam(team.id)"
          />
        </div>
      </details>
    </section>

    <section v-show="activeSection === 'members'" class="block app-card">
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

    <section v-show="activeSection === 'playoffs'" class="block app-card">
      <p class="hint">{{ t('tournaments.admin.playoffsHint') }}</p>
      <p class="hint">{{ playoffMessage }}</p>
      <Button
        v-if="!detail.playoffsGenerated"
        :label="t('tournaments.actions.generatePlayoffs')"
        :disabled="!playoffsReady"
        @click="generatePlayoffs"
      />
    </section>

    <form v-show="activeSection === 'settings'" class="block app-card" @submit.prevent="saveSettings">
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
import { computed, nextTick, reactive, ref, watch } from "vue";
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

type AdminSection = "schedule" | "teams" | "members" | "playoffs" | "settings";

const props = defineProps<{ detail: TournamentDetail }>();
const emit = defineEmits<{
	updated: [detail: TournamentDetail];
	failed: [message: string];
}>();

const { t, te } = useI18n();
const isMaster = useIsAdmin();
const sectionTrack = ref<HTMLElement | null>(null);
const activeSection = ref<AdminSection>("schedule");
const busy = ref(false);
const showCreateTeam = ref(false);
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

const sections = computed(() => [
	{ id: "schedule" as const, label: t("tournaments.admin.schedule") },
	{ id: "teams" as const, label: t("tournaments.admin.teams") },
	{ id: "members" as const, label: t("tournaments.admin.members") },
	{ id: "playoffs" as const, label: t("tournaments.admin.playoffs") },
	{ id: "settings" as const, label: t("tournaments.admin.settings") },
]);

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

watch(activeSection, () => {
	void scrollActiveSection();
});

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
const teamSizeHint = computed(() =>
	t("tournaments.admin.teamSizeHint", {
		min: props.detail.minPlayersPerTeam,
		max: props.detail.maxPlayersPerTeam,
	}),
);
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

function selectSection(id: AdminSection): void {
	activeSection.value = id;
}

async function scrollActiveSection(): Promise<void> {
	await nextTick();
	const active = sectionTrack.value?.querySelector<HTMLElement>(
		".coach-subsegment--active",
	);
	active?.scrollIntoView({
		inline: "center",
		block: "nearest",
		behavior: "smooth",
	});
}

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

function cancelCreateTeam(): void {
	showCreateTeam.value = false;
	teamName.value = "";
	selectedPlayers.value = [];
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
		cancelCreateTeam();
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
.admin {
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

.block {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-md);
}

.hint,
.label {
  margin: 0;
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

.teams-section {
  display: grid;
  gap: var(--app-space-sm);
  min-width: 0;
}

.teams-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--app-space-sm);
}

.teams-toolbar .hint {
  flex: 1;
  margin: 0;
}

.teams-toolbar :deep(.teams-add.p-button) {
  flex-shrink: 0;
  width: 2.75rem !important;
  height: 2.75rem !important;
  min-width: 2.75rem !important;
  min-height: 2.75rem !important;
  padding: 0 !important;
  border-radius: 50% !important;
  background: var(--app-primary) !important;
  border-color: var(--app-primary) !important;
  color: #fff !important;
}

.team-create,
.team-fold,
.empty-teams {
  padding: 0;
  overflow: hidden;
}

.team-create-title {
  margin: 0;
  padding: var(--app-space-sm) var(--app-space-md) 0;
  font-weight: 700;
}

.team-create-body,
.team-body {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-sm) var(--app-space-md) var(--app-space-md);
}

.team-create-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--app-space-sm);
}

.team-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-sm);
  min-height: var(--app-touch-min);
  padding: var(--app-space-sm) var(--app-space-md);
  font-weight: 700;
  cursor: pointer;
  list-style: none;
}

.team-summary::-webkit-details-marker {
  display: none;
}

.team-summary-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.team-summary-meta {
  flex-shrink: 0;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.team-chevron {
  flex-shrink: 0;
  font-size: 0.75rem;
  color: var(--app-text-muted);
  transition: transform 0.15s ease;
}

.team-fold[open] .team-chevron {
  transform: rotate(180deg);
}

.team-fold[open] .team-summary {
  border-bottom: 1px solid var(--app-border);
}

.rename-row {
  display: flex;
  align-items: center;
  gap: var(--app-space-xs);
}

.player-list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0;
}

.player-line {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.15rem var(--app-space-sm);
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--app-border);
}

.player-line:last-child {
  border-bottom: none;
}

.player-name {
  grid-column: 1;
  font-weight: 600;
  font-size: 0.875rem;
}

.player-nick {
  grid-column: 1;
  font-size: 0.75rem;
  color: var(--app-text-muted);
}

.player-line :deep(.p-button) {
  grid-column: 2;
  grid-row: 1 / span 2;
}

.add-player {
  display: grid;
  gap: var(--app-space-xs);
  padding-top: var(--app-space-sm);
  border-top: 1px solid var(--app-border);
}

.remove-team {
  justify-self: start;
  margin-top: var(--app-space-xs);
}

.empty-teams {
  margin: 0;
  padding: var(--app-space-md);
  text-align: center;
  color: var(--app-text-muted);
}
</style>
