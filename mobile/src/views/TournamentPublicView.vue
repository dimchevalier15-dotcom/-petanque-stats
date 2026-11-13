<template>
  <AppPage compact>
    <PageHeader
      :title="detail?.name ?? t('tournaments.public.title')"
      :subtitle="detail ? t(`tournaments.status.${detail.status}`) : ''"
    />

    <p v-if="loading" class="app-section-subtitle">{{ t('tournaments.public.loading') }}</p>
    <Message v-else-if="notFound" severity="warn">{{ t('tournaments.public.notFound') }}</Message>
    <Message v-else-if="error" severity="error">{{ error }}</Message>

    <template v-if="detail">
      <p class="spectator-hint">{{ t('tournaments.public.hint') }}</p>
      <div class="type-picker" :style="{ '--tab-count': '2' }">
        <SelectButton v-model="tab" :options="tabs" option-label="label" option-value="value" />
      </div>

      <TournamentMatchesPanel
        v-if="tab === 'matches'"
        :tournament-name="detail.name"
        :days="detail.days"
        :teams="detail.teams"
        :admin="false"
        :save-score="noopSave"
        :save-lane="noopSave"
        :save-teams="noopSave"
        :delete-day="noopDeleteDay"
      />
      <TournamentRankingPanel v-else :ranking="detail.ranking" />
    </template>
  </AppPage>
</template>

<script setup lang="ts">
import Message from 'primevue/message'
import SelectButton from 'primevue/selectbutton'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import TournamentMatchesPanel from '../components/tournament/TournamentMatchesPanel.vue'
import TournamentRankingPanel from '../components/tournament/TournamentRankingPanel.vue'
import { useTournamentPublicPolling } from '../composables/useTournamentPublicPolling'

const { t } = useI18n()
const route = useRoute()
const publicUuid = String(route.params.uuid)
const tab = ref('matches')

const { detail, loading, notFound, error } = useTournamentPublicPolling(publicUuid)

const tabs = computed(() => [
  { label: t('tournaments.tabs.matches'), value: 'matches' },
  { label: t('tournaments.tabs.ranking'), value: 'ranking' },
])

async function noopSave(): Promise<boolean> {
  return false
}

async function noopDeleteDay(): Promise<void> {}
</script>

<style scoped>
.spectator-hint {
  margin: 0 0 var(--app-space-sm);
  font-size: 0.8125rem;
  line-height: 1.45;
  color: var(--app-text-muted);
}

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
