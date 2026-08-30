<template>
  <AutoComplete
    v-model="selected"
    :suggestions="suggestions"
    option-label="label"
    :placeholder="placeholder"
    :invalid="invalid"
    :loading="searching"
    force-selection
    fluid
    :pt="{ input: { autocomplete: 'off' } }"
    @complete="onComplete"
    @item-select="onItemSelect"
    @clear="onClear"
    @blur="emit('blur')"
  >
    <template #option="{ option }">
      <span v-if="option.id === PROVISIONAL_OPTION_ID" class="participant-option participant-option--new">
        <i class="pi pi-user-plus" aria-hidden="true" />
        <span>{{ t('matches.create.addProvisional', { name: option.label }) }}</span>
      </span>
      <span v-else class="participant-option">{{ option.label }}</span>
    </template>
  </AutoComplete>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AutoComplete from 'primevue/autocomplete'
import type { MatchParticipant } from '../../models/MatchDraft'
import { playersService } from '../../services/players'
import { participantFromPlayer, PROVISIONAL_OPTION_ID } from '../../utils/matchParticipants'

const MIN_QUERY_LENGTH = 2
const SEARCH_DEBOUNCE_MS = 300

const props = withDefaults(
  defineProps<{
    modelValue: MatchParticipant | null
    placeholder?: string
    invalid?: boolean
    excludeIds?: number[]
  }>(),
  {
    placeholder: '',
    invalid: false,
    excludeIds: () => [],
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: MatchParticipant | null]
  /** The typed name was picked as a provisional participant: the parent allocates its id. */
  create: [name: string]
  blur: []
}>()

const { t } = useI18n()

const selected = ref<MatchParticipant | null>(props.modelValue)
const suggestions = ref<MatchParticipant[]>([])
const searching = ref(false)
let searchTimer: number | undefined

watch(
  () => props.modelValue,
  (participant) => {
    selected.value = participant
  },
)

function createOption(name: string): MatchParticipant {
  return { id: PROVISIONAL_OPTION_ID, label: name, shortLabel: name }
}

function onComplete(event: { query: string }): void {
  const query = event.query.trim()
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  if (query.length < MIN_QUERY_LENGTH) {
    suggestions.value = []
    searching.value = false
    return
  }

  // The provisional option is offered immediately: adding a name never waits for the network.
  suggestions.value = [createOption(query)]
  searching.value = true

  searchTimer = window.setTimeout(async () => {
    try {
      const players = await playersService.search(query)
      const excluded = new Set(props.excludeIds)
      const found = players
        .map(participantFromPlayer)
        .filter((participant) => !excluded.has(participant.id))
      suggestions.value = [...found, createOption(query)]
    } catch {
      suggestions.value = [createOption(query)]
    } finally {
      searching.value = false
    }
  }, SEARCH_DEBOUNCE_MS)
}

function onItemSelect(event: { value: MatchParticipant }): void {
  if (event.value.id === PROVISIONAL_OPTION_ID) {
    emit('create', event.value.label)
    return
  }
  emit('update:modelValue', event.value)
}

function onClear(): void {
  emit('update:modelValue', null)
}
</script>

<style scoped>
.participant-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.participant-option--new {
  font-weight: 600;
  color: var(--p-primary-color);
}
</style>
