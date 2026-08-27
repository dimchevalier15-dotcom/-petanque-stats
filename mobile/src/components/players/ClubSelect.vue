<template>
  <label class="app-field">
    <span>{{ label ?? t('players.fields.club') }}</span>
    <Dropdown
      :model-value="modelValue"
      :options="options"
      option-label="label"
      option-value="id"
      :placeholder="placeholder ?? t('players.placeholders.club')"
      overlay-class="context-dropdown-overlay"
      scroll-height="40vh"
      show-clear
      filter
      :loading="loading"
      :invalid="invalid"
      fluid
      @update:model-value="onUpdate"
    />
    <small v-if="error" class="field-error">{{ error }}</small>
  </label>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Dropdown from 'primevue/dropdown'
import { clubLabel } from '../../models/Club'
import { clubsService } from '../../services/clubs'

defineProps<{
  label?: string
  placeholder?: string
  invalid?: boolean
  error?: string
}>()

const modelValue = defineModel<number | null>({ default: null })

const { t } = useI18n()
const loading = ref(false)
const clubs = ref<Awaited<ReturnType<typeof clubsService.list>>>([])

const options = computed(() =>
  clubs.value.map((club) => ({
    id: club.id,
    label: clubLabel(club),
  })),
)

function onUpdate(value: number | null) {
  modelValue.value = value
}

onMounted(async () => {
  loading.value = true
  try {
    clubs.value = await clubsService.list()
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.field-error {
  color: #c24141;
  font-size: 0.8125rem;
}
</style>
