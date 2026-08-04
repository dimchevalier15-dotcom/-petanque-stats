<template>
  <main class="container">
    <h1>{{ t('app.title') }}</h1>
    <router-view />
  </main>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>

<style scoped>
.container { padding: 2rem; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol; }
h1 { margin: 0 0 1rem; }
</style>
