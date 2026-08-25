<template>
  <i18n-t keypath="legal.authNotice" tag="p" class="legal-notice">
    <template #terms>
      <router-link :to="{ name: 'terms' }">{{ t('legal.nav.terms') }}</router-link>
    </template>
    <template #privacy>
      <router-link :to="{ name: 'privacy' }">{{ t('legal.nav.privacy') }}</router-link>
    </template>
  </i18n-t>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<style scoped>
.legal-notice {
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.45;
  text-align: center;
  color: var(--app-text-muted);
}
</style>
