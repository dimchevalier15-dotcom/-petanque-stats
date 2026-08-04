<template>
  <section>
    <h1>{{ t('app.title') }}</h1>
    <p>{{ t('common.hello') }}</p>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>
