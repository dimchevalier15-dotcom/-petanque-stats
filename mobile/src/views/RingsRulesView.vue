<template>
  <AppPage>
    <PageHeader :title="t('rings.rules.title')" :subtitle="t('rings.rules.subtitle')" :back-to="{ name: 'ringsHome' }" />

    <article class="rules-card app-card">
      <RingsRules />
    </article>

    <Button
      class="start-btn"
      :label="t('rings.rules.start')"
      icon="pi pi-play"
      :loading="starting"
      @click="startSession"
    />
  </AppPage>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import RingsRules from '../components/rings/RingsRules.vue'
import { ringsSessionsService } from '../services/ringsSessions'

const { t } = useI18n()
const router = useRouter()

const starting = ref(false)

async function startSession(): Promise<void> {
  starting.value = true
  try {
    const session = await ringsSessionsService.start()
    await router.push({ name: 'ringsSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}
</script>

<style scoped>
.rules-card {
  padding: var(--app-space-md);
}

.start-btn {
  width: 100%;
  min-height: 2.75rem;
  font-weight: 700;
}
</style>
