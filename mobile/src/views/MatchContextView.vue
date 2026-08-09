<template>
  <section class="context">
    <h2>{{ title }}</h2>
    <p class="hint">{{ t('context.hint') }}</p>

    <form class="form" @submit.prevent="onSubmit">
      <label class="field">
        <span>{{ t('context.fields.comment') }}</span>
        <Textarea v-model="form.comment" rows="3" auto-resize />
      </label>

      <label class="field">
        <span>{{ t('context.fields.teamAName') }}</span>
        <InputText v-model="form.teamAName" />
      </label>

      <label class="field">
        <span>{{ t('context.fields.teamBName') }}</span>
        <InputText v-model="form.teamBName" />
      </label>

      <label class="field">
        <span>{{ t('context.fields.nature') }}</span>
        <Dropdown
          v-model="form.nature"
          :options="natureOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('context.placeholders.select')"
          show-clear
        />
      </label>

      <template v-if="form.nature === 'competition'">
        <label class="field">
          <span>{{ t('context.fields.competitionName') }}</span>
          <InputText v-model="form.competitionName" />
        </label>

        <label class="field">
          <span>{{ t('context.fields.competitionStage') }}</span>
          <Dropdown
            v-model="form.competitionStage"
            :options="competitionStageOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('context.placeholders.select')"
            show-clear
          />
        </label>
      </template>

      <label class="field">
        <span>{{ t('context.fields.terrainType') }}</span>
        <Dropdown
          v-model="form.terrainType"
          :options="terrainTypeOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('context.placeholders.select')"
          show-clear
        />
      </label>

      <div class="actions">
        <Button type="submit" :label="t('context.actions.save')" :loading="submitting" />
        <Button
          type="button"
          severity="secondary"
          outlined
          :label="t('context.actions.skip')"
          @click="goBack"
        />
      </div>
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import { useMatchContextOptions } from '../composables/useMatchContextOptions'
import {
  emptyMatchContextForm,
  hasMatchContextData,
  matchContextToForm,
  type MatchContextForm,
} from '../models/MatchContext'
import { matchesService } from '../services/matches'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { natureOptions, competitionStageOptions, terrainTypeOptions } = useMatchContextOptions(t)

const matchId = Number(route.params.id)
const submitting = ref(false)
const hasExistingContext = ref(false)
const form = reactive<MatchContextForm>(emptyMatchContextForm())

const title = computed(() =>
  hasExistingContext.value ? t('context.titleEdit') : t('context.titleAdd'),
)

function toPayload(formValue: MatchContextForm) {
  return {
    comment: formValue.comment.trim() || null,
    teamAName: formValue.teamAName.trim() || null,
    teamBName: formValue.teamBName.trim() || null,
    nature: formValue.nature,
    competitionName: formValue.nature === 'competition' ? formValue.competitionName.trim() || null : null,
    competitionStage: formValue.nature === 'competition' ? formValue.competitionStage : null,
    terrainType: formValue.terrainType,
  }
}

async function load() {
  if (!matchId) {
    router.replace({ name: 'home' })
    return
  }
  try {
    const context = await matchesService.getContext(matchId)
    Object.assign(form, matchContextToForm(context))
    hasExistingContext.value = hasMatchContextData(context)
  } catch {
    router.replace({ name: 'home' })
  }
}

async function onSubmit() {
  submitting.value = true
  try {
    await matchesService.updateContext(matchId, toPayload(form))
    router.push({ name: 'matchSummary', params: { id: matchId } })
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'matchSummary', params: { id: matchId } })
}

onMounted(load)
</script>

<style scoped>
.context { max-width: 560px; margin: 0.75rem auto 1.5rem; display: grid; gap: 0.75rem; }
.hint { opacity: 0.8; margin: 0; font-size: 0.9rem; }
.form { display: grid; gap: 0.75rem; }
.field { display: grid; gap: 0.25rem; }
.actions { display: grid; gap: 0.5rem; margin-top: 0.25rem; }
</style>
