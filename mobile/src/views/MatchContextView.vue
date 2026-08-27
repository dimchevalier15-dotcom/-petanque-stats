<template>
  <PageHeader :title="title" :back-to="{ name: 'matchSummary', params: { id: matchId } }" />

  <section class="context">
    <p class="hint">{{ t('context.hint') }}</p>

    <form class="app-form" @submit.prevent="onSubmit">
      <div class="app-card form-card">
        <label class="app-field">
          <span>{{ t('context.fields.playedAt') }}</span>
          <input v-model="form.playedAt" type="date" class="date-input" />
        </label>

        <label class="app-field">
          <span>{{ t('context.fields.comment') }}</span>
          <Textarea v-model="form.comment" rows="3" auto-resize fluid />
        </label>

        <label class="app-field">
          <span>{{ t('context.fields.teamAName') }}</span>
          <InputText v-model="form.teamAName" fluid />
        </label>

        <label class="app-field">
          <span>{{ t('context.fields.teamBName') }}</span>
          <InputText v-model="form.teamBName" fluid />
        </label>

        <label class="app-field">
          <span>{{ t('context.fields.nature') }}</span>
          <Dropdown
            v-model="form.nature"
            :options="natureOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('context.placeholders.select')"
            overlay-class="context-dropdown-overlay"
            scroll-height="40vh"
            show-clear
            fluid
          />
        </label>

        <template v-if="form.nature === 'competition'">
          <label class="app-field">
            <span>{{ t('context.fields.competitionName') }}</span>
            <Dropdown
              v-model="form.competitionSelection"
              :options="competitionOptions"
              option-label="label"
              option-value="value"
              :placeholder="t('context.placeholders.select')"
              overlay-class="context-dropdown-overlay"
              scroll-height="40vh"
              show-clear
              fluid
            />
          </label>

          <label v-if="form.competitionSelection === COMPETITION_OTHER_VALUE" class="app-field">
            <span>{{ t('context.fields.competitionNameOther') }}</span>
            <InputText v-model="form.competitionName" fluid />
          </label>

          <label class="app-field">
            <span>{{ t('context.fields.competitionStage') }}</span>
            <Dropdown
              v-model="form.competitionStage"
              :options="competitionStageOptions"
              option-label="label"
              option-value="value"
              :placeholder="t('context.placeholders.select')"
              overlay-class="context-dropdown-overlay"
              scroll-height="40vh"
              show-clear
              fluid
            />
          </label>
        </template>

        <label class="app-field">
          <span>{{ t('context.fields.terrainType') }}</span>
          <Dropdown
            v-model="form.terrainType"
            :options="terrainTypeOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('context.placeholders.select')"
            overlay-class="context-dropdown-overlay"
            scroll-height="40vh"
            show-clear
            fluid
          />
        </label>
      </div>

      <div class="app-actions">
        <Button type="submit" class="w-full" :label="t('context.actions.save')" :loading="submitting" />
        <Button
          type="button"
          severity="secondary"
          outlined
          class="w-full"
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
import PageHeader from '../components/layout/PageHeader.vue'
import { useMatchContextOptions } from '../composables/useMatchContextOptions'
import {
  COMPETITION_OTHER_VALUE,
  competitionLabel,
  type Competition,
} from '../models/Competition'
import {
  emptyMatchContextForm,
  hasMatchContextData,
  matchContextToForm,
  type MatchContextForm,
} from '../models/MatchContext'
import { competitionsService } from '../services/competitions'
import { matchesService } from '../services/matches'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { natureOptions, competitionStageOptions, terrainTypeOptions } = useMatchContextOptions(t)

const matchId = Number(route.params.id)
const submitting = ref(false)
const hasExistingContext = ref(false)
const competitions = ref<Competition[]>([])
const form = reactive<MatchContextForm>(emptyMatchContextForm())

const competitionOptions = computed(() => {
  const items = competitions.value.map((competition) => ({
    label: competitionLabel(competition),
    value: competition.id,
  }))
  items.push({ label: t('context.competition.other'), value: COMPETITION_OTHER_VALUE })
  return items
})

const title = computed(() =>
  hasExistingContext.value ? t('context.titleEdit') : t('context.titleAdd'),
)

function toPayload(formValue: MatchContextForm) {
  const isCompetition = formValue.nature === 'competition'
  const selectedKnownCompetition =
    isCompetition &&
    formValue.competitionSelection !== null &&
    formValue.competitionSelection !== COMPETITION_OTHER_VALUE

  return {
    comment: formValue.comment.trim() || null,
    teamAName: formValue.teamAName.trim() || null,
    teamBName: formValue.teamBName.trim() || null,
    nature: formValue.nature,
    competitionId: selectedKnownCompetition ? formValue.competitionSelection : null,
    competitionName:
      isCompetition && formValue.competitionSelection === COMPETITION_OTHER_VALUE
        ? formValue.competitionName.trim() || null
        : null,
    competitionStage: isCompetition ? formValue.competitionStage : null,
    terrainType: formValue.terrainType,
    playedAt: formValue.playedAt || null,
  }
}

async function load() {
  if (!matchId) {
    router.replace({ name: 'home' })
    return
  }
  try {
    const [context, competitionList] = await Promise.all([
      matchesService.getContext(matchId),
      competitionsService.list(),
    ])
    competitions.value = competitionList
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
.context {
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}

.hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
}

.form-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}

.form-card .app-field,
.form-card :deep(.p-select),
.form-card :deep(.p-dropdown) {
  min-width: 0;
  max-width: 100%;
}

.form-card :deep(.p-select-label),
.form-card :deep(.p-dropdown-label) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.w-full {
  width: 100%;
}

.date-input {
  width: 100%;
  min-height: 2.75rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  padding: 0.625rem 0.75rem;
  font: inherit;
  color: var(--app-text);
  background: var(--app-surface);
}
</style>

<style>
.context-dropdown-overlay {
  max-width: min(calc(100vw - 1.5rem), var(--app-page-max));
}

.context-dropdown-overlay .p-select-list,
.context-dropdown-overlay .p-dropdown-list,
.context-dropdown-overlay .p-select-items-wrapper,
.context-dropdown-overlay .p-dropdown-items-wrapper {
  max-height: min(40vh, 14rem);
  overflow-x: hidden;
  overflow-y: auto;
}

.context-dropdown-overlay .p-select-option,
.context-dropdown-overlay .p-dropdown-item {
  white-space: normal;
  overflow-wrap: anywhere;
  max-width: 100%;
}
</style>
