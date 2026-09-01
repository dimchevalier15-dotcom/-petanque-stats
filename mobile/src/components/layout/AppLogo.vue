<template>
  <img
    :class="['app-logo', sizeClass, { 'app-logo--centered': centered }]"
    src="/favicon.png"
    :alt="t('app.title')"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = withDefaults(
  defineProps<{
    size?: 'sm' | 'lg'
    centered?: boolean
  }>(),
  {
    size: 'lg',
    centered: false,
  },
)

const { t } = useI18n()
const sizeClass = computed(() => `app-logo--${props.size}`)
</script>

<style scoped>
.app-logo {
  object-fit: contain;
  flex-shrink: 0;
}

.app-logo--sm {
  width: 1.75rem;
  height: 1.75rem;
}

.app-logo--lg {
  width: 2.5rem;
  height: 2.5rem;
}

.app-logo--centered {
  display: block;
  margin-inline: auto;
  margin-bottom: var(--app-space-sm);
}
</style>
