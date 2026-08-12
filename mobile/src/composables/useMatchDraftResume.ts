import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { MatchDraft } from '../models/MatchDraft'
import { clearMatchDraft, loadMatchDraft } from '../services/matchDraftStorage'
import { useAuthStore } from '../stores/auth'

export function draftScore(draft: MatchDraft): { scoreA: number; scoreB: number } {
  let scoreA = 0
  let scoreB = 0
  for (const end of draft.ends) {
    if (end.canceled) continue
    if (end.winner && end.points) {
      if (end.winner === 'A') scoreA += end.points
      else scoreB += end.points
    }
  }
  return { scoreA, scoreB }
}

export function useMatchDraftResume() {
  const auth = useAuthStore()
  const router = useRouter()
  const draft = ref<MatchDraft | null>(loadMatchDraft(auth.user?.id ?? null))

  function refresh(): void {
    draft.value = loadMatchDraft(auth.user?.id ?? null)
  }

  function resume(): void {
    if (!draft.value) return
    router.push({ name: 'matchScore', params: { id: draft.value.id } })
  }

  function abandon(): void {
    clearMatchDraft()
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
