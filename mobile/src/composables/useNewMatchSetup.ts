import { computed, onMounted, reactive, ref, watch, type ComputedRef, type Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { playersService } from '../services/players'
import { matchesService } from '../services/matches'
import { playerToSearchOption, type PlayerSearchOption } from './usePlayerSearch'
import type { Player } from '../models/Player'
import {
  DEFAULT_TARGET_SCORE,
  type MatchType,
  type PlayerRole,
  type ShotType,
  type StatisticsMode,
} from '../models/Match'
import type { DefaultShotTypeDto } from '../dto/match/CreateMatchRequest'

export type MatchTeamSide = 'A' | 'B'

export interface NewMatchPlayerEntry {
  team: MatchTeamSide
  slot: number
  option: PlayerSearchOption
}

export interface UseNewMatchSetupReturn {
  type: Ref<MatchType>
  statisticsMode: Ref<StatisticsMode>
  teamAName: Ref<string>
  teamBName: Ref<string>
  typeOptions: ComputedRef<Array<{ label: string; value: MatchType }>>
  modeOptions: ComputedRef<Array<{ label: string; value: StatisticsMode }>>
  roleOptions: ComputedRef<Array<{ label: string; value: PlayerRole }>>
  teamASlots: ComputedRef<number[]>
  teamBSlots: ComputedRef<number[]>
  teamASelections: Array<PlayerSearchOption | null>
  teamBSelections: Array<PlayerSearchOption | null>
  teamASuggestions: Array<PlayerSearchOption[]>
  teamBSuggestions: Array<PlayerSearchOption[]>
  teamARoles: PlayerRole[]
  teamBRoles: PlayerRole[]
  configuredPlayers: ComputedRef<NewMatchPlayerEntry[]>
  showRoleConfig: ComputedRef<boolean>
  canStart: ComputedRef<boolean>
  submitting: Ref<boolean>
  attemptedSubmit: Ref<boolean>
  slotError: (team: MatchTeamSide, slot: number) => string | undefined
  showSlotError: (team: MatchTeamSide, slot: number) => boolean
  showDuplicateError: ComputedRef<boolean>
  onSearch: (team: MatchTeamSide, slot: number, query: string) => void
  touch: (team: MatchTeamSide, slot: number) => void
  goQuickAdd: (team: MatchTeamSide, slot: number) => void
  trackedFor: (team: MatchTeamSide, slot: number) => boolean
  setTrackedFor: (team: MatchTeamSide, slot: number, value: boolean) => void
  roleFor: (team: MatchTeamSide, slot: number) => PlayerRole
  setRoleFor: (team: MatchTeamSide, slot: number, role: PlayerRole) => void
  submit: () => Promise<void>
}

function slotsForType(type: MatchType): number[] {
  if (type === 'tete_a_tete') return [1]
  if (type === 'doublette') return [1, 2]
  return [1, 2, 3]
}

function defaultRoleFor(type: MatchType, position: number): PlayerRole {
  if (type === 'doublette') return position === 2 ? 'tireur' : 'pointeur'
  if (type === 'triplette') return position === 3 ? 'tireur' : 'pointeur'
  return 'pointeur'
}

function roleToShot(role: PlayerRole): ShotType {
  return role === 'tireur' ? 'tir' : 'point'
}

export function useNewMatchSetup(): UseNewMatchSetupReturn {
  const { t } = useI18n()
  const router = useRouter()

  const type = ref<MatchType>('doublette')
  const statisticsMode = ref<StatisticsMode>('standard')
  const teamAName = ref('')
  const teamBName = ref('')
  const tracked = reactive<Record<number, boolean>>({})
  const submitting = ref(false)
  const attemptedSubmit = ref(false)

  const teamARoles = reactive<PlayerRole[]>(['pointeur', 'tireur', 'tireur'])
  const teamBRoles = reactive<PlayerRole[]>(['pointeur', 'tireur', 'tireur'])
  const teamASelections = reactive<Array<PlayerSearchOption | null>>([null, null, null])
  const teamBSelections = reactive<Array<PlayerSearchOption | null>>([null, null, null])
  const teamASuggestions = reactive<Array<PlayerSearchOption[]>>([[], [], []])
  const teamBSuggestions = reactive<Array<PlayerSearchOption[]>>([[], [], []])
  const errors = reactive<Record<string, string>>({})
  const touched = reactive<Record<string, boolean>>({})
  const searchTimers = new Map<string, number>()

  const typeOptions = computed(() => [
    { label: t('matches.types.teteATete'), value: 'tete_a_tete' as MatchType },
    { label: t('matches.types.doublette'), value: 'doublette' as MatchType },
    { label: t('matches.types.triplette'), value: 'triplette' as MatchType },
  ])

  const modeOptions = computed(() => [
    { label: t('matches.stats.modes.standard'), value: 'standard' as StatisticsMode },
    { label: t('matches.stats.modes.simple'), value: 'simple' as StatisticsMode },
  ])

  const roleOptions = computed(() => {
    if (type.value === 'tete_a_tete') return []

    if (type.value === 'doublette') {
      return [
        { label: t('matches.roles.pointeur'), value: 'pointeur' as PlayerRole },
        { label: t('matches.roles.tireur'), value: 'tireur' as PlayerRole },
      ]
    }

    return [
      { label: t('matches.roles.pointeur'), value: 'pointeur' as PlayerRole },
      { label: t('matches.roles.milieu'), value: 'milieu' as PlayerRole },
      { label: t('matches.roles.tireur'), value: 'tireur' as PlayerRole },
    ]
  })

  const teamASlots = computed(() => slotsForType(type.value))
  const teamBSlots = computed(() => slotsForType(type.value))
  const showRoleConfig = computed(() => type.value !== 'tete_a_tete')

  function selectionAt(team: MatchTeamSide, slot: number): PlayerSearchOption | null {
    return team === 'A' ? teamASelections[slot - 1] : teamBSelections[slot - 1]
  }

  function configuredEntriesFor(team: MatchTeamSide, slots: number[]): NewMatchPlayerEntry[] {
    const selections = team === 'A' ? teamASelections : teamBSelections
    return slots
      .map((slot) => {
        const option = selections[slot - 1]
        return option ? { team, slot, option } : null
      })
      .filter((entry): entry is NewMatchPlayerEntry => entry !== null)
  }

  const configuredPlayers = computed(() => [
    ...configuredEntriesFor('A', teamASlots.value),
    ...configuredEntriesFor('B', teamBSlots.value),
  ])

  const selectedPlayers = computed(() => {
    const seen = new Set<number>()
    const list: PlayerSearchOption[] = []
    for (const entry of configuredPlayers.value) {
      if (!seen.has(entry.option.id)) {
        seen.add(entry.option.id)
        list.push(entry.option)
      }
    }
    return list
  })

  watch(
    selectedPlayers,
    (list) => {
      const currentIds = new Set(list.map((player) => player.id))
      for (const player of list) {
        if (tracked[player.id] === undefined) tracked[player.id] = true
      }
      for (const key of Object.keys(tracked)) {
        const id = Number(key)
        if (!currentIds.has(id)) delete tracked[id]
      }
    },
    { immediate: true },
  )

  function selectedIds(): number[] {
    return configuredPlayers.value.map((entry) => entry.option.id)
  }

  function validateAll(): boolean {
    for (const key of Object.keys(errors)) {
      delete errors[key]
    }

    const requireTeam = (team: MatchTeamSide, slots: number[]) => {
      for (const slot of slots) {
        if (!selectionAt(team, slot)) {
          errors[`${team}${slot}`] = t('matches.validations.required')
        }
      }
    }

    requireTeam('A', teamASlots.value)
    requireTeam('B', teamBSlots.value)

    const ids = selectedIds()
    if (ids.length !== new Set(ids).size) {
      errors.duplicates = t('matches.validations.duplicates')
    }

    return Object.keys(errors).length === 0
  }

  watch(
    [type, teamASelections, teamBSelections],
    () => {
      const expected = slotsForType(type.value).length
      for (let i = expected; i < 3; i++) {
        teamASelections[i] = null
        teamBSelections[i] = null
      }

      for (let i = 0; i < 3; i++) {
        teamARoles[i] = defaultRoleFor(type.value, i + 1)
        teamBRoles[i] = defaultRoleFor(type.value, i + 1)
      }

      validateAll()
    },
    { deep: true, immediate: true },
  )

  function touch(team: MatchTeamSide, slot: number): void {
    touched[`${team}${slot}`] = true
    validateAll()
  }

  function slotError(team: MatchTeamSide, slot: number): string | undefined {
    return errors[`${team}${slot}`]
  }

  function showSlotError(team: MatchTeamSide, slot: number): boolean {
    const key = `${team}${slot}`
    return (attemptedSubmit.value || touched[key]) && !!errors[key]
  }

  const showDuplicateError = computed(
    () => attemptedSubmit.value && errors.duplicates !== undefined,
  )

  const canStart = computed(() => {
    const expectedCount = teamASlots.value.length + teamBSlots.value.length
    return configuredPlayers.value.length === expectedCount && !errors.duplicates
  })

  function onSearch(team: MatchTeamSide, slot: number, query: string): void {
    const key = `${team}${slot}`
    const previous = searchTimers.get(key)
    if (previous) clearTimeout(previous)

    if (query.trim().length < 3) {
      if (team === 'A') teamASuggestions[slot - 1] = []
      else teamBSuggestions[slot - 1] = []
      return
    }

    const timer = window.setTimeout(async () => {
      const list = await playersService.search(query)
      const options = list.map(playerToSearchOption)
      if (team === 'A') teamASuggestions[slot - 1] = options
      else teamBSuggestions[slot - 1] = options
    }, 300)

    searchTimers.set(key, timer)
  }

  function goQuickAdd(team: MatchTeamSide, slot: number): void {
    router.push({ name: 'addPlayer', query: { returnTo: 'newMatch', slot: `${team}${slot}` } })
  }

  function trackedFor(team: MatchTeamSide, slot: number): boolean {
    const sel = selectionAt(team, slot)
    if (!sel) return false
    return tracked[sel.id] === undefined ? true : Boolean(tracked[sel.id])
  }

  function setTrackedFor(team: MatchTeamSide, slot: number, value: boolean): void {
    const sel = selectionAt(team, slot)
    if (!sel) return
    tracked[sel.id] = value
  }

  function roleFor(team: MatchTeamSide, slot: number): PlayerRole {
    return team === 'A' ? teamARoles[slot - 1] : teamBRoles[slot - 1]
  }

  function setRoleFor(team: MatchTeamSide, slot: number, role: PlayerRole): void {
    if (team === 'A') teamARoles[slot - 1] = role
    else teamBRoles[slot - 1] = role
  }

  onMounted(async () => {
    const q = router.currentRoute.value.query as Record<string, string | undefined>
    const newPlayerId = q.newPlayerId ? Number(q.newPlayerId) : undefined
    const slot = q.slot
    if (!newPlayerId || !slot || (slot[0] !== 'A' && slot[0] !== 'B')) return

    try {
      const player: Player = await playersService.getById(newPlayerId)
      const option = playerToSearchOption(player)
      const team = slot[0] as MatchTeamSide
      const pos = Number(slot.substring(1))
      if (team === 'A') teamASelections[pos - 1] = option
      else teamBSelections[pos - 1] = option
    } catch {
      // ignore
    }
  })

  async function submit(): Promise<void> {
    attemptedSubmit.value = true
    for (const slot of teamASlots.value) touched[`A${slot}`] = true
    for (const slot of teamBSlots.value) touched[`B${slot}`] = true

    if (!validateAll()) return

    submitting.value = true
    try {
      const teamA = teamASlots.value.map((slot) => teamASelections[slot - 1]?.id as number)
      const teamB = teamBSlots.value.map((slot) => teamBSelections[slot - 1]?.id as number)
      const trackedPlayers = selectedPlayers.value
        .filter((player) => tracked[player.id] !== false)
        .map((player) => player.id)

      const defaults: DefaultShotTypeDto[] = []
      teamA.forEach((playerId, idx) => {
        defaults.push({ playerId, defaultShotType: roleToShot(teamARoles[idx] ?? 'pointeur') })
      })
      teamB.forEach((playerId, idx) => {
        defaults.push({ playerId, defaultShotType: roleToShot(teamBRoles[idx] ?? 'pointeur') })
      })

      const trimmedTeamAName = teamAName.value.trim()
      const trimmedTeamBName = teamBName.value.trim()

      const { id } = await matchesService.create({
        type: type.value,
        targetScore: DEFAULT_TARGET_SCORE,
        teamA,
        teamB,
        teamAName: trimmedTeamAName || null,
        teamBName: trimmedTeamBName || null,
        statisticsMode: statisticsMode.value,
        trackedPlayers,
        defaultShotTypes: defaults,
      })

      router.push({
        name: 'matchScore',
        params: { id },
        query: {
          type: type.value,
          teamA: teamA.join(','),
          teamB: teamB.join(','),
          statisticsMode: statisticsMode.value,
          tracked: trackedPlayers.join(','),
          defaults: defaults.map((item) => `${item.playerId}:${item.defaultShotType}`).join(','),
        },
      })
    } catch {
      errors['A1'] = errors['A1'] || t('matches.validations.generic')
    } finally {
      submitting.value = false
    }
  }

  return {
    type,
    statisticsMode,
    teamAName,
    teamBName,
    typeOptions,
    modeOptions,
    roleOptions,
    teamASlots,
    teamBSlots,
    teamASelections,
    teamBSelections,
    teamASuggestions,
    teamBSuggestions,
    teamARoles,
    teamBRoles,
    configuredPlayers,
    showRoleConfig,
    canStart,
    submitting,
    attemptedSubmit,
    slotError,
    showSlotError,
    showDuplicateError,
    onSearch,
    touch,
    goQuickAdd,
    trackedFor,
    setTrackedFor,
    roleFor,
    setRoleFor,
    submit,
  }
}
