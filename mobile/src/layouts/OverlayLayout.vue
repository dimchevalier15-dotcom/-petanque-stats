<template>
  <div class="overlay-shell">
    <router-view />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'

onMounted(() => {
  document.documentElement.classList.add('overlay-mode')
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-mode')
})
</script>

<style>
html.overlay-mode,
html.overlay-mode body,
html.overlay-mode #app {
  background: transparent !important;
}

html.overlay-mode .overlay-shell {
  background: transparent;
  min-height: 100%;
}
</style>
