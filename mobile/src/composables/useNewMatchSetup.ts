import { computed, reactive, ref, watch, type ComputedRef, type Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { saveMatchDraft } from '../services/matchDraftStorage'
import { useAuthStore } from '../stores/auth'
import { useGuestStore } from '../stores/guest'
import type { MatchParticipant, MatchSetup } from '../models/MatchDraft'
import {
  DEFAULT_TARGET_SCORE,
  type MatchType,
  type PlayerRole,
  type ShotType,
  type StatisticsMode,
} from '../models/Match'
import { defaultRoleFor, roleToShot } from '../utils/matchRoles'
import { nextProvisionalId, emptySlotsToFill, participantFromPlayer, provisionalParticipant } from '../utils/matchParticipants'
import { normalizeOpeningScore } from '../utils/matchScore'

export type MatchTeamSide = 'A' | 'B'

export interface NewMatchPlayerEntry {
  team: MatchTeamSide
  slot: number
  participant: MatchParticipant
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
  teamASelections: Array<MatchParticipant | null>
  teamBSelections: Array<MatchParticipant | null>
  teamARoles: PlayerRole[]
  teamBRoles: PlayerRole[]
  configuredPlayers: ComputedRef<NewMatchPlayerEntry[]>
  showRoleConfig: ComputedRef<boolean>
  canStart: ComputedRef<boolean>
  submitting: Ref<boolean>
  attemptedSubmit: Ref<boolean>
  formError: Ref<string>
  selfParticipant: ComputedRef<MatchParticipant | null>
  canAddSelf: ComputedRef<boolean>
  slotError: (team: MatchTeamSide, slot: number) => string | undefined
  showSlotError: (team: MatchTeamSide, slot: number) => boolean
  showDuplicateError: ComputedRef<boolean>
  excludedIdsFor: (team: MatchTeamSide, slot: number) => number[]
  teamNamePlaceholder: (team: MatchTeamSide) => string
  select: (team: MatchTeamSide, slot: number, participant: MatchParticipant | null) => void
  addProvisional: (team: MatchTeamSide, slot: number, name: string) => void
  addSelf: () => void
  touch: (team: MatchTeamSide, slot: number) => void
  trackedFor: (team: MatchTeamSide, slot: number) => boolean
  setTrackedFor: (team: MatchTeamSide, slot: number, value: boolean) => void
  roleFor: (team: MatchTeamSide, slot: number) => PlayerRole
  setRoleFor: (team: MatchTeamSide, slot: number, role: PlayerRole) => void
  openingScoreA: Ref<number>
  openingScoreB: Ref<number>
  openingScoreError: Ref<string>
  submit: () => void
}

function slotsForType(type: MatchType): number[] {
  if (type === 'tete_a_tete') return [1]
  if (type === 'doublette') return [1, 2]
  return [1, 2, 3]
}

export function useNewMatchSetup(): UseNewMatchSetupReturn {
  const { t } = useI18n()
  const router = useRouter()
  const auth = useAuthStore()
  const guest = useGuestStore()

  const type = ref<MatchType>('doublette')
  const statisticsMode = ref<StatisticsMode>('standard')
  const teamAName = ref('')
  const teamBName = ref('')
  const openingScoreA = ref(0)
  const openingScoreB = ref(0)
  const openingScoreError = ref('')
  const tracked = reactive<Record<number, boolean>>({})
  const submitting = ref(false)
  const attemptedSubmit = ref(false)
  const formError = ref('')

  const teamARoles = reactive<PlayerRole[]>(['pointeur', 'tireur', 'tireur'])
  const teamBRoles = reactive<PlayerRole[]>(['pointeur', 'tireur', 'tireur'])
  const teamASelections = reactive<Array<MatchParticipant | null>>([null, null, null])
  const teamBSelections = reactive<Array<MatchParticipant | null>>([null, null, null])
  const errors = reactive<Record<string, string>>({})
  const touched = reactive<Record<string, boolean>>({})

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

  function selectionsOf(team: MatchTeamSide): Array<MatchParticipant | null> {
    return team === 'A' ? teamASelections : teamBSelections
  }

  function selectionAt(team: MatchTeamSide, slot: number): MatchParticipant | null {
    return selectionsOf(team)[slot - 1] ?? null
  }

  function configuredEntriesFor(team: MatchTeamSide, slots: number[]): NewMatchPlayerEntry[] {
    const selections = selectionsOf(team)
    return slots
      .map((slot) => {
        const participant = selections[slot - 1]
        return participant ? { team, slot, participant } : null
      })
      .filter((entry): entry is NewMatchPlayerEntry => entry !== null)
  }

  const configuredPlayers = computed(() => [
    ...configuredEntriesFor('A', teamASlots.value),
    ...configuredEntriesFor('B', teamBSlots.value),
  ])

  const selectedPlayers = computed(() => {
    const seen = new Set<number>()
    const list: MatchParticipant[] = []
    for (const entry of configuredPlayers.value) {
      if (!seen.has(entry.participant.id)) {
        seen.add(entry.participant.id)
        list.push(entry.participant)
      }
    }
    return list
  })

  const allParticipants = computed(() =>
    [...teamASelections, ...teamBSelections].filter(
      (participant): participant is MatchParticipant => participant !== null,
    ),
  )

  const selfParticipant = computed<MatchParticipant | null>(() => {
    const user = auth.user
    if (!user?.playerId) return null
    const firstName = user.firstName ?? ''
    const lastName = user.lastName ?? ''
    if (`${firstName}${lastName}`.trim() === '') return null
    return participantFromPlayer({
      id: user.playerId,
      firstName,
      lastName,
      nickname: user.nickname ?? '',
      clubId: null,
      clubName: null,
    })
  })

  const canAddSelf = computed(() => {
    const self = selfParticipant.value
    if (!self) return false
    if (allParticipants.value.some((participant) => participant.id === self.id)) return false
    return firstEmptySlot() !== null
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
    return configuredPlayers.value.map((entry) => entry.participant.id)
  }

  function validateAll(): boolean {
    for (const key of Object.keys(errors)) {
      delete errors[key]
    }

    const ids = selectedIds()
    if (ids.length > 0 && ids.length !== new Set(ids).size) {
      errors.duplicates = t('matches.validations.duplicates')
    }

    return Object.keys(errors).length === 0
  }

  watch(
    type,
    (nextType) => {
      const expected = slotsForType(nextType).length
      for (let i = expected; i < 3; i++) {
        teamASelections[i] = null
        teamBSelections[i] = null
      }

      for (let i = 0; i < 3; i++) {
        teamARoles[i] = defaultRoleFor(nextType, i + 1)
        teamBRoles[i] = defaultRoleFor(nextType, i + 1)
      }

      validateAll()
    },
    { immediate: true },
  )

  watch(
    [teamASelections, teamBSelections],
    () => {
      validateAll()
    },
    { deep: true },
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
    return (attemptedSubmit.value || touched[key] === true) && errors[key] !== undefined
  }

  const showDuplicateError = computed(
    () => attemptedSubmit.value && errors.duplicates !== undefined,
  )

  const canStart = computed(() => !errors.duplicates)

  /** Already picked participants must not be offered again in the other slots. */
  function excludedIdsFor(team: MatchTeamSide, slot: number): number[] {
    const current = selectionAt(team, slot)
    return allParticipants.value
      .filter((participant) => participant.id !== current?.id)
      .map((participant) => participant.id)
  }

  /** The team name falls back to the first participant's name, shown as a hint. */
  function teamNamePlaceholder(team: MatchTeamSide): string {
    const first = selectionAt(team, 1)
    return first ? first.shortLabel : t('matches.create.teamNameHint')
  }

  function select(team: MatchTeamSide, slot: number, participant: MatchParticipant | null): void {
    selectionsOf(team)[slot - 1] = participant
    touch(team, slot)
  }

  function addProvisional(team: MatchTeamSide, slot: number, name: string): void {
    const trimmed = name.trim()
    if (trimmed === '') return
    const id = nextProvisionalId(allParticipants.value)
    select(team, slot, provisionalParticipant(id, trimmed))
  }

  function firstEmptySlot(): { team: MatchTeamSide; slot: number } | null {
    for (const slot of teamASlots.value) {
      if (!selectionAt('A', slot)) return { team: 'A', slot }
    }
    for (const slot of teamBSlots.value) {
      if (!selectionAt('B', slot)) return { team: 'B', slot }
    }
    return null
  }

  function addSelf(): void {
    const self = selfParticipant.value
    const target = firstEmptySlot()
    if (!self || !target) return
    select(target.team, target.slot, self)
  }

  function trackedFor(team: MatchTeamSide, slot: number): boolean {
    const selection = selectionAt(team, slot)
    if (!selection) return false
    return tracked[selection.id] === undefined ? true : Boolean(tracked[selection.id])
  }

  function setTrackedFor(team: MatchTeamSide, slot: number, value: boolean): void {
    const selection = selectionAt(team, slot)
    if (!selection) return
    tracked[selection.id] = value
  }

  function roleFor(team: MatchTeamSide, slot: number): PlayerRole {
    const roles = team === 'A' ? teamARoles : teamBRoles
    return roles[slot - 1] ?? 'pointeur'
  }

  function setRoleFor(team: MatchTeamSide, slot: number, role: PlayerRole): void {
    if (team === 'A') teamARoles[slot - 1] = role
    else teamBRoles[slot - 1] = role
  }

  function fillEmptySlotsWithDefaults(): void {
    const pending = emptySlotsToFill(
      teamASlots.value,
      teamBSlots.value,
      (team, slot) => selectionAt(team, slot) !== null,
    )

    for (const { team, slot, letter } of pending) {
      const label = t('matches.create.defaultPlayer', { letter })
      const id = nextProvisionalId(allParticipants.value)
      const participant = provisionalParticipant(id, label)
      selectionsOf(team)[slot - 1] = participant
      tracked[participant.id] = true
    }

    if (pending.length > 0) {
      validateAll()
    }
  }

  function buildSetup(): MatchSetup {
    const teamA = teamASlots.value.map((slot) => selectionAt('A', slot) as MatchParticipant)
    const teamB = teamBSlots.value.map((slot) => selectionAt('B', slot) as MatchParticipant)

    const defaultShotTypes: Record<number, ShotType> = {}
    const startingRoles: Record<number, PlayerRole> = {}
    const applyRoles = (participants: MatchParticipant[], roles: PlayerRole[]) => {
      participants.forEach((participant, index) => {
        const role = roles[index] ?? 'pointeur'
        startingRoles[participant.id] = role
        defaultShotTypes[participant.id] = roleToShot(role)
      })
    }
    applyRoles(teamA, teamARoles)
    applyRoles(teamB, teamBRoles)

    return {
      id: Date.now(),
      type: type.value,
      targetScore: DEFAULT_TARGET_SCORE,
      statisticsMode: statisticsMode.value,
      teamA: teamA.map((participant) => participant.id),
      teamB: teamB.map((participant) => participant.id),
      teamAName: teamAName.value.trim() || null,
      teamBName: teamBName.value.trim() || null,
      trackedPlayers: selectedPlayers.value
        .filter((participant) => tracked[participant.id] !== false)
        .map((participant) => participant.id),
      defaultShotTypes,
      startingRoles,
      participants: [...teamA, ...teamB],
      startedAt: new Date().toISOString(),
    }
  }

  /**
   * Starting a match is entirely local: nothing is sent until the match is finished.
   * See ADR-001.
   */
  function submit(): void {
    attemptedSubmit.value = true
    formError.value = ''
    fillEmptySlotsWithDefaults()

    if (!validateAll()) return

    const scoreA = normalizeOpeningScore(openingScoreA.value, DEFAULT_TARGET_SCORE)
    const scoreB = normalizeOpeningScore(openingScoreB.value, DEFAULT_TARGET_SCORE)
    if (scoreA >= DEFAULT_TARGET_SCORE && scoreB >= DEFAULT_TARGET_SCORE) {
      openingScoreError.value = t('matches.create.inProgress.invalid')
      return
    }
    openingScoreA.value = scoreA
    openingScoreB.value = scoreB
    openingScoreError.value = ''

    submitting.value = true
    try {
      const setup = buildSetup()
      const isGuest = guest.isGuestSession || !auth.isAuthenticated
      if (isGuest && !guest.isGuestSession) {
        guest.enterGuestMode()
      }
      saveMatchDraft(
        setup,
        {
          currentEndIndex: 0,
          ends: [{ index: 1, balls: [], canceled: false }],
          distanceEstimate: null,
          currentRoles: { ...setup.startingRoles },
          substitutions: [],
          openingScoreA: scoreA,
          openingScoreB: scoreB,
        },
        auth.user?.id ?? null,
        { guest: isGuest },
      )

      router.push({ name: 'matchScore', params: { id: setup.id } })
    } catch {
      submitting.value = false
      formError.value = t('matches.validations.generic')
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
    teamARoles,
    teamBRoles,
    configuredPlayers,
    showRoleConfig,
    canStart,
    submitting,
    attemptedSubmit,
    formError,
    selfParticipant,
    canAddSelf,
    slotError,
    showSlotError,
    showDuplicateError,
    excludedIdsFor,
    teamNamePlaceholder,
    select,
    addProvisional,
    addSelf,
    touch,
    trackedFor,
    setTrackedFor,
    roleFor,
    setRoleFor,
    openingScoreA,
    openingScoreB,
    openingScoreError,
    submit,
  }
}
