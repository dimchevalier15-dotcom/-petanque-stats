<template>
  <section class="placeholder">
    <h2>{{ t('placeholders.newMatch.title') }}</h2>
    <p>{{ t('placeholders.newMatch.content') }}</p>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>

<style scoped>
.placeholder { max-width: 480px; margin: 2rem auto; display: grid; gap: 0.75rem; }
</style>
