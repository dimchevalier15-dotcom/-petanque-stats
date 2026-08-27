<template>
  <AppPage>
    <PageHeader :title="t('admin.impersonate.title')" :back-to="{ name: 'adminHome' }" />

    <section class="impersonate-card app-card">
      <p class="hint">{{ t('admin.impersonate.hint') }}</p>

      <PlayerSearchSelect
        v-model="impersonateSelection"
        :label="t('admin.impersonate.searchLabel')"
        :placeholder="t('admin.impersonate.searchPlaceholder')"
        :empty-hint="t('admin.impersonate.empty')"
      />

      <p v-if="impersonation.isActive && impersonation.player" class="active-player">
        {{ t('admin.impersonate.active', { name: formatPlayerLabel(impersonation.player) }) }}
      </p>

      <div class="impersonate-actions">
        <Button
          v-if="!impersonation.isActive"
          type="button"
          class="w-full"
          :label="t('admin.impersonate.start')"
          :disabled="!impersonateSelection"
          @click="startImpersonation"
        />
        <Button
          v-else
          type="button"
          severity="secondary"
          outlined
          class="w-full"
          :label="t('admin.impersonate.exit')"
          @click="stopImpersonation"
        />
      </div>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import Button from 'primevue/button'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import { formatPlayerLabel } from '../composables/usePlayerSearch'
import type { Player } from '../models/Player'
import { useImpersonationStore } from '../stores/impersonation'

const { t } = useI18n()
const impersonation = useImpersonationStore()
const impersonateSelection = ref<Player | null>(null)

function startImpersonation() {
  if (!impersonateSelection.value) {
    return
  }
  impersonation.setPlayer(impersonateSelection.value)
}

function stopImpersonation() {
  impersonation.clear()
  impersonateSelection.value = null
}
</script>

<style scoped>
.impersonate-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
}

.hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.active-player {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--app-primary);
}

.impersonate-actions {
  display: grid;
  gap: var(--app-space-sm);
}

.w-full {
  width: 100%;
}
</style>
