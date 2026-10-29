<template>
  <AppPage>
    <PageHeader
      :title="t('jumpShot.home.title')"
      :subtitle="t('jumpShot.home.subtitle')"
      :back-to="{ name: 'trainingHome' }"
    />

    <div class="home-actions">
      <Button
        class="action-secondary"
        :label="t('jumpShot.home.rules')"
        icon="pi pi-book"
        severity="secondary"
        outlined
        @click="goRules"
      />
      <Button
        class="action-primary"
        :label="t('jumpShot.home.start')"
        icon="pi pi-play"
        :loading="starting"
        @click="startNewSession"
      />
    </div>

    <Dialog
      v-model:visible="resumeDialog"
      :modal="true"
      :closable="false"
      :header="t('jumpShot.resume.title')"
      class="jump-shot-dialog"
    >
      <div class="resume-content">
        <p>{{ t('jumpShot.resume.message') }}</p>
        <div class="actions">
          <Button
            :label="t('jumpShot.resume.abandon')"
            severity="secondary"
            outlined
            :loading="abandoning"
            @click="abandonCurrent"
          />
          <Button :label="t('jumpShot.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
        </div>
      </div>
    </Dialog>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import type { JumpShotSessionStarted } from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

const { t } = useI18n()
const router = useRouter()

const starting = ref(false)
const abandoning = ref(false)
const resumeDialog = ref(false)
const currentSession = ref<JumpShotSessionStarted | null>(null)

function goRules(): void {
  router.push({ name: 'jumpShotRules' })
}

async function startNewSession(): Promise<void> {
  starting.value = true
  try {
    const session = await jumpShotSessionsService.start()
    await router.push({ name: 'jumpShotSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

function resumeCurrent(): void {
  if (!currentSession.value) {
    return
  }
  resumeDialog.value = false
  void router.push({ name: 'jumpShotSession', params: { id: currentSession.value.id } })
}

async function abandonCurrent(): Promise<void> {
  if (!currentSession.value) {
    return
  }
  abandoning.value = true
  try {
    await jumpShotSessionsService.abandon(currentSession.value.id)
    resumeDialog.value = false
    currentSession.value = null
  } finally {
    abandoning.value = false
  }
}

onMounted(async () => {
  const current = await jumpShotSessionsService.current()
  if (current) {
    currentSession.value = current
    resumeDialog.value = true
  }
})
</script>

<style scoped>
.home-actions {
  display: grid;
  gap: var(--app-space-sm);
}

.action-primary,
.action-secondary {
  width: 100%;
}

.resume-content {
  display: grid;
  gap: var(--app-space-md);
}

.resume-content .actions {
  display: grid;
  gap: var(--app-space-sm);
}
</style>
