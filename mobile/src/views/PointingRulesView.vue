<template>
  <AppPage>
    <PageHeader
      :title="t('pointing.rules.title')"
      :subtitle="t('pointing.rules.subtitle')"
      :back-to="backTarget"
    />

    <article class="rules-card app-card">
      <section class="rules-section">
        <h3>{{ t('pointing.rules.sections.setup.title') }}</h3>
        <ul>
          <li v-for="n in 4" :key="'setup-' + n">{{ t(`pointing.rules.sections.setup.items.${n - 1}`) }}</li>
        </ul>
      </section>
    </article>

    <article
      v-for="workshop in POINTING_WORKSHOPS"
      :id="`workshop-${workshop}`"
      :key="workshop"
      class="rules-card app-card workshop-card"
    >
      <PointingWorkshopRules :workshop="workshop" />
    </article>

    <Button
      v-if="!sessionId"
      class="start-btn"
      :label="t('pointing.rules.start')"
      icon="pi pi-play"
      :loading="starting"
      @click="startSession"
    />
  </AppPage>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PointingWorkshopRules from '../components/pointing/PointingWorkshopRules.vue'
import { POINTING_WORKSHOPS } from '../models/Pointing'
import { pointingSessionsService } from '../services/pointingSessions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const starting = ref(false)
const sessionId = computed(() => {
  const raw = route.query.sessionId
  return raw ? Number(raw) : null
})

const backTarget = computed(() => {
  if (sessionId.value) {
    const workshop = route.query.workshop
    return {
      name: 'pointingSession',
      params: { id: sessionId.value },
      query: workshop ? { workshop: String(workshop) } : {},
    }
  }
  return { name: 'trainingPointPrecision' }
})

async function startSession(): Promise<void> {
  starting.value = true
  try {
    const session = await pointingSessionsService.start()
    await router.push({ name: 'pointingSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}

onMounted(async () => {
  const workshop = Number(route.query.workshop)
  if (workshop > 1) {
    await nextTick()
    document.getElementById(`workshop-${workshop}`)?.scrollIntoView({ block: 'start' })
  }
})
</script>

<style scoped>
.rules-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-md);
}

.workshop-card {
  margin-top: var(--app-space-md);
  scroll-margin-top: var(--app-space-md);
}

.rules-section h3 {
  margin: 0 0 var(--app-space-xs);
  font-size: 1rem;
}

.rules-section ul {
  margin: 0;
  padding-left: 1.25rem;
  display: grid;
  gap: 0.375rem;
  font-size: 0.9375rem;
  line-height: 1.45;
}

.start-btn {
  width: 100%;
  margin-top: var(--app-space-md);
}
</style>
