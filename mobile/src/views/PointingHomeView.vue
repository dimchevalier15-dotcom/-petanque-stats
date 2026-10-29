<template>
  <AppPage>
    <PageHeader
      :title="t('pointing.home.title')"
      :subtitle="t('pointing.home.subtitle')"
      :back-to="{ name: 'trainingHome' }"
    />
    <div class="home-actions">
      <Button
        class="action-primary w-full"
        :label="t('pointing.home.newSession')"
        icon="pi pi-circle"
        :loading="starting"
        @click="startNewSession"
      />
    </div>

    <Dialog
      v-model:visible="resumeDialog"
      :modal="true"
      :closable="false"
      :header="t('pointing.resume.title')"
      class="pointing-dialog"
    >
      <div class="resume-content">
        <p>{{ t('pointing.resume.message') }}</p>
        <div class="actions">
          <Button
            :label="t('pointing.resume.abandon')"
            severity="secondary"
            outlined
            :loading="abandoning"
            @click="abandonCurrent"
          />
          <Button :label="t('pointing.resume.continue')" icon="pi pi-arrow-right" @click="resumeCurrent" />
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
import { pointingSessionsService } from '../services/pointingSessions'
import type { PointingSessionStarted } from '../models/Pointing'

const { t } = useI18n()
const router = useRouter()

const starting = ref(false)
const abandoning = ref(false)
const resumeDialog = ref(false)
const currentSession = ref<PointingSessionStarted | null>(null)

async function startNewSession(): Promise<void> {
  starting.value = true
  try {
    const session = await pointingSessionsService.start()
    router.push({ name: 'pointingSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

async function resumeCurrent(): Promise<void> {
  if (!currentSession.value) return
  resumeDialog.value = false
  router.push({ name: 'pointingSession', params: { id: currentSession.value.id } })
}

async function abandonCurrent(): Promise<void> {
  if (!currentSession.value) return
  abandoning.value = true
  try {
    await pointingSessionsService.abandon(currentSession.value.id)
    currentSession.value = null
    resumeDialog.value = false
  } finally {
    abandoning.value = false
  }
}

onMounted(async () => {
  try {
    currentSession.value = await pointingSessionsService.current()
    if (currentSession.value) {
      resumeDialog.value = true
    }
  } catch {
    // ignore
  }
})
</script>

<style scoped>
.w-full {
  width: 100%;
}

.home-actions {
  display: grid;
  gap: var(--app-space-sm);
}

.action-primary {
  min-height: 2.75rem;
  font-weight: 700;
}

.resume-content {
  display: grid;
  gap: var(--app-space-md);
}

.resume-content p {
  margin: 0;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.resume-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}
</style>
