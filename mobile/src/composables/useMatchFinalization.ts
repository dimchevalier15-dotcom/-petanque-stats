import { reactive, ref } from 'vue'
import type { MatchDraftProgress, MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { ParticipantResolution } from '../models/ParticipantResolution'
import { matchesService } from '../services/matches'
import { playersService } from '../services/players'
import { clearMatchDraft, saveMatchDraftProgress } from '../services/matchDraftStorage'
import {
  assignPlaceholderPlayers,
  containsProvisionalParticipant,
  excludePlayersFromTracked,
  remapSetup,
  remapSubmission,
  TooManyPlaceholderParticipantsError,
  unresolvedParticipants,
} from '../utils/matchParticipants'
import { buildCreateMatchRequest, buildMatchSubmission } from '../utils/matchSubmission'

/**
 * Saves a finished match in three steps: the missing Players, the match, then its content.
 * Unresolved provisional participants are mapped to placeholder Players (A–F), excluded from
 * tracked statistics. See ADR-001.
 */
export function useMatchFinalization(setup: MatchSetup, initialProgress: MatchDraftProgress) {
  const progress = reactive<MatchDraftProgress>({
    serverId: initialProgress.serverId,
    resolvedPlayers: { ...initialProgress.resolvedPlayers },
  })
  const saving = ref(false)
  const error = ref<'save' | 'tooManyPlaceholders' | null>(null)

  async function resolveParticipants(resolutions: ParticipantResolution[]): Promise<void> {
    for (const resolution of resolutions) {
      if (resolution.kind === 'skip') {
        continue
      }

      if (progress.resolvedPlayers[resolution.participantId] !== undefined) {
        continue
      }

      let playerId = resolution.playerId
      if (resolution.kind === 'new') {
        const nickname = resolution.nickname.trim()
        const created = await playersService.create({
          firstName: resolution.firstName.trim(),
          lastName: resolution.lastName.trim(),
          clubId: resolution.clubId,
          ...(nickname === '' ? {} : { nickname }),
        })
        playerId = created.id
      }

      if (playerId === null) {
        throw new Error('Unresolved participant')
      }

      progress.resolvedPlayers[resolution.participantId] = playerId
      saveMatchDraftProgress({
        resolvedPlayers: { [resolution.participantId]: playerId },
      })
    }
  }

  function buildFinalMapping(
    state: MatchPlayState,
    resolutions: ParticipantResolution[],
    placeholderPlayerIds: number[],
  ): Record<number, number> {
    const mapping = { ...progress.resolvedPlayers }

    for (const resolution of resolutions) {
      if (resolution.kind === 'existing' && resolution.playerId !== null) {
        mapping[resolution.participantId] = resolution.playerId
      }
    }

    const pendingIds = unresolvedParticipants(setup, state.substitutions, mapping).map(
      (participant) => participant.id,
    )
    assignPlaceholderPlayers(pendingIds, placeholderPlayerIds, mapping)

    return mapping
  }

  async function createMatch(resolvedSetup: MatchSetup): Promise<number> {
    if (progress.serverId !== null) {
      return progress.serverId
    }

    const { id } = await matchesService.create(buildCreateMatchRequest(resolvedSetup))
    progress.serverId = id
    saveMatchDraftProgress({ serverId: id })
    return id
  }

  /** Returns the server match id, or null when the save failed. */
  async function save(
    state: MatchPlayState,
    resolutions: ParticipantResolution[] = [],
  ): Promise<number | null> {
    if (saving.value) {
      return null
    }

    saving.value = true
    error.value = null

    try {
      await resolveParticipants(resolutions)

      const placeholderPlayerIds = await playersService.placeholderPlayerIds()
      const placeholderIds = new Set(placeholderPlayerIds)
      const mapping = buildFinalMapping(state, resolutions, placeholderPlayerIds)

      let resolvedSetup = remapSetup(setup, mapping)
      resolvedSetup = {
        ...resolvedSetup,
        trackedPlayers: excludePlayersFromTracked(resolvedSetup.trackedPlayers, placeholderIds),
      }

      let payload = remapSubmission(buildMatchSubmission(setup, state), mapping)
      payload = {
        ...payload,
        trackedPlayers: excludePlayersFromTracked(payload.trackedPlayers, placeholderIds),
      }

      if (containsProvisionalParticipant(payload)) {
        error.value = 'save'
        return null
      }

      const matchId = await createMatch(resolvedSetup)
      await matchesService.complete(matchId, payload)

      clearMatchDraft()
      return matchId
    } catch (caught) {
      error.value =
        caught instanceof TooManyPlaceholderParticipantsError ? 'tooManyPlaceholders' : 'save'
      return null
    } finally {
      saving.value = false
    }
  }

  return { saving, error, progress, save }
}
