<template>
  <div class="app-shell">
    <main class="app-content">
      <router-view />
    </main>
    <BottomNav />
  </div>
</template>

<script setup lang="ts">
import BottomNav from '../components/layout/BottomNav.vue'
</script>
