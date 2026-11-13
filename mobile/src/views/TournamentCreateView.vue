<template>
  <AppPage>
    <PageHeader :title="t('tournaments.create')" :back-to="{ name: 'tournaments' }" />

    <form v-if="isMaster" class="app-form app-card form-card" @submit.prevent="submit">
      <p class="format">{{ t('tournaments.formatLabel') }}</p>

      <label class="app-field">
        <span>{{ t('tournaments.fields.name') }}</span>
        <InputText v-model="name" fluid required />
      </label>
      <label class="app-field">
        <span>{{ t('tournaments.fields.description') }}</span>
        <InputText v-model="description" fluid />
      </label>

      <div class="app-field">
        <span>{{ t('tournaments.fields.admins') }}</span>
        <div class="inline">
          <InputText v-model="adminQuery" :placeholder="t('tournaments.fields.adminSearch')" fluid />
          <Button type="button" :label="t('tournaments.actions.search')" outlined @click="searchAdmins" />
        </div>
        <button
          v-for="user in adminResults"
          :key="user.id"
          type="button"
          class="pick"
          @click="addAdmin(user)"
        >
          {{ user.email }}
        </button>
        <div v-for="admin in admins" :key="admin.id" class="chip">
          <span>{{ admin.email }}</span>
          <Button type="button" icon="pi pi-times" text rounded @click="removeAdmin(admin.id)" />
        </div>
      </div>

      <div class="pair">
        <label class="app-field">
          <span>{{ t('tournaments.fields.minPlayers') }}</span>
          <InputText v-model.number="minPlayers" type="number" min="1" max="10" fluid />
        </label>
        <label class="app-field">
          <span>{{ t('tournaments.fields.maxPlayers') }}</span>
          <InputText v-model.number="maxPlayers" type="number" min="1" max="10" fluid />
        </label>
      </div>
      <div class="pair">
        <label class="app-field">
          <span>{{ t('tournaments.fields.pointsWin') }}</span>
          <InputText v-model.number="pointsForWin" type="number" min="0" max="20" fluid />
        </label>
        <label class="app-field">
          <span>{{ t('tournaments.fields.pointsDraw') }}</span>
          <InputText v-model.number="pointsForDraw" type="number" min="0" max="20" fluid />
        </label>
      </div>
      <label class="check">
        <input v-model="drawsAllowed" type="checkbox" />
        <span>{{ t('tournaments.fields.draws') }}</span>
      </label>
      <label class="app-field">
        <span>{{ t('tournaments.fields.lanes') }}</span>
        <InputText v-model.number="numberOfLanes" type="number" min="1" max="40" fluid />
      </label>
      <label class="app-field">
        <span>{{ t('tournaments.fields.excludedLanes') }}</span>
        <InputText v-model="excludedText" placeholder="5, 8" fluid />
      </label>
      <p v-if="lanePreview.length" class="hint">{{ lanePreview.join(', ') }}</p>
      <div class="pair">
        <label class="app-field">
          <span>{{ t('tournaments.fields.startsAt') }}</span>
          <input v-model="startsAt" type="date" class="date-input" />
        </label>
        <label class="app-field">
          <span>{{ t('tournaments.fields.endsAt') }}</span>
          <input v-model="endsAt" type="date" class="date-input" />
        </label>
      </div>

      <Message v-if="error" severity="error">{{ error }}</Message>
      <Button type="submit" :label="t('tournaments.actions.save')" :loading="saving" />
    </form>
  </AppPage>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import AppPage from "../components/layout/AppPage.vue";
import PageHeader from "../components/layout/PageHeader.vue";
import { useIsAdmin } from "../composables/useIsAdmin";
import { userHasMasterAccess } from "../models/UserRole";
import { tournamentsService } from "../services/tournaments";
import { useAuthStore } from "../stores/auth";
import { tournamentErrorMessage } from "../utils/tournamentError";
import { parseLaneNumbers, usableLaneNumbers } from "../utils/tournamentLanes";

const { t, te } = useI18n();
const router = useRouter();
const auth = useAuthStore();
const isMaster = useIsAdmin();
const name = ref("");
const description = ref("");
const adminQuery = ref("");
const adminResults = ref<{ id: number; email: string }[]>([]);
const admins = ref<{ id: number; email: string }[]>([]);
const minPlayers = ref(2);
const maxPlayers = ref(3);
const pointsForWin = ref(1);
const pointsForDraw = ref(0);
const drawsAllowed = ref(false);
const numberOfLanes = ref(8);
const excludedText = ref("");
const startsAt = ref("");
const endsAt = ref("");
const error = ref("");
const saving = ref(false);

const lanePreview = computed(() =>
	usableLaneNumbers(
		numberOfLanes.value || 0,
		parseLaneNumbers(excludedText.value),
	),
);

watch(
	() => auth.user,
	(user) => {
		if (user && !userHasMasterAccess(user)) {
			void router.replace({ name: "tournaments" });
		}
	},
	{ immediate: true },
);

async function searchAdmins(): Promise<void> {
	error.value = "";
	try {
		adminResults.value = await tournamentsService.searchUsers(adminQuery.value);
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
	}
}

function addAdmin(user: { id: number; email: string }): void {
	if (!admins.value.some((admin) => admin.id === user.id)) {
		admins.value.push(user);
	}
	adminResults.value = [];
	adminQuery.value = "";
}

function removeAdmin(id: number): void {
	admins.value = admins.value.filter((admin) => admin.id !== id);
}

async function submit(): Promise<void> {
	error.value = "";
	saving.value = true;
	try {
		const created = await tournamentsService.create({
			name: name.value.trim(),
			description: description.value.trim() || null,
			administratorUserIds: admins.value.map((admin) => admin.id),
			minPlayersPerTeam: minPlayers.value,
			maxPlayersPerTeam: maxPlayers.value,
			pointsForWin: pointsForWin.value,
			pointsForDraw: pointsForDraw.value,
			drawsAllowed: drawsAllowed.value,
			numberOfLanes: numberOfLanes.value,
			excludedLaneNumbers: parseLaneNumbers(excludedText.value),
			startsAt: startsAt.value || null,
			endsAt: endsAt.value || null,
		});
		await router.push({ name: "tournamentDetail", params: { id: created.id } });
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
	} finally {
		saving.value = false;
	}
}
</script>

<style scoped>
.form-card {
  display: grid;
  gap: var(--app-space-md);
  padding: var(--app-space-md);
}

.format {
  margin: 0;
  font-weight: 700;
}

.inline,
.pair,
.chip {
  display: flex;
  gap: var(--app-space-sm);
  align-items: center;
}

.pair {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.pick,
.check {
  display: flex;
  gap: var(--app-space-sm);
  align-items: center;
  min-height: var(--app-touch-min);
  text-align: left;
}

.hint {
  margin: 0;
  color: var(--app-text-muted);
  font-size: 0.875rem;
}

.date-input {
  min-height: var(--app-touch-min);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  padding: 0.5rem 0.75rem;
}
</style>
