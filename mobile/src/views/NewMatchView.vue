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

      <section class="matchup">
        <article class="team-panel team-panel--a">
          <header class="team-head">
            <span class="team-badge">{{ t('matches.teams.aShort') }}</span>
            <InputText
              v-model="teamAName"
              :placeholder="t('matches.create.teamNameHint')"
              maxlength="100"
              class="team-name-input"
            />
          </header>

          <div v-for="slot in teamASlots" :key="`A${slot}`" class="player-slot">
            <div class="player-search-row">
              <AutoComplete
                v-model="teamASelections[slot - 1]"
                :suggestions="teamASuggestions[slot - 1]"
                option-label="label"
                :placeholder="t('matches.fields.playerN', { n: slot })"
                class="player-search"
                fluid
                @complete="(event) => onSearch('A', slot, event.query)"
                @item-select="() => touch('A', slot)"
                @blur="() => touch('A', slot)"
                :pt="{ input: { autocomplete: 'off' } }"
                :invalid="showSlotError('A', slot)"
              />
              <Button
                type="button"
                class="add-player-btn"
                icon="pi pi-plus"
                rounded
                outlined
                :aria-label="t('matches.create.addPlayer')"
                @click="goQuickAdd('A', slot)"
              />
            </div>
            <div v-if="teamASelections[slot - 1]" class="player-slot-options">
              <div v-if="showRoleConfig" class="player-option">
                <span class="player-option-label">{{ t('matches.create.role') }}</span>
                <SelectButton
                  :model-value="roleFor('A', slot)"
                  :options="roleOptions"
                  option-label="label"
                  option-value="value"
                  size="small"
                  class="role-picker"
                  @update:model-value="(value) => setRoleFor('A', slot, value as PlayerRole)"
                />
              </div>
              <label class="player-option track-toggle">
                <span class="player-option-label">{{ t('matches.create.trackPlayer') }}</span>
                <ToggleSwitch
                  :model-value="trackedFor('A', slot)"
                  @update:model-value="(value) => setTrackedFor('A', slot, value)"
                />
              </label>
            </div>
            <small v-if="showSlotError('A', slot)" class="field-error">{{ slotError('A', slot) }}</small>
          </div>
        </article>

        <div class="versus" aria-hidden="true">{{ t('matches.create.versus') }}</div>

        <article class="team-panel team-panel--b">
          <header class="team-head">
            <span class="team-badge">{{ t('matches.teams.bShort') }}</span>
            <InputText
              v-model="teamBName"
              :placeholder="t('matches.create.teamNameHint')"
              maxlength="100"
              class="team-name-input"
            />
          </header>

          <div v-for="slot in teamBSlots" :key="`B${slot}`" class="player-slot">
            <div class="player-search-row">
              <AutoComplete
                v-model="teamBSelections[slot - 1]"
                :suggestions="teamBSuggestions[slot - 1]"
                option-label="label"
                :placeholder="t('matches.fields.playerN', { n: slot })"
                class="player-search"
                fluid
                @complete="(event) => onSearch('B', slot, event.query)"
                @item-select="() => touch('B', slot)"
                @blur="() => touch('B', slot)"
                :pt="{ input: { autocomplete: 'off' } }"
                :invalid="showSlotError('B', slot)"
              />
              <Button
                type="button"
                class="add-player-btn"
                icon="pi pi-plus"
                rounded
                outlined
                :aria-label="t('matches.create.addPlayer')"
                @click="goQuickAdd('B', slot)"
              />
            </div>
            <div v-if="teamBSelections[slot - 1]" class="player-slot-options">
              <div v-if="showRoleConfig" class="player-option">
                <span class="player-option-label">{{ t('matches.create.role') }}</span>
                <SelectButton
                  :model-value="roleFor('B', slot)"
                  :options="roleOptions"
                  option-label="label"
                  option-value="value"
                  size="small"
                  class="role-picker"
                  @update:model-value="(value) => setRoleFor('B', slot, value as PlayerRole)"
                />
              </div>
              <label class="player-option track-toggle">
                <span class="player-option-label">{{ t('matches.create.trackPlayer') }}</span>
                <ToggleSwitch
                  :model-value="trackedFor('B', slot)"
                  @update:model-value="(value) => setTrackedFor('B', slot, value)"
                />
              </label>
            </div>
            <small v-if="showSlotError('B', slot)" class="field-error">{{ slotError('B', slot) }}</small>
          </div>
        </article>
      </section>

      <p v-if="showDuplicateError" class="form-banner" role="alert">{{ t('matches.validations.duplicates') }}</p>

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
          @click="abandonAndClose"
        />
        <Button :label="t('matches.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AutoComplete from 'primevue/autocomplete'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import ToggleSwitch from 'primevue/toggleswitch'
import PageHeader from '../components/layout/PageHeader.vue'
import { draftScore, useMatchDraftResume } from '../composables/useMatchDraftResume'
import { useNewMatchSetup } from '../composables/useNewMatchSetup'
import type { PlayerRole } from '../models/Match'

const { t } = useI18n()
const { draft, resume, abandon } = useMatchDraftResume()
const resumeDialog = ref(draft.value !== null)

const currentScore = computed(() => {
  if (!draft.value) return { scoreA: 0, scoreB: 0 }
  return draftScore(draft.value)
})

function resumeCurrent(): void {
  resumeDialog.value = false
  resume()
}

function abandonAndClose(): void {
  abandon()
  resumeDialog.value = false
}

const {
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
  showRoleConfig,
  canStart,
  submitting,
  showDuplicateError,
  slotError,
  showSlotError,
  onSearch,
  touch,
  goQuickAdd,
  trackedFor,
  setTrackedFor,
  roleFor,
  setRoleFor,
  submit,
} = useNewMatchSetup()
</script>

<style scoped>
.new-match {
  padding-bottom: calc(env(safe-area-inset-bottom, 0px) + var(--app-space-xl));
  min-width: 0;
}

.setup-form {
  display: grid;
  gap: var(--app-space-xl);
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

.matchup {
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}

.team-panel {
  padding: var(--app-space-md);
  border-radius: var(--app-radius);
  background: var(--app-surface);
  border: 1px solid var(--app-border);
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}

.team-panel--a {
  border-left: 4px solid #22c55e;
}

.team-panel--b {
  border-left: 4px solid #3b82f6;
}

.team-head {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: center;
  gap: var(--app-space-sm);
}

.team-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.75rem;
  height: 1.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  flex-shrink: 0;
}

.team-panel--a .team-badge {
  background: #ecfdf3;
  color: #15803d;
}

.team-panel--b .team-badge {
  background: #eff6ff;
  color: #1d4ed8;
}

.team-name-input {
  width: 100%;
  min-width: 0;
  font-size: 0.875rem;
}

.versus {
  justify-self: center;
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--app-text-subtle);
}

.player-slot {
  display: grid;
  gap: var(--app-space-xs);
  min-width: 0;
}

.player-slot-options {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-sm) 0 0;
  border-top: 1px dashed var(--app-border);
}

.player-option {
  display: grid;
  gap: 0.375rem;
}

.player-option-label {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--app-text-subtle);
}

.role-picker :deep(.p-selectbutton) {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
}

.role-picker :deep(.p-togglebutton) {
  flex: 1 1 5.5rem;
  justify-content: center;
  font-size: 0.75rem;
  padding: 0.375rem 0.5rem;
  min-height: 2.25rem;
  white-space: normal;
  line-height: 1.15;
}

.track-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-md);
}

.player-search-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: var(--app-space-sm);
  align-items: center;
}

.player-search {
  min-width: 0;
}

.player-search :deep(.p-autocomplete) {
  width: 100%;
  min-width: 0;
}

.player-search :deep(.p-autocomplete-input) {
  width: 100%;
  min-width: 0;
}

.add-player-btn {
  width: var(--app-touch-min);
  height: var(--app-touch-min);
  min-width: var(--app-touch-min);
  min-height: var(--app-touch-min);
  padding: 0;
  flex-shrink: 0;
}

.field-error {
  color: #c24141;
  font-size: 0.75rem;
  padding-left: 0.125rem;
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
