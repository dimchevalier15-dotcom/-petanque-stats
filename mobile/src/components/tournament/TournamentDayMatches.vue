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
        <span class="score">{{ scoreText(match) }}</span>
        <span class="team team--right">{{ sideName(match.team2) }}</span>
      </div>
      <div class="meta">
        <span>{{ t('tournaments.matches.lane', { lane: match.laneNumber }) }}</span>
        <span>{{ t(`tournaments.matches.${match.status}`) }}</span>
        <span v-if="match.fictional">{{ t('tournaments.matches.fictional') }}</span>
      </div>
      <div v-if="match.viewerCanEditScore" class="score-form">
        <InputText v-model="drafts[match.id].score1" type="number" inputmode="numeric" min="0" max="13" />
        <InputText v-model="drafts[match.id].score2" type="number" inputmode="numeric" min="0" max="13" />
        <Button size="small" :label="t('tournaments.actions.saveScore')" @click="saveScore(match.id)" />
      </div>
      <div v-if="canEditTeams(match)" class="lane-form">
        <select v-model.number="drafts[match.id].team1" class="lane-select team-select">
          <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
        </select>
        <select v-model.number="drafts[match.id].team2" class="lane-select team-select">
          <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
        </select>
        <Button size="small" outlined :label="t('tournaments.actions.saveTeams')" @click="emit('teams', { matchId: match.id, team1Id: drafts[match.id].team1, team2Id: drafts[match.id].team2 })" />
      </div>
      <div v-if="admin" class="lane-form">
        <select v-model.number="drafts[match.id].lane" class="lane-select">
          <option v-for="lane in day.usableLaneNumbers" :key="lane" :value="lane">{{ lane }}</option>
        </select>
        <Button size="small" outlined :label="t('tournaments.actions.saveLane')" @click="emit('lane', { matchId: match.id, laneNumber: drafts[match.id].lane })" />
      </div>
    </article>
  </div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import type {
	TournamentDay,
	TournamentMatch,
	TournamentMatchSide,
} from "../../models/TournamentDay";
import type { TournamentTeam } from "../../models/TournamentTeam";

const props = defineProps<{
	day: TournamentDay;
	teams: TournamentTeam[];
	admin: boolean;
}>();

const emit = defineEmits<{
	score: [payload: { matchId: number; score1: number; score2: number }];
	lane: [payload: { matchId: number; laneNumber: number }];
	teams: [payload: { matchId: number; team1Id: number; team2Id: number }];
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
for (const match of props.day.matches) {
	drafts[match.id] = {
		score1: match.score1 === null ? "" : String(match.score1),
		score2: match.score2 === null ? "" : String(match.score2),
		lane: match.laneNumber,
		team1: match.team1.teamId ?? 0,
		team2: match.team2.teamId ?? 0,
	};
}

function canEditTeams(match: TournamentMatch): boolean {
	return props.admin && match.phase === "championship" && !match.fictional;
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

function scoreText(match: TournamentMatch): string {
	if (match.score1 === null || match.score2 === null) {
		return "–";
	}
	return `${match.score1} - ${match.score2}`;
}

function saveScore(matchId: number): void {
	const draft = drafts[matchId];
	emit("score", {
		matchId,
		score1: Number(draft.score1),
		score2: Number(draft.score2),
	});
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

.score {
  font-variant-numeric: tabular-nums;
  font-weight: 800;
}

.meta,
.score-form,
.lane-form {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
  color: var(--app-text-muted);
  font-size: 0.75rem;
}

.score-form :deep(input),
.lane-select {
  width: 4.25rem;
  min-height: 2.5rem;
}

.lane-select {
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: white;
}

.team-select {
  width: 7.5rem;
}
</style>
