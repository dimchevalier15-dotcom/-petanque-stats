<template>
  <PageHeader :title="t('matches.create.title')" :subtitle="t('matches.create.subtitle')" :back-to="{ name: 'home' }" />

  <section class="new-match">
    <form class="setup-form" @submit.prevent="submit">
      <section class="type-bar" :aria-label="t('matches.create.typeSection')">
        <SelectButton
          v-model="type"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          class="type-picker"
        />
      </section>

      <div v-if="canAddSelf" class="quick-actions">
        <Button
          type="button"
          size="small"
          outlined
          icon="pi pi-user"
          :label="t('matches.create.addSelf')"
          @click="addSelf"
        />
      </div>

      <section class="matchup">
        <MatchTeamPanel team="A" :setup="setup" />
        <div class="versus" aria-hidden="true">{{ t('matches.create.versus') }}</div>
        <MatchTeamPanel team="B" :setup="setup" />
      </section>

      <p v-if="showDuplicateError" class="form-banner" role="alert">
        {{ t('matches.validations.duplicates') }}
      </p>
      <p v-if="formError" class="form-banner" role="alert">{{ formError }}</p>

      <section class="stats-block app-card app-card--muted">
        <div class="stats-row">
          <span class="stats-label">{{ t('matches.stats.mode.title') }}</span>
          <SelectButton
            v-model="statisticsMode"
            :options="modeOptions"
            option-label="label"
            option-value="value"
            size="small"
            class="mode-picker"
          />
          <small class="stats-hint">{{ statisticsModeHint }}</small>
        </div>
      </section>

      <div class="start-bar">
        <Button
          type="submit"
          class="start-btn"
          :label="t('matches.actions.start')"
          icon="pi pi-play"
          :loading="submitting"
          :disabled="submitting || !canStart"
        />
      </div>
    </form>
  </section>

  <Dialog
    v-model:visible="resumeDialog"
    :modal="true"
    :closable="false"
    :header="t('matches.resume.title')"
    class="match-resume-dialog"
  >
    <div class="resume-content">
      <p>{{ t('matches.resume.message') }}</p>
      <p v-if="draft" class="resume-score">
        {{ t('matches.resume.score', { scoreA: currentScore.scoreA, scoreB: currentScore.scoreB }) }}
      </p>
      <div class="resume-actions">
        <Button
          :label="t('matches.resume.abandon')"
          severity="secondary"
          outlined
          @click="askAbandon"
        />
        <Button :label="t('matches.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
      </div>
    </div>
  </Dialog>

  <Dialog
    v-model:visible="abandonDialog"
    :modal="true"
    :header="t('matches.resume.abandonTitle')"
    class="match-resume-dialog"
  >
    <div class="resume-content">
      <p>{{ t('matches.resume.abandonMessage') }}</p>
      <div class="resume-actions">
        <Button
          :label="t('matches.resume.abandonCancel')"
          severity="secondary"
          outlined
          @click="abandonDialog = false"
        />
        <Button
          :label="t('matches.resume.abandonConfirm')"
          severity="danger"
          icon="pi pi-trash"
          @click="confirmAbandon"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import SelectButton from 'primevue/selectbutton'
import PageHeader from '../components/layout/PageHeader.vue'
import MatchTeamPanel from '../components/match/MatchTeamPanel.vue'
import { draftScore, useMatchDraftResume } from '../composables/useMatchDraftResume'
import { useNewMatchSetup } from '../composables/useNewMatchSetup'

const { t } = useI18n()
const { draft, resume, abandon } = useMatchDraftResume()

const setup = useNewMatchSetup()
const {
  type,
  statisticsMode,
  typeOptions,
  modeOptions,
  canStart,
  canAddSelf,
  submitting,
  formError,
  showDuplicateError,
  addSelf,
  submit,
} = setup

const resumeDialog = ref(draft.value !== null)
const abandonDialog = ref(false)

const currentScore = computed(() => {
  if (!draft.value) return { scoreA: 0, scoreB: 0 }
  return draftScore(draft.value)
})

const statisticsModeHint = computed(() =>
  statisticsMode.value === 'standard'
    ? t('matches.stats.modes.standardHint')
    : t('matches.stats.modes.simpleHint'),
)

function resumeCurrent(): void {
  resumeDialog.value = false
  resume()
}

function askAbandon(): void {
  abandonDialog.value = true
}

/** The draft is the only copy of an ongoing match: losing it must be explicit. */
function confirmAbandon(): void {
  abandon()
  abandonDialog.value = false
  resumeDialog.value = false
}
</script>

<style scoped>
.new-match {
  padding-bottom: calc(env(safe-area-inset-bottom, 0px) + var(--app-space-xl));
  min-width: 0;
}

.setup-form {
  display: grid;
  gap: var(--app-space-lg);
}

.resume-content {
  display: grid;
  gap: var(--app-space-md);
}

.resume-score {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
}

.resume-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.type-bar {
  margin-top: var(--app-space-xs);
}

.type-picker {
  width: 100%;
}

.type-picker :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  width: 100%;
}

.type-picker :deep(.p-togglebutton) {
  justify-content: center;
  min-height: var(--app-touch-min);
  padding: 0.5rem 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: normal;
}

.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--app-space-sm);
  margin-top: calc(-1 * var(--app-space-sm));
}

.matchup {
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}

.versus {
  justify-self: center;
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--app-text-subtle);
}

.form-banner {
  margin: 0;
  padding: var(--app-space-sm) var(--app-space-md);
  border-radius: var(--app-radius-sm);
  background: #fef2f2;
  color: #b91c1c;
  font-size: 0.8125rem;
  font-weight: 600;
}

.stats-block {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-md);
}

.stats-row {
  display: grid;
  gap: var(--app-space-sm);
}

.stats-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.stats-hint {
  color: var(--app-text-subtle);
  font-size: 0.75rem;
  line-height: 1.35;
}

.mode-picker {
  width: 100%;
}

.mode-picker :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  width: 100%;
}

.mode-picker :deep(.p-togglebutton) {
  justify-content: center;
  font-size: 0.75rem;
  min-height: 2.25rem;
}

.start-bar {
  position: sticky;
  bottom: calc(var(--app-nav-h) + env(safe-area-inset-bottom, 0px));
  z-index: 10;
  padding-top: var(--app-space-xs);
  background: linear-gradient(to top, var(--app-bg) 75%, transparent);
}

.start-btn {
  width: 100%;
  min-height: 3rem;
  font-weight: 700;
}
</style>
