import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { MatchDraft } from '../models/MatchDraft'
import { clearMatchDraft, loadMatchDraft } from '../services/matchDraftStorage'
import { useAuthStore } from '../stores/auth'
import { useGuestStore } from '../stores/guest'

import { matchScore } from '../utils/matchScore'

export function draftScore(draft: MatchDraft): { scoreA: number; scoreB: number } {
  return matchScore(draft.ends, draft.openingScoreA ?? 0, draft.openingScoreB ?? 0)
}

export function useMatchDraftResume() {
  const auth = useAuthStore()
  const guest = useGuestStore()
  const router = useRouter()

  function loadCurrentDraft(): MatchDraft | null {
    if (guest.isGuestSession) {
      return loadMatchDraft(null, { guest: true })
    }
    return loadMatchDraft(auth.user?.id ?? null)
  }

  const draft = ref<MatchDraft | null>(loadCurrentDraft())

  function refresh(): void {
    draft.value = loadCurrentDraft()
  }

  function resume(): void {
    if (!draft.value) return
    router.push({ name: 'matchScore', params: { id: draft.value.id } })
  }

  function abandon(): void {
    if (guest.isGuestSession) {
      clearMatchDraft({ guest: true })
    } else {
      clearMatchDraft()
    }
    draft.value = null
  }

  onMounted(refresh)

  return {
    draft,
    hasDraft: () => draft.value !== null,
    refresh,
    resume,
    abandon,
    draftScore,
  }
}
