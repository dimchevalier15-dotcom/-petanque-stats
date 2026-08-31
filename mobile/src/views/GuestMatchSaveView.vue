<template>
  <AppPage>
    <section class="save-state app-card">
      <p v-if="pending">{{ t('guest.save.inProgress') }}</p>
      <Message v-else-if="errorMessage" severity="error">{{ errorMessage }}</Message>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Message from 'primevue/message'
import AppPage from '../components/layout/AppPage.vue'
import { useMatchFinalization } from '../composables/useMatchFinalization'
import { draftToPlayState, draftToSetup } from '../composables/useGuestMatchConversion'
import { loadMatchDraft } from '../services/matchDraftStorage'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const pending = ref(true)
const errorMessage = ref('')

const userId = auth.user?.id ?? null
const draft = userId !== null ? loadMatchDraft(userId) : null

if (!draft || userId === null) {
  void router.replace(
    userId === null
      ? { name: 'login', query: { saveGuestMatch: '1' } }
      : { name: 'home' },
  )
}

const setup = draft ? draftToSetup(draft) : draftToSetup({
  id: 0,
  type: 'doublette',
  targetScore: 13,
  statisticsMode: 'standard',
  teamA: [],
  teamB: [],
  teamAName: null,
  teamBName: null,
  trackedPlayers: [],
  startingRoles: {},
  participants: [],
  startedAt: new Date().toISOString(),
})

const playState = draft ? draftToPlayState(draft) : draftToPlayState({
  currentEndIndex: 0,
  ends: [],
  distanceEstimate: null,
  currentRoles: {},
  substitutions: [],
})

const { save, error } = useMatchFinalization(setup, {
  serverId: draft?.serverId ?? null,
  resolvedPlayers: draft?.resolvedPlayers ?? {},
})

onMounted(async () => {
  if (!draft || userId === null) {
    pending.value = false
    return
  }

  const matchId = await save(playState)
  pending.value = false

  if (matchId !== null) {
    void router.replace({ name: 'matchSummary', params: { id: matchId } })
    return
  }

  errorMessage.value =
    error.value === 'tooManyPlaceholders'
      ? t('matches.players.tooManyPlaceholders')
      : t('matches.players.saveError')
})
</script>

<style scoped>
.save-state {
  padding: var(--app-space-lg);
  text-align: center;
}
</style>
