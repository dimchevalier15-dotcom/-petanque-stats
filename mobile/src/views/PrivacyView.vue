<template>
  <LegalDocument :title="t('legal.privacy.title')" :updated-at="t('legal.updatedAt')">
    <p>{{ t('legal.privacy.intro') }}</p>

    <h2>{{ t('legal.privacy.s1.title') }}</h2>
    <p>{{ t('legal.privacy.s1.p1') }}</p>
    <p>
      {{ t('legal.publisher.labels.company') }}: {{ publisher.companyName }}.
      {{ t('legal.privacy.s1.contactLabel') }}:
      <a :href="`mailto:${publisher.email}`">{{ publisher.email }}</a>.
    </p>

    <h2>{{ t('legal.privacy.s2.title') }}</h2>
    <p>{{ t('legal.privacy.s2.intro') }}</p>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s2.items')" :key="index">{{ item }}</li>
    </ul>
    <p>{{ t('legal.privacy.s2.outro') }}</p>

    <h2>{{ t('legal.privacy.s3.title') }}</h2>
    <h3>{{ t('legal.privacy.s3.accountTitle') }}</h3>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s3.accountItems')" :key="`a-${index}`">{{ item }}</li>
    </ul>
    <h3>{{ t('legal.privacy.s3.playerTitle') }}</h3>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s3.playerItems')" :key="`p-${index}`">{{ item }}</li>
    </ul>
    <h3>{{ t('legal.privacy.s3.gameTitle') }}</h3>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s3.gameItems')" :key="`g-${index}`">{{ item }}</li>
    </ul>
    <p>{{ t('legal.privacy.s3.statsNote') }}</p>
    <h3>{{ t('legal.privacy.s3.authTitle') }}</h3>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s3.authItems')" :key="`auth-${index}`">{{ item }}</li>
    </ul>
    <h3>{{ t('legal.privacy.s3.deviceTitle') }}</h3>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s3.deviceItems')" :key="`d-${index}`">{{ item }}</li>
    </ul>
    <p>{{ t('legal.privacy.s3.notCollected') }}</p>

    <h2>{{ t('legal.privacy.s4.title') }}</h2>
    <p>{{ t('legal.privacy.s4.p1') }}</p>
    <p>{{ t('legal.privacy.s4.hosting') }}</p>
    <p>{{ t('legal.privacy.s4.resend') }}</p>
    <p>{{ t('legal.privacy.s4.googlePlay') }}</p>
    <p>{{ t('legal.privacy.s4.noTracking') }}</p>

    <h2>{{ t('legal.privacy.s5.title') }}</h2>
    <p>{{ t('legal.privacy.s5.p1') }}</p>

    <h2>{{ t('legal.privacy.s6.title') }}</h2>
    <p>{{ t('legal.privacy.s6.p1') }}</p>

    <h2>{{ t('legal.privacy.s7.title') }}</h2>
    <p>{{ t('legal.privacy.s7.p1') }}</p>

    <h2>{{ t('legal.privacy.s8.title') }}</h2>
    <p>{{ t('legal.privacy.s8.p1') }}</p>

    <h2>{{ t('legal.privacy.s9.title') }}</h2>
    <i18n-t keypath="legal.privacy.s9.p1" tag="p">
      <template #link>
        <router-link :to="{ name: 'deleteAccount' }">{{ t('legal.nav.deleteAccount') }}</router-link>
      </template>
    </i18n-t>
    <p>{{ t('legal.privacy.s9.deletedIntro') }}</p>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s9.deletedItems')" :key="index">{{ item }}</li>
    </ul>
    <p>{{ t('legal.privacy.s9.kept') }}</p>
    <p>{{ t('legal.privacy.s9.extended') }}</p>

    <h2>{{ t('legal.privacy.s10.title') }}</h2>
    <p>{{ t('legal.privacy.s10.intro') }}</p>
    <ul>
      <li v-for="(item, index) in tm('legal.privacy.s10.items')" :key="index">{{ item }}</li>
    </ul>
    <p>
      {{ t('legal.privacy.s10.contact') }}:
      <a :href="`mailto:${publisher.email}`">{{ publisher.email }}</a>.
    </p>

    <h2>{{ t('legal.privacy.s11.title') }}</h2>
    <p>{{ t('legal.privacy.s11.p1') }}</p>

    <h2>{{ t('legal.privacy.s12.title') }}</h2>
    <p>{{ t('legal.privacy.s12.p1') }}</p>
  </LegalDocument>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import LegalDocument from '../components/legal/LegalDocument.vue'
import { publisher } from '../legal/publisher'

const { t, tm } = useI18n()
</script>
