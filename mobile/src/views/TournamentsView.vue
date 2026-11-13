<template>
  <AppPage>
    <PageHeader :title="t('tournaments.title')" :subtitle="t('tournaments.subtitle')" :show-back="false" />

    <Button
      v-if="isMaster"
      as="router-link"
      :to="{ name: 'tournamentCreate' }"
      :label="t('tournaments.create')"
      icon="pi pi-plus"
    />

    <p v-if="loading" class="app-section-subtitle">{{ t('tournaments.loading') }}</p>
    <Message v-else-if="error" severity="error">{{ error }}</Message>
    <p v-else-if="tournaments.length === 0" class="empty app-card">{{ t('tournaments.empty') }}</p>

    <button
      v-for="tournament in tournaments"
      :key="tournament.id"
      type="button"
      class="tournament-card app-card"
      @click="router.push({ name: 'tournamentDetail', params: { id: tournament.id } })"
    >
      <span class="tournament-name">{{ tournament.name }}</span>
      <span class="tournament-meta">
        <span class="status">{{ t(`tournaments.status.${tournament.status}`) }}</span>
        <span v-if="period(tournament)">{{ period(tournament) }}</span>
      </span>
      <i class="pi pi-chevron-right" aria-hidden="true" />
    </button>
  </AppPage>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import Message from "primevue/message";
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import AppPage from "../components/layout/AppPage.vue";
import PageHeader from "../components/layout/PageHeader.vue";
import { useIsAdmin } from "../composables/useIsAdmin";
import type { TournamentSummary } from "../models/TournamentSummary";
import { tournamentsService } from "../services/tournaments";
import { tournamentErrorMessage } from "../utils/tournamentError";

const { t, te } = useI18n();
const router = useRouter();
const isMaster = useIsAdmin();
const tournaments = ref<TournamentSummary[]>([]);
const loading = ref(true);
const error = ref("");

onMounted(async () => {
	try {
		tournaments.value = await tournamentsService.list();
	} catch (caught) {
		error.value = tournamentErrorMessage(caught, t, te);
	} finally {
		loading.value = false;
	}
});

function period(tournament: TournamentSummary): string {
	if (tournament.startsAt && tournament.endsAt) {
		return `${tournament.startsAt} – ${tournament.endsAt}`;
	}
	return tournament.startsAt ?? tournament.endsAt ?? "";
}
</script>

<style scoped>
.empty {
  margin: 0;
  padding: var(--app-space-md);
}

.tournament-card {
  display: grid;
  grid-template-columns: 1fr auto;
  grid-template-areas: 'name chevron' 'meta chevron';
  gap: 0.25rem var(--app-space-sm);
  align-items: center;
  width: 100%;
  padding: var(--app-space-md);
  text-align: left;
  color: inherit;
  cursor: pointer;
}

.tournament-name {
  grid-area: name;
  font-weight: 700;
}

.tournament-meta {
  grid-area: meta;
  display: flex;
  flex-wrap: wrap;
  gap: var(--app-space-sm);
  color: var(--app-text-muted);
  font-size: 0.8125rem;
}

.status {
  color: var(--app-primary);
  font-weight: 700;
}

.tournament-card i {
  grid-area: chevron;
  color: var(--app-text-subtle);
}
</style>
