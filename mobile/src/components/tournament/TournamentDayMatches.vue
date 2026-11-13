<template>
  <div class="match-list">
    <article
      v-for="match in day.matches"
      :key="match.id"
      class="match"
      :class="{ 'match--mine': match.highlighted, 'match--fictional': match.fictional }"
    >
      <div class="line">
        <span class="team">{{ sideName(match.team1) }}</span>
        <div class="score-cell">
          <div v-if="showScoreRead(match)" class="score-read">
            <span class="score">{{ scoreText(match) }}</span>
            <Button
              type="button"
              icon="pi pi-pencil"
              text
              rounded
              size="small"
              class="score-edit-btn"
              :aria-label="t('tournaments.actions.editScore')"
              :disabled="isSaving(match.id, 'score')"
              @click="openScoreEdit(match.id)"
            />
          </div>
          <template v-else-if="match.viewerCanEditScore">
            <div class="score-inputs">
              <InputText
                v-model="drafts[match.id].score1"
                type="number"
                inputmode="numeric"
                min="0"
                max="13"
                class="score-input"
                :disabled="isSaving(match.id, 'score')"
              />
              <span class="score-sep" aria-hidden="true">–</span>
              <InputText
                v-model="drafts[match.id].score2"
                type="number"
                inputmode="numeric"
                min="0"
                max="13"
                class="score-input"
                :disabled="isSaving(match.id, 'score')"
              />
            </div>
            <div v-if="showSaveAction(match, 'score') || (scoreEditMode[match.id] && hasScore(match) && !scoreDirty(match))" class="score-actions">
              <Button
                v-if="showSaveAction(match, 'score')"
                type="button"
                size="small"
                class="match-validate-btn score-validate-btn"
                :class="{ 'match-validate-btn--saved': savePhase(match.id, 'score') === 'success' }"
                icon="pi pi-check"
                :loading="savePhase(match.id, 'score') === 'loading'"
                :disabled="savePhase(match.id, 'score') === 'success'"
                :label="validateLabel(match.id, 'score')"
                @click="submitScore(match.id)"
              />
              <button
                v-else-if="scoreEditMode[match.id] && hasScore(match)"
                type="button"
                class="score-cancel"
                @click="closeScoreEdit(match.id)"
              >
                {{ t('tournaments.actions.cancel') }}
              </button>
            </div>
          </template>
          <span v-else class="score">{{ scoreText(match) }}</span>
        </div>
        <span class="team team--right">{{ sideName(match.team2) }}</span>
      </div>
      <div class="meta">
        <span>{{ t('tournaments.matches.lane', { lane: match.laneNumber }) }}</span>
        <span>{{ t(`tournaments.matches.${match.status}`) }}</span>
        <span v-if="match.fictional">{{ t('tournaments.matches.fictional') }}</span>
      </div>
      <div v-if="canEditTeams(match)" class="edit-row">
        <select
          v-model.number="drafts[match.id].team1"
          class="lane-select team-select"
          :disabled="isSaving(match.id, 'teams')"
        >
          <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
        </select>
        <select
          v-model.number="drafts[match.id].team2"
          class="lane-select team-select"
          :disabled="isSaving(match.id, 'teams')"
        >
          <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
        </select>
        <Button
          v-if="showSaveAction(match, 'teams')"
          type="button"
          size="small"
          class="match-validate-btn"
          :class="{ 'match-validate-btn--saved': savePhase(match.id, 'teams') === 'success' }"
          icon="pi pi-check"
          :loading="savePhase(match.id, 'teams') === 'loading'"
          :disabled="savePhase(match.id, 'teams') === 'success'"
          :label="validateLabel(match.id, 'teams')"
          @click="submitTeams(match.id)"
        />
      </div>
      <div v-if="admin" class="edit-row">
        <select
          v-model.number="drafts[match.id].lane"
          class="lane-select lane-select--solo"
          :disabled="isSaving(match.id, 'lane')"
        >
          <option v-for="lane in day.usableLaneNumbers" :key="lane" :value="lane">{{ lane }}</option>
        </select>
        <Button
          v-if="showSaveAction(match, 'lane')"
          type="button"
          size="small"
          class="match-validate-btn"
          :class="{ 'match-validate-btn--saved': savePhase(match.id, 'lane') === 'success' }"
          icon="pi pi-check"
          :loading="savePhase(match.id, 'lane') === 'loading'"
          :disabled="savePhase(match.id, 'lane') === 'success'"
          :label="validateLabel(match.id, 'lane')"
          @click="submitLane(match.id)"
        />
      </div>
    </article>
  </div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import { reactive, watch } from "vue";
import { useI18n } from "vue-i18n";
import type {
	TournamentDay,
	TournamentMatch,
	TournamentMatchSide,
} from "../../models/TournamentDay";
import type { TournamentTeam } from "../../models/TournamentTeam";

type EditKind = "score" | "teams" | "lane";
type SavePhase = "idle" | "loading" | "success";

const SAVE_FEEDBACK_MS = 1800;

const props = defineProps<{
	day: TournamentDay;
	teams: TournamentTeam[];
	admin: boolean;
	saveScore: (payload: {
		matchId: number;
		score1: number;
		score2: number;
	}) => Promise<boolean>;
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

const { t } = useI18n();
const drafts = reactive<
	Record<
		number,
		{
			score1: string;
			score2: string;
			lane: number;
			team1: number;
			team2: number;
		}
	>
>({});
const saveStates = reactive<Record<string, SavePhase>>({});
const scoreEditMode = reactive<Record<number, boolean>>({});

function stateKey(matchId: number, kind: EditKind): string {
	return `${matchId}:${kind}`;
}

function ensureDraft(match: TournamentMatch): void {
	drafts[match.id] ??= {
		score1: match.score1 === null ? "" : String(match.score1),
		score2: match.score2 === null ? "" : String(match.score2),
		lane: match.laneNumber,
		team1: match.team1.teamId ?? 0,
		team2: match.team2.teamId ?? 0,
	};
}

function syncDraftsFromServer(matches: TournamentMatch[]): void {
	for (const match of matches) {
		ensureDraft(match);
		const draft = drafts[match.id];
		if (!scoreDirty(match)) {
			draft.score1 = match.score1 === null ? "" : String(match.score1);
			draft.score2 = match.score2 === null ? "" : String(match.score2);
		}
		if (!teamsDirty(match)) {
			draft.team1 = match.team1.teamId ?? 0;
			draft.team2 = match.team2.teamId ?? 0;
		}
		if (!laneDirty(match)) {
			draft.lane = match.laneNumber;
		}
	}
}

syncDraftsFromServer(props.day.matches);

watch(
	() => props.day.matches,
	(matches) => {
		syncDraftsFromServer(matches);
	},
	{ deep: true },
);

function canEditTeams(match: TournamentMatch): boolean {
	return props.admin && match.phase === "championship" && !match.fictional;
}

function draftScore(value: string): number | null {
	const trimmed = value.trim();
	if (trimmed === "") {
		return null;
	}
	return Number(trimmed);
}

function scoreDirty(match: TournamentMatch): boolean {
	const draft = drafts[match.id];
	if (!draft) {
		return false;
	}
	return (
		draftScore(draft.score1) !== match.score1 ||
		draftScore(draft.score2) !== match.score2
	);
}

function teamsDirty(match: TournamentMatch): boolean {
	const draft = drafts[match.id];
	if (!draft) {
		return false;
	}
	return (
		draft.team1 !== (match.team1.teamId ?? 0) ||
		draft.team2 !== (match.team2.teamId ?? 0)
	);
}

function laneDirty(match: TournamentMatch): boolean {
	const draft = drafts[match.id];
	return draft ? draft.lane !== match.laneNumber : false;
}

function savePhase(matchId: number, kind: EditKind): SavePhase {
	return saveStates[stateKey(matchId, kind)] ?? "idle";
}

function isSaving(matchId: number, kind: EditKind): boolean {
	return savePhase(matchId, kind) === "loading";
}

function showSaveAction(match: TournamentMatch, kind: EditKind): boolean {
	const phase = savePhase(match.id, kind);
	if (phase === "loading" || phase === "success") {
		return true;
	}
	if (kind === "score") {
		return scoreDirty(match);
	}
	if (kind === "teams") {
		return teamsDirty(match);
	}
	return laneDirty(match);
}

function validateLabel(matchId: number, kind: EditKind): string {
	if (savePhase(matchId, kind) === "success") {
		return t("tournaments.actions.saved");
	}
	return t("tournaments.actions.validate");
}

async function runSave(
	matchId: number,
	kind: EditKind,
	save: () => Promise<boolean>,
): Promise<void> {
	const key = stateKey(matchId, kind);
	saveStates[key] = "loading";
	const ok = await save();
	if (!ok) {
		saveStates[key] = "idle";
		return;
	}
	saveStates[key] = "success";
	if (kind === "score") {
		closeScoreEdit(matchId);
	}
	window.setTimeout(() => {
		if (saveStates[key] === "success") {
			saveStates[key] = "idle";
		}
	}, SAVE_FEEDBACK_MS);
}

async function submitScore(matchId: number): Promise<void> {
	const draft = drafts[matchId];
	await runSave(matchId, "score", () =>
		props.saveScore({
			matchId,
			score1: Number(draft.score1),
			score2: Number(draft.score2),
		}),
	);
}

async function submitTeams(matchId: number): Promise<void> {
	const draft = drafts[matchId];
	await runSave(matchId, "teams", () =>
		props.saveTeams({
			matchId,
			team1Id: draft.team1,
			team2Id: draft.team2,
		}),
	);
}

async function submitLane(matchId: number): Promise<void> {
	const draft = drafts[matchId];
	await runSave(matchId, "lane", () =>
		props.saveLane({
			matchId,
			laneNumber: draft.lane,
		}),
	);
}

function sideName(side: TournamentMatchSide): string {
	if (side.name) {
		return side.name;
	}
	if (side.pendingWinnerOfRound) {
		return t("tournaments.matches.pending", {
			round: t(`tournaments.matches.rounds.${side.pendingWinnerOfRound}`),
			slot: side.pendingWinnerOfSlot ?? "",
		});
	}
	return "—";
}

function hasScore(match: TournamentMatch): boolean {
	return match.score1 !== null && match.score2 !== null;
}

function showScoreInputs(match: TournamentMatch): boolean {
	if (!match.viewerCanEditScore) {
		return false;
	}
	if (!hasScore(match)) {
		return true;
	}
	if (scoreEditMode[match.id]) {
		return true;
	}
	const phase = savePhase(match.id, "score");
	return phase === "loading" || phase === "success" || scoreDirty(match);
}

function showScoreRead(match: TournamentMatch): boolean {
	return match.viewerCanEditScore && hasScore(match) && !showScoreInputs(match);
}

function openScoreEdit(matchId: number): void {
	scoreEditMode[matchId] = true;
}

function closeScoreEdit(matchId: number): void {
	scoreEditMode[matchId] = false;
}

function scoreText(match: TournamentMatch): string {
	if (!hasScore(match)) {
		return "–";
	}
	return `${match.score1} - ${match.score2}`;
}
</script>

<style scoped>
.match-list {
  display: grid;
  gap: var(--app-space-sm);
}

.match {
  display: grid;
  gap: 0.35rem;
  padding: 0.6rem 0.7rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: var(--app-surface);
}

.match--mine {
  border-color: #2563eb;
  background: #e8f1fb;
}

.line {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 0.4rem;
  align-items: center;
}

.team {
  font-weight: 700;
  font-size: 0.9rem;
}

.team--right {
  text-align: right;
}

.score-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  justify-self: center;
}

.score-read {
  display: flex;
  align-items: center;
  gap: 0.1rem;
}

.score {
  font-variant-numeric: tabular-nums;
  font-weight: 800;
}

.score-inputs {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.score-inputs :deep(input.p-inputtext) {
  width: 2.65rem;
  min-height: 2.25rem;
  text-align: center;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  padding-inline: 0.25rem;
  -moz-appearance: textfield;
  appearance: textfield;
}

.score-inputs :deep(input.p-inputtext::-webkit-outer-spin-button),
.score-inputs :deep(input.p-inputtext::-webkit-inner-spin-button) {
  -webkit-appearance: none;
  margin: 0;
}

.score-sep {
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  color: var(--app-text-muted);
}

.score-actions {
  display: flex;
  justify-content: center;
}

.score-validate-btn {
  margin-left: 0 !important;
}

.score-cancel {
  border: 0;
  padding: 0.15rem 0.35rem;
  background: transparent;
  color: var(--app-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

.score-edit-btn {
  flex-shrink: 0;
  width: 1.85rem;
  height: 1.85rem;
  padding: 0;
  color: var(--app-text-muted);
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
  color: var(--app-text-muted);
  font-size: 0.75rem;
}

.edit-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
}

.edit-row :deep(input.p-inputtext) {
  width: 4.25rem;
  min-height: 2.5rem;
}

.lane-select {
  min-height: 2.5rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: white;
}

.lane-select--solo {
  width: 4.25rem;
}

.team-select {
  width: 7.5rem;
}

.edit-row .match-validate-btn {
  flex-shrink: 0;
  margin-left: auto;
  transition: background 0.2s ease, border-color 0.2s ease;
}

.edit-row :deep(.match-validate-btn.p-button) {
  font-weight: 600;
  font-size: 0.8125rem;
  padding-inline: 0.65rem;
  background: var(--app-primary) !important;
  border-color: var(--app-primary) !important;
}

.edit-row :deep(.match-validate-btn--saved.p-button) {
  background: #15803d !important;
  border-color: #15803d !important;
}
</style>
