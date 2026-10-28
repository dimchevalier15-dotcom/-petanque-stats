import { computed } from 'vue'
import { useRoute } from 'vue-router'

export type CoachSpaceKind = 'club' | 'selection'

export function useCoachSpaceFromRoute() {
  const route = useRoute()

  const kind = computed<CoachSpaceKind>(() =>
    route.meta.coachSpace === 'selection' ? 'selection' : 'club',
  )

  const selectionGlobaleId = computed<number | null>(() => {
    if (kind.value !== 'selection') {
      return null
    }
    const raw = route.params.selectionId
    const id = Number(Array.isArray(raw) ? raw[0] : raw)
    return Number.isFinite(id) && id > 0 ? id : null
  })

  const playerListRouteName = computed(() =>
    kind.value === 'selection' ? 'selectionGlobalePlayers' : 'coachPlayers',
  )

  const playerDetailRouteName = computed(() =>
    kind.value === 'selection' ? 'selectionGlobalePlayer' : 'coachPlayer',
  )

  const playerHistoryRouteName = computed(() =>
    kind.value === 'selection' ? 'selectionGlobalePlayerHistory' : 'coachPlayerHistory',
  )

  return {
    kind,
    selectionGlobaleId,
    playerListRouteName,
    playerDetailRouteName,
    playerHistoryRouteName,
  }
}
