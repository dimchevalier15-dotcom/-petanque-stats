<template>
  <ul class="publisher-list">
    <li>{{ t('legal.publisher.labels.serviceName') }}: Pétanque Analytics</li>
    <li>{{ t('legal.publisher.labels.company') }}: {{ publisher.companyName }}</li>
    <li>{{ t('legal.publisher.labels.legalForm') }}: {{ publisher.legalForm }}</li>
    <li>
      {{ t('legal.publisher.labels.address') }}:
      {{ publisher.addressLine }}, {{ publisher.addressDistrict }}, {{ publisher.addressCity }},
      {{ t('legal.publisher.country') }}
    </li>
    <li>
      {{ t('legal.publisher.labels.email') }}:
      <a :href="`mailto:${publisher.email}`">{{ publisher.email }}</a>
    </li>
    <li>{{ t('legal.publisher.labels.publicationDirector') }}: {{ publisher.publicationDirector }}</li>
    <li>{{ t('legal.publisher.labels.ico') }}: {{ publisher.ico }}</li>
    <li>{{ t('legal.publisher.labels.dic') }}: {{ publisher.dic }}</li>
    <li>{{ t('legal.publisher.labels.icDph') }}: {{ publisher.icDph }}</li>
    <li>{{ t('legal.publisher.labels.commercialRegister') }}: {{ publisher.commercialRegister }}</li>
    <li>
      {{ t('legal.publisher.labels.incorporationDate') }}:
      {{ formatIncorporationDate(publisher.incorporationDate) }}
    </li>
  </ul>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { publisher } from '../../legal/publisher'

const { t, locale } = useI18n()

function formatIncorporationDate(isoDate: string): string {
  const date = new Date(`${isoDate}T12:00:00`)
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'long' }).format(date)
}
</script>

<style scoped>
.publisher-list {
  margin: 0;
  padding-left: 1.15rem;
  display: grid;
  gap: 0.35rem;
}
</style>
