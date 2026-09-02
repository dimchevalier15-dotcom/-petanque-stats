<template>
  <ImpersonationBanner v-if="!isOverlayLayout" />
  <AppUpdateRequiredBlock v-if="!isOverlayLayout" />
  <component :is="layoutComponent" />
  <Toast v-if="!isOverlayLayout" />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import AppUpdateRequiredBlock from './components/AppUpdateRequiredBlock.vue'
import ImpersonationBanner from './components/admin/ImpersonationBanner.vue'
import { useAppUpdateStore } from './stores/appUpdate'
import MainLayout from './layouts/MainLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import FocusLayout from './layouts/FocusLayout.vue'
import PlayLayout from './layouts/PlayLayout.vue'
import OverlayLayout from './layouts/OverlayLayout.vue'

const route = useRoute()

const layouts = {
  main: MainLayout,
  auth: AuthLayout,
  focus: FocusLayout,
  play: PlayLayout,
  overlay: OverlayLayout,
} as const

const isOverlayLayout = computed(() => route.meta.layout === 'overlay')

const layoutComponent = computed(() => {
  const key = (route.meta.layout as keyof typeof layouts) ?? 'main'
  return layouts[key] ?? MainLayout
})

onMounted(() => {
  if (!isOverlayLayout.value) {
    void useAppUpdateStore().checkOnce()
  }
})
</script>
