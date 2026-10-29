<template>
  <AppPage>
    <PageHeader
      :title="t('jumpShot.rules.title')"
      :subtitle="t('jumpShot.rules.subtitle')"
      :back-to="backTarget"
    />

    <article class="rules-card app-card">
      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.setup.title') }}</h3>
        <ul>
          <li v-for="n in 3" :key="'setup-' + n">{{ t(`jumpShot.rules.sections.setup.items.${n - 1}`) }}</li>
        </ul>
      </section>

      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.distances.title') }}</h3>
        <p>{{ t('jumpShot.rules.sections.distances.intro') }}</p>
      </section>

      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.series.title') }}</h3>
        <p>{{ t('jumpShot.rules.sections.series.intro') }}</p>
      </section>

      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.results.title') }}</h3>
        <ul>
          <li v-for="result in JUMP_SHOT_THROW_RESULTS" :key="result">
            {{ t(`jumpShot.results.${result}`) }}
          </li>
        </ul>
      </section>

      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.progression.title') }}</h3>
        <ul>
          <li v-for="n in 4" :key="'prog-' + n">{{ t(`jumpShot.rules.sections.progression.items.${n - 1}`) }}</li>
        </ul>
      </section>

      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.special.title') }}</h3>
        <p>{{ t('jumpShot.rules.sections.special.intro') }}</p>
        <ul>
          <li v-for="n in 3" :key="'special-' + n">{{ t(`jumpShot.rules.sections.special.items.${n - 1}`) }}</li>
        </ul>
      </section>

      <section class="rules-section">
        <h3>{{ t('jumpShot.rules.sections.victory.title') }}</h3>
        <p>{{ t('jumpShot.rules.sections.victory.intro') }}</p>
      </section>
    </article>

    <Button
      v-if="!sessionId"
      class="start-btn"
      :label="t('jumpShot.rules.start')"
      icon="pi pi-play"
      :loading="starting"
      @click="startSession"
    />
  </AppPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import { JUMP_SHOT_THROW_RESULTS } from '../models/JumpShot'
import { jumpShotSessionsService } from '../services/jumpShotSessions'

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
    return { name: 'jumpShotSession', params: { id: sessionId.value } }
  }
  return { name: 'jumpShotHome' }
})

async function startSession(): Promise<void> {
  starting.value = true
  try {
    const session = await jumpShotSessionsService.start()
    await router.push({ name: 'jumpShotSession', params: { id: session.id } })
  } finally {
    starting.value = false
  }
}
</script>

<style scoped>
.rules-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-lg);
}

.rules-section h3 {
  margin: 0 0 var(--app-space-xs);
  font-size: 1rem;
}

.rules-section p {
  margin: 0 0 var(--app-space-xs);
  font-size: 0.9375rem;
  line-height: 1.45;
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
