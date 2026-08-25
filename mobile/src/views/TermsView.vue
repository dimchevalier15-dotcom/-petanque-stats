<template>
  <LegalDocument :title="t('legal.terms.title')" :updated-at="t('legal.updatedAt')">
    <p>{{ t('legal.terms.intro') }}</p>
    <p>
      {{ t('legal.publisher.labels.company') }}: {{ publisher.companyName }}.
      {{ t('legal.terms.contactLabel') }}:
      <a :href="`mailto:${publisher.email}`">{{ publisher.email }}</a>.
    </p>

    <h2>{{ t('legal.terms.s1.title') }}</h2>
    <p>{{ t('legal.terms.s1.p1') }}</p>
    <p>{{ t('legal.terms.s1.p2') }}</p>

    <h2>{{ t('legal.terms.s2.title') }}</h2>
    <p>{{ t('legal.terms.s2.p1') }}</p>
    <p>{{ t('legal.terms.s2.p2') }}</p>

    <h2>{{ t('legal.terms.s3.title') }}</h2>
    <p>{{ t('legal.terms.s3.p1') }}</p>
    <p>{{ t('legal.terms.s3.p2') }}</p>

    <h2>{{ t('legal.terms.s4.title') }}</h2>
    <p>{{ t('legal.terms.s4.intro') }}</p>
    <ul>
      <li v-for="(item, index) in tm('legal.terms.s4.items')" :key="index">{{ item }}</li>
    </ul>
    <p>{{ t('legal.terms.s4.outro') }}</p>

    <h2>{{ t('legal.terms.s5.title') }}</h2>
    <p>{{ t('legal.terms.s5.p1') }}</p>
    <i18n-t keypath="legal.terms.s5.p2" tag="p">
      <template #privacy>
        <router-link :to="{ name: 'privacy' }">{{ t('legal.nav.privacy') }}</router-link>
      </template>
    </i18n-t>
    <i18n-t keypath="legal.terms.s5.p3" tag="p">
      <template #delete>
        <router-link :to="{ name: 'deleteAccount' }">{{ t('legal.nav.deleteAccount') }}</router-link>
      </template>
    </i18n-t>

    <h2>{{ t('legal.terms.s6.title') }}</h2>
    <p>{{ t('legal.terms.s6.p1') }}</p>

    <h2>{{ t('legal.terms.s7.title') }}</h2>
    <p>{{ t('legal.terms.s7.p1') }}</p>
    <p>{{ t('legal.terms.s7.p2') }}</p>

    <h2>{{ t('legal.terms.s8.title') }}</h2>
    <p>{{ t('legal.terms.s8.p1') }}</p>

    <h2>{{ t('legal.terms.s9.title') }}</h2>
    <p>{{ t('legal.terms.s9.p1') }}</p>
    <p>{{ t('legal.terms.s9.p2') }}</p>

    <h2>{{ t('legal.terms.s10.title') }}</h2>
    <p>{{ t('legal.terms.s10.p1') }}</p>

    <h2>{{ t('legal.terms.s11.title') }}</h2>
    <p>{{ t('legal.terms.s11.p1') }}</p>
  </LegalDocument>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import LegalDocument from '../components/legal/LegalDocument.vue'
import { publisher } from '../legal/publisher'

const { t, tm } = useI18n()
</script>
