<template>
  <section class="new-match">
    <h2>{{ t('matches.create.title') }}</h2>

    <form class="form" @submit.prevent="onSubmit">
      <div class="teams">
        <div class="team">
          <h3>{{ t('matches.teams.a') }}</h3>
          <div class="player-row" v-for="slot in teamASlots" :key="slot">
            <AutoComplete
              v-model="teamASelections[slot-1]"
              :suggestions="teamASuggestions[slot-1]"
              optionLabel="label"
              :placeholder="t('matches.fields.playerN', { n: slot })"
              @complete="(e) => onSearch('A', slot, e.query)"
              @item-select="() => validateAll()"
              :pt="{ input: { autocomplete: 'off' } }"
            />
            <Button
              class="add"
              icon="pi pi-plus"
              text
              aria-label="add player"
              @click="goQuickAdd('A', slot)"
            />
            <small class="error" v-if="errors[`A${slot}`]">{{ errors[`A${slot}`] }}</small>
          </div>
        </div>

        <div class="team">
          <h3>{{ t('matches.teams.b') }}</h3>
          <div class="player-row" v-for="slot in teamBSlots" :key="slot">
            <AutoComplete
              v-model="teamBSelections[slot-1]"
              :suggestions="teamBSuggestions[slot-1]"
              optionLabel="label"
              :placeholder="t('matches.fields.playerN', { n: slot })"
              @complete="(e) => onSearch('B', slot, e.query)"
              @item-select="() => validateAll()"
              :pt="{ input: { autocomplete: 'off' } }"
            />
            <Button
              class="add"
              icon="pi pi-plus"
              text
              aria-label="add player"
              @click="goQuickAdd('B', slot)"
            />
            <small class="error" v-if="errors[`B${slot}`]">{{ errors[`B${slot}`] }}</small>
          </div>
        </div>
      </div>

      <div class="info">
        <label class="field">
          <span>{{ t('matches.fields.type') }}</span>
          <Dropdown v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" />
        </label>

        <label class="field">
          <span>{{ t('matches.fields.target') }}</span>
          <InputText type="number" min="1" v-model.number="targetScore" />
        </label>
      </div>

      <div class="actions">
        <Button type="submit" class="start" :label="t('matches.actions.start')" :disabled="submitting || !isValid" />
        <Button type="button" severity="secondary" outlined :label="t('matches.actions.cancel')" @click="onCancel" />
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
import { playersService, type Player } from '../services/players'
import { matchesService, type MatchType } from '../services/matches'

const { t } = useI18n()
const router = useRouter()

const type = ref<MatchType>('doublette')
const targetScore = ref<number>(13)

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
const submitting = ref(false)

function playerToOption(p: Player): { id: number; label: string } {
  const name = `${p.firstName} ${p.lastName}`.trim()
  return { id: p.id, label: p.nickname ? `${p.nickname} (${name})` : name }
}

async function onSearch(team: 'A' | 'B', slot: number, q: string) {
  const list = await playersService.search(q)
  const options = list.map(playerToOption)
  if (team === 'A') teamASuggestions[slot - 1] = options
  else teamBSuggestions[slot - 1] = options
}

function goQuickAdd(team: 'A' | 'B', slot: number) {
  router.push({ name: 'addPlayer', query: { returnTo: 'newMatch', slot: `${team}${slot}` } })
}

function selectedIds(): number[] {
  const a = teamASelections.filter(Boolean).map((o) => (o as { id: number }).id)
  const b = teamBSelections.filter(Boolean).map((o) => (o as { id: number }).id)
  return [...a, ...b]
}

const isValid = computed(() => validateAll())

function validateAll(): boolean {
  // clear
  for (const k of Object.keys(errors)) delete errors[k]

  const expected = type.value === 'tete_a_tete' ? 1 : type.value === 'doublette' ? 2 : 3

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

  // ensure correct count per team
  if (teamASlots.value.length !== expected || teamBSlots.value.length !== expected) {
    // slots arrays already driven by type; no additional message needed
  }

  return Object.keys(errors).length === 0
}

watch(type, () => {
  // Reset selections beyond expected slots
  const expected = type.value === 'tete_a_tete' ? 1 : type.value === 'doublette' ? 2 : 3
  for (let i = expected; i < 3; i++) {
    teamASelections[i] = null
    teamBSelections[i] = null
  }
  validateAll()
})

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
  if (!validateAll()) return
  submitting.value = true
  try {
    const expected = type.value === 'tete_a_tete' ? 1 : type.value === 'doublette' ? 2 : 3
    const teamA = teamASelections.slice(0, expected).map((o) => (o as { id: number }).id)
    const teamB = teamBSelections.slice(0, expected).map((o) => (o as { id: number }).id)

    const { id } = await matchesService.create({ type: type.value, targetScore: targetScore.value, teamA, teamB })
    router.push({ name: 'matchScore', params: { id } })
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
.new-match { max-width: 520px; margin: 1rem auto 2rem; display: grid; gap: 1rem; }
.form { display: grid; gap: 1rem; }
.teams { display: grid; gap: 1rem; grid-template-columns: 1fr; }
.team { display: grid; gap: 0.5rem; padding: 0.5rem; border: 1px solid #eee; border-radius: 8px; }
.player-row { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 0.25rem; }
.add { justify-self: end; }
.info { display: grid; gap: 0.75rem; }
.field { display: grid; gap: 0.25rem; }
.actions { display: grid; gap: 0.5rem; }
.error { color: #dc2626; font-size: 0.8rem; }
@media (min-width: 640px) { .teams { grid-template-columns: repeat(2, 1fr); gap: 1rem; } }
</style>
