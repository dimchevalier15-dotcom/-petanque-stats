<template>
  <div class="tournament-player-add">
    <PlayerSearchSelect
      :model-value="null"
      :placeholder="placeholder"
      @update:model-value="onExistingSelected"
    />

    <div class="add-divider">
      <span>{{ t('coach.addPlayer.orCreate') }}</span>
    </div>

    <section class="create-block">
      <h4 class="create-title">{{ t('coach.addPlayer.createTitle') }}</h4>
      <label class="app-field">
        <span>{{ t('players.fields.firstName') }}</span>
        <InputText v-model="firstName" fluid :disabled="busy" />
      </label>
      <label class="app-field">
        <span>{{ t('players.fields.lastName') }}</span>
        <InputText v-model="lastName" fluid :disabled="busy" />
      </label>
      <Button
        type="button"
        size="small"
        icon="pi pi-user-plus"
        :label="t('tournaments.admin.createPlayer')"
        :loading="busy"
        :disabled="!canCreate"
        class="create-btn"
        @click="createAndSelect"
      />
    </section>
  </div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import PlayerSearchSelect from "../players/PlayerSearchSelect.vue";
import type { Player } from "../../models/Player";
import { playersService } from "../../services/players";

const props = withDefaults(
	defineProps<{
		placeholder?: string;
		disabled?: boolean;
		excludeIds?: number[];
	}>(),
	{
		placeholder: "",
		disabled: false,
		excludeIds: () => [],
	},
);

const emit = defineEmits<{
	select: [player: Player];
	error: [error: unknown];
}>();

const { t } = useI18n();
const firstName = ref("");
const lastName = ref("");
const busy = ref(false);

const canCreate = computed(
	() =>
		!props.disabled &&
		!busy.value &&
		firstName.value.trim() !== "" &&
		lastName.value.trim() !== "",
);

function onExistingSelected(player: Player | null): void {
	if (!player || props.disabled) {
		return;
	}
	if (props.excludeIds.includes(player.id)) {
		return;
	}
	emit("select", player);
}

async function createAndSelect(): Promise<void> {
	if (!canCreate.value) {
		return;
	}
	busy.value = true;
	try {
		const player = await playersService.create({
			firstName: firstName.value.trim(),
			lastName: lastName.value.trim(),
		});
		if (props.excludeIds.includes(player.id)) {
			return;
		}
		firstName.value = "";
		lastName.value = "";
		emit("select", player);
	} catch (error) {
		emit("error", error);
	} finally {
		busy.value = false;
	}
}
</script>

<style scoped>
.tournament-player-add {
  display: grid;
  gap: var(--app-space-sm);
}

.add-divider {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--app-text-muted);
  font-size: 0.75rem;
  font-weight: 600;
}

.add-divider::before,
.add-divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: var(--app-border);
}

.create-block {
  display: grid;
  gap: var(--app-space-sm);
}

.create-title {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
}

.create-btn {
  justify-self: start;
}
</style>
