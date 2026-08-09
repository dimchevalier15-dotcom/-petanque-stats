<template>
  <div class="empty-state app-card app-card--muted">
    <i v-if="icon" :class="icon" class="empty-icon" aria-hidden="true" />
    <p class="empty-title">{{ title }}</p>
    <p v-if="hint" class="empty-hint">{{ hint }}</p>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  hint?: string
  icon?: string
}>()
</script>

<style scoped>
.empty-state {
  padding: var(--app-space-xl) var(--app-space-lg);
  text-align: center;
  display: grid;
  gap: var(--app-space-sm);
  justify-items: center;
}

.empty-icon {
  font-size: 2rem;
  color: var(--app-text-subtle);
}

.empty-title {
  margin: 0;
  font-weight: 700;
}

.empty-hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
  max-width: 18rem;
}
</style>
