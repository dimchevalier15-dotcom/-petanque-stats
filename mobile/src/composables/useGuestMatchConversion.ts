import type { Router } from 'vue-router'
import type { MatchDraft, MatchPlayState, MatchSetup } from '../models/MatchDraft'
import {
  loadMatchDraft,
  transferGuestDraftToUser,
} from '../services/matchDraftStorage'
import { useGuestStore } from '../stores/guest'
import { unresolvedParticipants } from '../utils/matchParticipants'

export function draftToSetup(draft: MatchDraft): MatchSetup {
  return {
    id: draft.id,
    type: draft.type,
    targetScore: draft.targetScore,
    statisticsMode: draft.statisticsMode,
    teamA: draft.teamA,
    teamB: draft.teamB,
    teamAName: draft.teamAName,
    teamBName: draft.teamBName,
    trackedPlayers: draft.trackedPlayers,
    defaultShotTypes: draft.defaultShotTypes ?? {},
    startingRoles: draft.startingRoles,
    participants: draft.participants,
    startedAt: draft.startedAt,
  }
}

export function draftToPlayState(draft: MatchDraft): MatchPlayState {
  return {
    currentEndIndex: draft.currentEndIndex,
    ends: draft.ends,
    distanceEstimate: draft.distanceEstimate,
    currentRoles: draft.currentRoles,
    substitutions: draft.substitutions ?? [],
    openingScoreA: draft.openingScoreA ?? 0,
    openingScoreB: draft.openingScoreB ?? 0,
  }
}

export function loadPendingGuestDraft(): MatchDraft | null {
  return loadMatchDraft(null, { guest: true })
}

/**
 * Moves the guest draft to the authenticated user, then routes to save or participant resolution.
 */
export function routeAfterGuestAuth(router: Router, userId: number): void {
  const guest = useGuestStore()
  const draft = loadPendingGuestDraft()
  if (!draft) {
    void router.replace({ name: 'home' })
    return
  }

  transferGuestDraftToUser(userId, draft)
  guest.leaveGuestMode()

  const setup = draftToSetup(draft)
  const playState = draftToPlayState(draft)
  const pending = unresolvedParticipants(
    setup,
    playState.substitutions,
    draft.resolvedPlayers ?? {},
  )

  if (pending.length > 0) {
    void router.replace({ name: 'matchPlayers', params: { id: draft.id } })
    return
  }

  void router.replace({ name: 'guestMatchSave' })
}
