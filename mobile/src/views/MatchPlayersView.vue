<template>
  <PageHeader :title="t('matches.players.title')" :subtitle="t('matches.players.subtitle')" />

  <section class="resolve">
    <p class="resolve-hint">{{ t('matches.players.hint') }}</p>

    <article v-for="entry in resolutions" :key="entry.participantId" class="app-card resolve-card">
      <header class="resolve-head">
        <span class="resolve-name">{{ labelFor(entry.participantId) }}</span>
        <SelectButton
          v-model="entry.kind"
          :options="kindOptions"
          option-label="label"
          option-value="value"
          size="small"
          class="kind-picker"
        />
      </header>

      <PlayerSearchSelect
        v-if="entry.kind === 'existing'"
        :model-value="selectedPlayers[entry.participantId] ?? null"
        :placeholder="t('matches.players.searchPlaceholder')"
        :empty-hint="t('matches.players.searchEmpty')"
        @update:model-value="(player) => onPlayerSelected(entry, player)"
      />

      <p v-else-if="entry.kind === 'skip'" class="resolve-skip-hint">
        {{ t('matches.players.skipHint') }}
      </p>

      <div v-else class="resolve-form">
        <label class="app-field">
          <span>{{ t('players.fields.firstName') }}</span>
          <InputText v-model="entry.firstName" fluid :invalid="showErrors && entry.firstName.trim() === ''" />
        </label>
        <label class="app-field">
          <span>{{ t('players.fields.lastName') }}</span>
          <InputText v-model="entry.lastName" fluid :invalid="showErrors && entry.lastName.trim() === ''" />
        </label>
        <label class="app-field">
          <span>{{ t('players.fields.nickname') }}</span>
          <InputText v-model="entry.nickname" fluid />
        </label>
        <ClubSelect v-model="entry.clubId" />
      </div>

      <small v-if="showErrors && !isResolutionComplete(entry)" class="field-error">
        {{ t('matches.players.incomplete') }}
      </small>
    </article>

    <p v-if="showErrors && hasDuplicateSelection" class="form-banner" role="alert">
      {{ t('matches.players.duplicate') }}
    </p>
    <p v-if="errorMessage" class="form-banner" role="alert">{{ errorMessage }}</p>

    <Button
      class="save-btn"
      :label="t('matches.players.save')"
      icon="pi pi-check"
      :loading="saving"
      @click="onSave"
    />
  </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import PageHeader from '../components/layout/PageHeader.vue'
import ClubSelect from '../components/players/ClubSelect.vue'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import { useMatchFinalization } from '../composables/useMatchFinalization'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { Player } from '../models/Player'
import {
  isResolutionComplete,
  resolutionFromLabel,
  type ParticipantResolution,
} from '../models/ParticipantResolution'
import { loadMatchDraft } from '../services/matchDraftStorage'
import { useAuthStore } from '../stores/auth'
import {
  findParticipant,
  isProvisionalParticipant,
  unresolvedParticipants,
} from '../utils/matchParticipants'
import { allMatchPlayerIds } from '../utils/matchSubstitutions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const draftId = Number(route.params.id)
const draft = loadMatchDraft(auth.user?.id ?? null)
const activeDraft = draft !== null && draft.id === draftId ? draft : null

if (!activeDraft) {
  void router.replace({ name: 'home' })
}

const setup: MatchSetup = {
  id: activeDraft?.id ?? 0,
  type: activeDraft?.type ?? 'doublette',
  targetScore: activeDraft?.targetScore ?? 13,
  statisticsMode: activeDraft?.statisticsMode ?? 'standard',
  teamA: activeDraft?.teamA ?? [],
  teamB: activeDraft?.teamB ?? [],
  teamAName: activeDraft?.teamAName ?? null,
  teamBName: activeDraft?.teamBName ?? null,
  trackedPlayers: activeDraft?.trackedPlayers ?? [],
  defaultShotTypes: activeDraft?.defaultShotTypes ?? {},
  startingRoles: activeDraft?.startingRoles ?? {},
  participants: activeDraft?.participants ?? [],
  startedAt: activeDraft?.startedAt ?? new Date().toISOString(),
}

const playState: MatchPlayState = {
  currentEndIndex: activeDraft?.currentEndIndex ?? 0,
  ends: activeDraft?.ends ?? [],
  distanceEstimate: activeDraft?.distanceEstimate ?? null,
  currentRoles: activeDraft?.currentRoles ?? {},
  substitutions: activeDraft?.substitutions ?? [],
  openingScoreA: activeDraft?.openingScoreA ?? 0,
  openingScoreB: activeDraft?.openingScoreB ?? 0,
}

const { saving, save, error: saveError, progress } = useMatchFinalization(setup, {
  serverId: activeDraft?.serverId ?? null,
  resolvedPlayers: activeDraft?.resolvedPlayers ?? {},
})

const pending = unresolvedParticipants(setup, playState.substitutions, progress.resolvedPlayers)

const resolutions = reactive<ParticipantResolution[]>(
  pending.map((participant) => resolutionFromLabel(participant.id, participant.label)),
)

const selectedPlayers = reactive<Record<number, Player | null>>({})
const showErrors = ref(false)
const errorMessage = ref('')

const kindOptions = computed(() => [
  { label: t('matches.players.kinds.skip'), value: 'skip' as const },
  { label: t('matches.players.kinds.existing'), value: 'existing' as const },
  { label: t('matches.players.kinds.new'), value: 'new' as const },
])

/** Player ids already taken by a real participant of this match. */
const takenPlayerIds = computed(() =>
  allMatchPlayerIds(setup.teamA, setup.teamB, playState.substitutions).filter(
    (id) => !isProvisionalParticipant(id),
  ),
)

const hasDuplicateSelection = computed(() => {
  const linked = resolutions.flatMap((entry) =>
    entry.kind === 'existing' && entry.playerId !== null ? [entry.playerId] : [],
  )
  const all = [...takenPlayerIds.value, ...linked]
  return new Set(all).size !== all.length
})

function labelFor(participantId: number): string {
  return findParticipant(setup.participants, participantId)?.label ?? `#${-participantId}`
}

function onPlayerSelected(entry: ParticipantResolution, player: Player | null): void {
  selectedPlayers[entry.participantId] = player
  entry.playerId = player?.id ?? null
}

async function onSave(): Promise<void> {
  showErrors.value = true
  errorMessage.value = ''

  if (!resolutions.every(isResolutionComplete) || hasDuplicateSelection.value) {
    return
  }

  const matchId = await save(playState, [...resolutions])
  if (matchId === null) {
    errorMessage.value =
      saveError.value === 'tooManyPlaceholders'
        ? t('matches.players.tooManyPlaceholders')
        : t('matches.players.saveError')
    return
  }

  void router.replace({ name: 'matchSummary', params: { id: matchId } })
}
</script>

<style scoped>
.resolve {
  display: grid;
  gap: var(--app-space-md);
  padding-bottom: calc(env(safe-area-inset-bottom, 0px) + var(--app-space-xl));
}

.resolve-hint {
  margin: 0;
  color: var(--app-text-muted);
  font-size: 0.875rem;
  line-height: 1.45;
}

.resolve-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-md);
}

.resolve-head {
  display: grid;
  gap: var(--app-space-sm);
}

.resolve-name {
  font-weight: 800;
  font-size: 1rem;
}

.kind-picker :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  width: 100%;
}

.kind-picker :deep(.p-togglebutton) {
  justify-content: center;
  font-size: 0.75rem;
  min-height: 2.25rem;
}

.resolve-skip-hint {
  margin: 0;
  color: var(--app-text-muted);
  font-size: 0.8125rem;
  line-height: 1.4;
}

.resolve-form {
  display: grid;
  gap: var(--app-space-md);
}

.field-error {
  color: #c24141;
  font-size: 0.75rem;
}

.form-banner {
  margin: 0;
  padding: var(--app-space-sm) var(--app-space-md);
  border-radius: var(--app-radius-sm);
  background: #fef2f2;
  color: #b91c1c;
  font-size: 0.8125rem;
  font-weight: 600;
}

.save-btn {
  width: 100%;
  min-height: 3rem;
  font-weight: 700;
}
</style>
