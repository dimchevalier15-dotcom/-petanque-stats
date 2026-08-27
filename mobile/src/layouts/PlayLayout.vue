<template>
  <div class="play-shell" :class="{ 'play-shell--phone-inset': isNative }">
    <router-view />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { Capacitor } from '@capacitor/core'

const isNative = Capacitor.isNativePlatform()

onMounted(() => {
  if (isNative) {
    document.documentElement.classList.add('native-app')
  }
})

onUnmounted(() => {
  document.documentElement.classList.remove('native-app')
})
</script>
