<template>
  <component :is="layoutComponent" />
  <Toast />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import MainLayout from './layouts/MainLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import FocusLayout from './layouts/FocusLayout.vue'
import PlayLayout from './layouts/PlayLayout.vue'

const route = useRoute()

const layouts = {
  main: MainLayout,
  auth: AuthLayout,
  focus: FocusLayout,
  play: PlayLayout,
} as const

const layoutComponent = computed(() => {
  const key = (route.meta.layout as keyof typeof layouts) ?? 'main'
  return layouts[key] ?? MainLayout
})
</script>
