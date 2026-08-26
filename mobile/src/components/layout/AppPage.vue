<template>
  <section class="app-page" :class="{ 'app-page--compact': compact }">
    <header v-if="title || $slots.header" class="page-top">
      <slot name="header">
        <h2 v-if="title" class="app-section-title">{{ title }}</h2>
        <p v-if="subtitle" class="app-section-subtitle">{{ subtitle }}</p>
      </slot>
    </header>
    <div class="page-body">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
  subtitle?: string
  compact?: boolean
}>()
</script>

<style scoped>
.app-page {
  display: grid;
  gap: var(--app-space-lg);
  min-width: 0;
}

.app-page--compact {
  gap: var(--app-space-md);
}

.page-top {
  display: grid;
  gap: var(--app-space-xs);
}

.page-body {
  display: grid;
  gap: var(--app-space-md);
  min-width: 0;
}
</style>
