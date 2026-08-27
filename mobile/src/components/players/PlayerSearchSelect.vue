<template>
  <div class="player-search">
    <label v-if="label" class="app-field">
      <span>{{ label }}</span>
      <AutoComplete
        v-model="selectedOption"
        :suggestions="suggestions"
        option-label="label"
        :placeholder="placeholder"
        force-selection
        fluid
        @complete="onComplete"
        @item-select="onItemSelect"
        @clear="onClear"
      />
    </label>
    <AutoComplete
      v-else
      v-model="selectedOption"
      :suggestions="suggestions"
      option-label="label"
      :placeholder="placeholder"
      force-selection
      fluid
      @complete="onComplete"
      @item-select="onItemSelect"
      @clear="onClear"
    />
    <p v-if="showEmptyHint && emptyHint" class="empty-hint">{{ emptyHint }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import AutoComplete from 'primevue/autocomplete'
import { accountService } from '../../services/account'
import { playersService } from '../../services/players'
import type { Player } from '../../models/Player'
import { playerToSearchOption, type PlayerSearchOption } from '../../composables/usePlayerSearch'

const props = withDefaults(
  defineProps<{
    modelValue: Player | null
    label?: string
    placeholder?: string
    emptyHint?: string
    unlinkedOnly?: boolean
    authenticatedSearch?: boolean
    minQueryLength?: number
    searchPlayers?: (query: string) => Promise<Player[]>
  }>(),
  {
    label: undefined,
    placeholder: '',
    emptyHint: undefined,
    unlinkedOnly: false,
    authenticatedSearch: false,
    minQueryLength: 3,
    searchPlayers: undefined,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: Player | null]
  empty: []
}>()

const suggestions = ref<PlayerSearchOption[]>([])
const selectedOption = ref<PlayerSearchOption | null>(null)
const lastResults = ref<Player[]>([])
const showEmptyHint = ref(false)
let searchTimer: number | undefined

watch(
  () => props.modelValue,
  (player) => {
    selectedOption.value = player ? playerToSearchOption(player) : null
  },
  { immediate: true },
)

async function searchPlayers(q: string): Promise<Player[]> {
  if (props.searchPlayers) {
    return props.searchPlayers(q)
  }
  if (props.authenticatedSearch) {
    return accountService.searchUnlinkedPlayers(q)
  }
  return playersService.search(q, { unlinkedOnly: props.unlinkedOnly })
}

function onComplete(event: { query: string }) {
  const q = event.query.trim()
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  if (q.length < props.minQueryLength) {
    suggestions.value = []
    lastResults.value = []
    showEmptyHint.value = false
    return
  }

  searchTimer = window.setTimeout(async () => {
    const list = await searchPlayers(q)
    lastResults.value = list
    suggestions.value = list.map(playerToSearchOption)
    showEmptyHint.value = list.length === 0
    if (list.length === 0) {
      emit('empty')
    }
  }, 300)
}

function onItemSelect(event: { value: PlayerSearchOption }) {
  const player = lastResults.value.find((item) => item.id === event.value.id) ?? null
  emit('update:modelValue', player)
}

function onClear() {
  emit('update:modelValue', null)
  showEmptyHint.value = false
}
</script>

<style scoped>
.player-search {
  display: grid;
  gap: var(--app-space-xs);
}

.empty-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}
</style>
