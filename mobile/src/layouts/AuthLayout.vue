<template>
  <div class="auth-shell">
    <div class="auth-top">
      <div class="auth-lang">
        <LanguageSwitcher />
      </div>
      <AppUpdateRecommendedBanner />
    </div>
    <div class="auth-main">
      <router-view />
    </div>
  </div>
</template>

<script setup lang="ts">
import AppUpdateRecommendedBanner from '../components/AppUpdateRecommendedBanner.vue'
import LanguageSwitcher from '../components/layout/LanguageSwitcher.vue'
</script>
