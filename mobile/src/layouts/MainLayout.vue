<template>
  <div class="app-shell">
    <main class="app-content">
      <router-view />
    </main>
    <BottomNav v-if="showBottomNav" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import BottomNav from '../components/layout/BottomNav.vue'
import { useAuthStore } from '../stores/auth'
import { useGuestStore } from '../stores/guest'

const route = useRoute()
const auth = useAuthStore()
const guest = useGuestStore()

const showBottomNav = computed(
  () => auth.isAuthenticated && !guest.isGuestSession && route.meta.layout === 'main',
)
</script>
