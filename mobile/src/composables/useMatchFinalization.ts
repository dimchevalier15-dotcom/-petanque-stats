import { reactive, ref } from 'vue'
import type { MatchDraftProgress, MatchPlayState, MatchSetup } from '../models/MatchDraft'
import type { ParticipantResolution } from '../models/ParticipantResolution'
import { matchesService } from '../services/matches'
import { playersService } from '../services/players'
import { clearMatchDraft, saveMatchDraftProgress } from '../services/matchDraftStorage'
import {
  containsProvisionalParticipant,
  remapSetup,
  remapSubmission,
} from '../utils/matchParticipants'
import { buildCreateMatchRequest, buildMatchSubmission } from '../utils/matchSubmission'

/**
 * Saves a finished match in three steps: the missing Players, the match, then its content.
 * Each step records its outcome in the draft, so a failed attempt resumes where it stopped
 * without ever duplicating a Player or a match. See ADR-001.
 */
export function useMatchFinalization(setup: MatchSetup, initialProgress: MatchDraftProgress) {
  const progress = reactive<MatchDraftProgress>({
    serverId: initialProgress.serverId,
    resolvedPlayers: { ...initialProgress.resolvedPlayers },
  })
  const saving = ref(false)

  async function resolveParticipants(resolutions: ParticipantResolution[]): Promise<void> {
    for (const resolution of resolutions) {
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

    try {
      await resolveParticipants(resolutions)

      const mapping = { ...progress.resolvedPlayers }
      const payload = remapSubmission(buildMatchSubmission(setup, state), mapping)
      if (containsProvisionalParticipant(payload)) {
        return null
      }

      const matchId = await createMatch(remapSetup(setup, mapping))
      await matchesService.complete(matchId, payload)

      clearMatchDraft()
      return matchId
    } catch {
      return null
    } finally {
      saving.value = false
    }
  }

  return { saving, progress, save }
}
