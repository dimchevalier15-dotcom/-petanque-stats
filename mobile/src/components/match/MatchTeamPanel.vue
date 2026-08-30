<template>
  <article class="team-panel" :class="`team-panel--${team.toLowerCase()}`">
    <header class="team-head">
      <span class="team-badge">{{ t(`matches.teams.${team === 'A' ? 'aShort' : 'bShort'}`) }}</span>
      <InputText
        v-model="teamName"
        :placeholder="setup.teamNamePlaceholder(team)"
        maxlength="100"
        class="team-name-input"
      />
    </header>

    <div v-for="slot in slots" :key="`${team}${slot}`" class="player-slot">
      <MatchParticipantSelect
        :model-value="participantAt(slot)"
        :placeholder="t('matches.fields.playerN', { n: slot })"
        :invalid="setup.showSlotError(team, slot)"
        :exclude-ids="setup.excludedIdsFor(team, slot)"
        @update:model-value="(value) => setup.select(team, slot, value)"
        @create="(name) => setup.addProvisional(team, slot, name)"
        @blur="setup.touch(team, slot)"
      />

      <div v-if="participantAt(slot)" class="player-slot-options">
        <span v-if="isProvisional(slot)" class="provisional-badge">
          <i class="pi pi-user-plus" aria-hidden="true" />
          {{ t('matches.create.provisionalBadge') }}
        </span>

        <div v-if="setup.showRoleConfig.value" class="player-option">
          <span class="player-option-label">{{ t('matches.create.role') }}</span>
          <SelectButton
            :model-value="setup.roleFor(team, slot)"
            :options="setup.roleOptions.value"
            option-label="label"
            option-value="value"
            size="small"
            class="role-picker"
            @update:model-value="(value) => setup.setRoleFor(team, slot, value as PlayerRole)"
          />
        </div>

        <label class="player-option track-toggle">
          <span class="player-option-label">{{ t('matches.create.trackPlayer') }}</span>
          <ToggleSwitch
            :model-value="setup.trackedFor(team, slot)"
            @update:model-value="(value) => setup.setTrackedFor(team, slot, value)"
          />
        </label>
      </div>

      <small v-if="setup.showSlotError(team, slot)" class="field-error">
        {{ setup.slotError(team, slot) }}
      </small>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import ToggleSwitch from 'primevue/toggleswitch'
import MatchParticipantSelect from './MatchParticipantSelect.vue'
import type { MatchTeamSide, UseNewMatchSetupReturn } from '../../composables/useNewMatchSetup'
import type { PlayerRole } from '../../models/Match'
import type { MatchParticipant } from '../../models/MatchDraft'
import { isProvisionalParticipant } from '../../utils/matchParticipants'

const props = defineProps<{
  team: MatchTeamSide
  setup: UseNewMatchSetupReturn
}>()

const { t } = useI18n()

const slots = computed(() =>
  props.team === 'A' ? props.setup.teamASlots.value : props.setup.teamBSlots.value,
)

const selections = computed(() =>
  props.team === 'A' ? props.setup.teamASelections : props.setup.teamBSelections,
)

const teamName = computed({
  get: () => (props.team === 'A' ? props.setup.teamAName.value : props.setup.teamBName.value),
  set: (value: string) => {
    if (props.team === 'A') {
      props.setup.teamAName.value = value
    } else {
      props.setup.teamBName.value = value
    }
  },
})

function participantAt(slot: number): MatchParticipant | null {
  return selections.value[slot - 1] ?? null
}

function isProvisional(slot: number): boolean {
  const participant = participantAt(slot)
  return participant !== null && isProvisionalParticipant(participant.id)
}
</script>

<style scoped>
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

.provisional-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--app-text-muted);
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

.field-error {
  color: #c24141;
  font-size: 0.75rem;
  padding-left: 0.125rem;
}
</style>
