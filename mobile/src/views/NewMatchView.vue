<template>
  <PageHeader :title="t('matches.create.title')" :back-to="{ name: 'home' }" />
  <section class="new-match">
    <form class="app-form" @submit.prevent="onSubmit">
      <div class="teams">
        <div class="team app-card">
          <h3>{{ t('matches.teams.a') }}</h3>
          <div class="player-row" v-for="slot in teamASlots" :key="slot">
            <div>
              <AutoComplete
                  v-model="teamASelections[slot-1]"
                  :suggestions="teamASuggestions[slot-1]"
                  optionLabel="label"
                  :placeholder="t('matches.fields.playerN', { n: slot })"
                  @complete="(e) => onSearch('A', slot, e.query)"
                  @item-select="() => touch('A', slot)"
                  @blur="() => touch('A', slot)"
                  :pt="{ input: { autocomplete: 'off' } }"
                  :invalid="touched[`A${slot}`] && !!errors[`A${slot}`]"
              />
              <Button
                  class="add"
                  icon="pi pi-plus"
                  text
                  aria-label="add player"
                  @click="goQuickAdd('A', slot)"
              />
            </div>
            <div class="player-options">
              <ToggleSwitch
                  :modelValue="trackedFor('A', slot)"
                  @update:modelValue="(v) => setTrackedFor('A', slot, v)"
                  :disabled="!teamASelections[slot-1]"
              />
              <SelectButton
                  v-model="teamARoles[slot-1]"
                  :options="roleOptions"
                  optionLabel="label"
                  optionValue="value"
                  :disabled="!teamASelections[slot-1]"
                  class="role"
              />
            </div>
          </div>
        </div>

        <div class="team app-card">
          <h3>{{ t('matches.teams.b') }}</h3>
          <div class="player-row" v-for="slot in teamBSlots" :key="slot">
            <div>
              <AutoComplete
                  v-model="teamBSelections[slot-1]"
                  :suggestions="teamBSuggestions[slot-1]"
                  optionLabel="label"
                  :placeholder="t('matches.fields.playerN', { n: slot })"
                  @complete="(e) => onSearch('B', slot, e.query)"
                  @item-select="() => touch('B', slot)"
                  @blur="() => touch('B', slot)"
                  :pt="{ input: { autocomplete: 'off' } }"
                  :invalid="touched[`B${slot}`] && !!errors[`B${slot}`]"
              />
              <Button
                  class="add"
                  icon="pi pi-plus"
                  text
                  aria-label="add player"
                  @click="goQuickAdd('B', slot)"
              />
            </div>
            <div class="player-options">
              <ToggleSwitch
                  :modelValue="trackedFor('B', slot)"
                  @update:modelValue="(v) => setTrackedFor('B', slot, v)"
                  :disabled="!teamBSelections[slot-1]"
              />
              <SelectButton
                  v-model="teamBRoles[slot-1]"
                  :options="roleOptions"
                  optionLabel="label"
                  optionValue="value"
                  :disabled="!teamBSelections[slot-1]"
                  class="role"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="info app-card">
        <label class="app-field">
          <span>{{ t('matches.fields.type') }}</span>
          <Dropdown v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" />
        </label>

        <label class="app-field">
          <span>{{ t('matches.fields.target') }}</span>
          <InputText type="number" min="1" v-model.number="targetScore" />
        </label>
      </div>

      <div class="stats app-card">
        <h3>{{ t('matches.stats.sectionTitle') }}</h3>

        <div class="mode">
          <span class="mode-label">{{ t('matches.stats.mode.title') }}</span>
          <SelectButton
            v-model="statisticsMode"
            :options="modeOptions"
            optionLabel="label"
            optionValue="value"
          />
        </div>
      </div>

      <div class="app-actions">
        <Button type="submit" class="start w-full" :label="t('matches.actions.start')" :disabled="submitting || !isValid" />
        <Button type="button" severity="secondary" outlined class="w-full" :label="t('matches.actions.cancel')" @click="onCancel" />
      </div>
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AutoComplete from 'primevue/autocomplete'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import ToggleSwitch from 'primevue/toggleswitch'
import { playersService } from '../services/players'
import { matchesService } from '../services/matches'
import type { Player } from '../models/Player'
import type { MatchType, StatisticsMode, PlayerRole, ShotType } from '../models/Match'
import type { DefaultShotTypeDto } from '../dto/match/CreateMatchRequest'
import PageHeader from '../components/layout/PageHeader.vue'

const { t } = useI18n()
const router = useRouter()

const type = ref<MatchType>('doublette')
const targetScore = ref<number>(13)
const statisticsMode = ref<StatisticsMode>('standard')
// tracked[playerId] = true if stats tracked for that player
const tracked = reactive<Record<number, boolean>>({})

const modeOptions = computed(() => [
  { label: t('matches.stats.modes.standard'), value: 'standard' },
  { label: t('matches.stats.modes.simple'), value: 'simple' },
])

// Role selection per player within a team
const roleOptions = computed(() => {
  if (type.value === 'tete_a_tete') {
    return [];
  }

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

const teamARoles = reactive<PlayerRole[]>(['pointeur', 'tireur', 'tireur'])
const teamBRoles = reactive<PlayerRole[]>(['pointeur', 'tireur', 'tireur'])

const typeOptions = computed(() => [
  { label: t('matches.types.teteATete'), value: 'tete_a_tete' },
  { label: t('matches.types.doublette'), value: 'doublette' },
  { label: t('matches.types.triplette'), value: 'triplette' },
])

const teamASlots = computed(() => (type.value === 'tete_a_tete' ? [1] : type.value === 'doublette' ? [1, 2] : [1, 2, 3]))
const teamBSlots = computed(() => (type.value === 'tete_a_tete' ? [1] : type.value === 'doublette' ? [1, 2] : [1, 2, 3]))

// Selections store simple objects { id, label }
const teamASelections = reactive<Array<{ id: number; label: string } | null>>([null, null, null])
const teamBSelections = reactive<Array<{ id: number; label: string } | null>>([null, null, null])

const teamASuggestions = reactive<Array<Array<{ id: number; label: string }>>>([[], [], []])
const teamBSuggestions = reactive<Array<Array<{ id: number; label: string }>>>([[], [], []])

const errors = reactive<Record<string, string>>({})
const touched = reactive<Record<string, boolean>>({})
const submitting = ref(false)
const searchTimers = new Map<string, number>()

function playerToOption(p: Player): { id: number; label: string } {
  const name = `${p.firstName} ${p.lastName}`.trim()
  return { id: p.id, label: p.nickname ? `${p.nickname} (${name})` : name }
}

function onSearch(team: 'A' | 'B', slot: number, q: string) {
  const key = `${team}${slot}`

  const previous = searchTimers.get(key)
  if (previous) {
    clearTimeout(previous)
  }

  if (q.trim().length < 3) {
    if (team === 'A') {
      teamASuggestions[slot - 1] = []
    } else {
      teamBSuggestions[slot - 1] = []
    }
    return
  }

  const timer = window.setTimeout(async () => {
    const list = await playersService.search(q)
    const options = list.map(playerToOption)

    if (team === 'A') {
      teamASuggestions[slot - 1] = options
    } else {
      teamBSuggestions[slot - 1] = options
    }
  }, 300)

  searchTimers.set(key, timer)
}

function goQuickAdd(team: 'A' | 'B', slot: number) {
  router.push({ name: 'addPlayer', query: { returnTo: 'newMatch', slot: `${team}${slot}` } })
}

function selectionAt(team: 'A' | 'B', slot: number): { id: number; label: string } | null {
  return team === 'A' ? teamASelections[slot - 1] : teamBSelections[slot - 1]
}

function trackedFor(team: 'A' | 'B', slot: number): boolean {
  const sel = selectionAt(team, slot)
  if (!sel) return false
  const v = tracked[sel.id]
  return v === undefined ? true : Boolean(v)
}

function setTrackedFor(team: 'A' | 'B', slot: number, value: boolean): void {
  const sel = selectionAt(team, slot)
  if (!sel) return
  tracked[sel.id] = Boolean(value)
}

function selectedIds(): number[] {
  const a = teamASelections.filter(Boolean).map((o) => (o as { id: number }).id)
  const b = teamBSelections.filter(Boolean).map((o) => (o as { id: number }).id)
  return [...a, ...b]
}

const isValid = computed(() => Object.keys(errors).length === 0)

// Computed list of selected players as simple objects with id+label for UI (for tracked toggles)
const selectedPlayers = computed(() => {
  const opts: Array<{ id: number; label: string }> = []
  const expected = type.value === 'tete_a_tete' ? 1 : type.value === 'doublette' ? 2 : 3
  const a = teamASelections.slice(0, expected).filter(Boolean) as Array<{ id: number; label: string }>
  const b = teamBSelections.slice(0, expected).filter(Boolean) as Array<{ id: number; label: string }>
  for (const o of [...a, ...b]) {
    if (!opts.find((x) => x.id === o.id)) {
      opts.push({ id: o.id, label: o.label })
    }
  }
  return opts
})

// Keep tracked map in sync with selected players
watch(
  selectedPlayers,
  (list) => {
    const currentIds = new Set(list.map((p) => p.id))
    // Add new players default ON
    for (const p of list) {
      if (tracked[p.id] === undefined) tracked[p.id] = true
    }
    // Remove players no longer selected
    Object.keys(tracked).forEach((k) => {
      const id = Number(k)
      if (!currentIds.has(id)) {
        delete tracked[id]
      }
    })
  },
  { deep: true, immediate: true }
)

function touch(team: 'A' | 'B', slot: number) {
  touched[`${team}${slot}`] = true
  validateAll()
}

function validateAll(): boolean {
  Object.keys(errors).forEach((k) => {
    delete errors[k]
  })

  const req = (team: 'A' | 'B', slots: number[]) => {
    for (const s of slots) {
      const val = team === 'A' ? teamASelections[s - 1] : teamBSelections[s - 1]
      if (!val) {
        errors[`${team}${s}`] = t('matches.validations.required')
      }
    }
  }
  req('A', teamASlots.value)
  req('B', teamBSlots.value)

  // duplicates
  const ids = selectedIds()
  const unique = new Set(ids)
  if (ids.length !== unique.size) {
    // mark a generic error on first slot of each team
    errors['A1'] = errors['A1'] || t('matches.validations.duplicates')
    errors['B1'] = errors['B1'] || t('matches.validations.duplicates')
  }

  return Object.keys(errors).length === 0
}

watch(
    [type, teamASelections, teamBSelections],
    () => {
      const expected =
          type.value === 'tete_a_tete'
              ? 1
              : type.value === 'doublette'
                  ? 2
                  : 3

      // reset selections beyond expected
      for (let i = expected; i < 3; i++) {
        teamASelections[i] = null
        teamBSelections[i] = null
      }

      // set default roles per type
      const defaultsFor = (pos: number): PlayerRole => {
        if (type.value === 'doublette') return pos === 2 ? 'tireur' : 'pointeur'
        if (type.value === 'triplette') return pos === 3 ? 'tireur' : 'pointeur'
        return 'pointeur'
      }
      for (let i = 0; i < 3; i++) {
        teamARoles[i] = teamARoles[i] ?? defaultsFor(i + 1)
        teamBRoles[i] = teamBRoles[i] ?? defaultsFor(i + 1)
      }

      validateAll()
    },
    {
      deep: true,
      immediate: true,
    }
)

onMounted(async () => {
  const q = router.currentRoute.value.query as Record<string, string | undefined>
  const newPlayerId = q.newPlayerId ? Number(q.newPlayerId) : undefined
  const slot = q.slot
  if (newPlayerId && slot && (slot.startsWith('A') || slot.startsWith('B'))) {
    try {
      const p = await playersService.getById(newPlayerId)
      const opt = playerToOption(p)
      const team = slot[0] as 'A' | 'B'
      const pos = Number(slot.substring(1))
      if (team === 'A') teamASelections[pos - 1] = opt
      else teamBSelections[pos - 1] = opt
    } catch {
      // ignore
    }
  }
})

async function onSubmit() {
  teamASlots.value.forEach((slot) => {
    touched[`A${slot}`] = true
  })

  teamBSlots.value.forEach((slot) => {
    touched[`B${slot}`] = true
  })

  if (!validateAll()) {
    return
  }
  submitting.value = true
  try {
    const expected = type.value === 'tete_a_tete' ? 1 : type.value === 'doublette' ? 2 : 3
    const teamA = teamASelections.slice(0, expected).map((o) => (o as { id: number }).id)
    const teamB = teamBSelections.slice(0, expected).map((o) => (o as { id: number }).id)

    const trackedPlayers = selectedPlayers.value.filter((p) => tracked[p.id] !== false).map((p) => p.id)
    // Build default shot types from selected roles
    const toShot = (r: PlayerRole): ShotType => (r === 'tireur' ? 'tir' : 'point')
    const defaults: DefaultShotTypeDto[] = []
    teamA.forEach((pid, idx) => defaults.push({ playerId: pid, defaultShotType: toShot(teamARoles[idx] ?? 'pointeur') }))
    teamB.forEach((pid, idx) => defaults.push({ playerId: pid, defaultShotType: toShot(teamBRoles[idx] ?? 'pointeur') }))

    const { id } = await matchesService.create({
      type: type.value,
      targetScore: targetScore.value,
      teamA,
      teamB,
      statisticsMode: statisticsMode.value,
      trackedPlayers,
      defaultShotTypes: defaults,
    })
    router.push({
      name: 'matchScore',
      params: { id },
      query: {
        type: type.value,
        targetScore: String(targetScore.value),
        teamA: teamA.join(','),
        teamB: teamB.join(','),
        statisticsMode: statisticsMode.value,
        tracked: trackedPlayers.join(','),
        defaults: defaults.map((d) => `${d.playerId}:${d.defaultShotType}`).join(','),
      },
    })
  } catch (e) {
    // naive mapping of backend errors (display on first slot)
    errors['A1'] = errors['A1'] || t('matches.validations.generic')
  } finally {
    submitting.value = false
  }
}

function onCancel() {
  router.push({ name: 'home' })
}
</script>

<style scoped>
.new-match { display: grid; gap: var(--app-space-md); }
.teams { display: flex; flex-direction: column; gap: var(--app-space-md); }
.team { display: grid; gap: var(--app-space-sm); padding: var(--app-space-md); }
.info, .stats { padding: var(--app-space-md); display: grid; gap: var(--app-space-md); }
.player-row {
  display: flex;
  gap: 8px;
  flex-direction: column;
  align-content: flex-start;
  align-items: flex-start;
}
.player-options {
  gap: 16px;
  display: flex;
  align-items: center;
}
.add { justify-self: end; }
.info { display: grid; gap: 0.75rem; }
.field { display: grid; gap: 0.25rem; }
.actions { display: grid; gap: 0.5rem; }
.error { color: #dc2626; font-size: 0.8rem; }
.mode { display: flex; align-items: center; gap: 16px; }
</style>
