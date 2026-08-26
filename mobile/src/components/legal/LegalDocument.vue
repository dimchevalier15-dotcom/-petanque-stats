<template>
  <AppPage>
    <PageHeader :title="title"/>

    <article class="legal-doc app-card">
      <p v-if="updatedAt" class="updated">{{ updatedAt }}</p>
      <slot />
    </article>

    <nav class="legal-nav" :aria-label="t('legal.nav.label')">
      <router-link :to="{ name: 'terms' }">{{ t('legal.nav.terms') }}</router-link>
      <router-link :to="{ name: 'privacy' }">{{ t('legal.nav.privacy') }}</router-link>
      <router-link :to="{ name: 'legal' }">{{ t('legal.nav.legal') }}</router-link>
      <router-link :to="{ name: 'deleteAccount' }">{{ t('legal.nav.deleteAccount') }}</router-link>
    </nav>
  </AppPage>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { RouteLocationRaw } from 'vue-router'
import AppPage from '../layout/AppPage.vue'
import PageHeader from '../layout/PageHeader.vue'
import { useAuthStore } from '../../stores/auth'

defineProps<{
  title: string
  updatedAt?: string
}>()

const { t } = useI18n()
const auth = useAuthStore()
</script>

<style scoped>
.legal-doc {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
  line-height: 1.55;
  font-size: 0.9375rem;
}

.legal-doc :deep(h2) {
  margin: var(--app-space-sm) 0 0;
  font-size: 1.05rem;
}

.legal-doc :deep(h3) {
  margin: 0;
  font-size: 0.95rem;
}

.legal-doc :deep(p),
.legal-doc :deep(ul),
.legal-doc :deep(ol) {
  margin: 0;
}

.legal-doc :deep(ul),
.legal-doc :deep(ol) {
  padding-left: 1.15rem;
  display: grid;
  gap: 0.35rem;
}

.legal-doc :deep(.placeholder) {
  display: inline;
  background: #fff4d6;
  border-radius: 4px;
  padding: 0.05em 0.3em;
  font-weight: 600;
}

.updated {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.legal-nav {
  display: grid;
  gap: var(--app-space-sm);
  padding: var(--app-space-md) 0 var(--app-space-lg);
  font-size: 0.875rem;
}
</style>
