<template>
  <div v-if="impersonation.isActive && impersonation.player" class="impersonation-banner">
    <div class="banner-text">
      <i class="pi pi-user" aria-hidden="true" />
      <span>{{ t('admin.impersonate.banner', { name: playerLabel }) }}</span>
    </div>
    <Button
      type="button"
      size="small"
      severity="secondary"
      outlined
      :label="t('admin.impersonate.stop')"
      @click="stop"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import { formatPlayerLabel } from '../../composables/usePlayerSearch'
import { useImpersonationStore } from '../../stores/impersonation'

const { t } = useI18n()
const router = useRouter()
const impersonation = useImpersonationStore()

const playerLabel = computed(() =>
  impersonation.player ? formatPlayerLabel(impersonation.player) : '',
)

function stop() {
  impersonation.clear()
  if (router.currentRoute.value.name === 'myStats' || router.currentRoute.value.name === 'matchHistory') {
    router.go(0)
  }
}
</script>

<style scoped>
.impersonation-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-sm);
  padding: 0.625rem var(--app-space-md);
  background: #fff7ed;
  border-bottom: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 0.875rem;
}

.banner-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.banner-text span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
