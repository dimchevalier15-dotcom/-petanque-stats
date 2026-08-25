<template>
  <LegalDocument :title="t('legal.notice.title')" :updated-at="t('legal.updatedAt')">
    <p>{{ t('legal.notice.intro') }}</p>

    <h2>{{ t('legal.notice.s1.title') }}</h2>
    <LegalPublisherBlock />

    <h2>{{ t('legal.notice.s2.title') }}</h2>
    <p>{{ t('legal.notice.s2.intro') }}</p>
    <p class="host-block">{{ t('legal.notice.s2.hetzner') }}</p>
    <p>
      {{ t('legal.notice.s2.site') }}:
      <a href="https://petanque-analytics.com">https://petanque-analytics.com</a><br />
      {{ t('legal.notice.s2.api') }}:
      <a href="https://api.petanque-analytics.com">https://api.petanque-analytics.com</a>
    </p>
    <p>{{ t('legal.notice.s2.https') }}</p>

    <h2>{{ t('legal.notice.s3.title') }}</h2>
    <p>{{ t('legal.notice.s3.p1') }}</p>

    <h2>{{ t('legal.notice.s4.title') }}</h2>
    <p>{{ t('legal.notice.s4.p1') }}</p>

    <h2>{{ t('legal.notice.s5.title') }}</h2>
    <i18n-t keypath="legal.notice.s5.p1" tag="p">
      <template #privacy>
        <router-link :to="{ name: 'privacy' }">{{ t('legal.nav.privacy') }}</router-link>
      </template>
      <template #delete>
        <router-link :to="{ name: 'deleteAccount' }">{{ t('legal.nav.deleteAccount') }}</router-link>
      </template>
    </i18n-t>

    <h2>{{ t('legal.notice.s6.title') }}</h2>
    <i18n-t keypath="legal.notice.s6.p1" tag="p">
      <template #terms>
        <router-link :to="{ name: 'terms' }">{{ t('legal.nav.terms') }}</router-link>
      </template>
    </i18n-t>
  </LegalDocument>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import LegalDocument from '../components/legal/LegalDocument.vue'
import LegalPublisherBlock from '../components/legal/LegalPublisherBlock.vue'

const { t } = useI18n()
</script>

<style scoped>
.host-block {
  white-space: pre-line;
}
</style>
