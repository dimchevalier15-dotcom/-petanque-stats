<template>
  <header class="page-header">
    <div class="leading">
      <Button
        v-if="showBack"
        icon="pi pi-arrow-left"
        text
        rounded
        class="back-btn"
        :aria-label="t('nav.back')"
        @click="onBack"
      />
    </div>
    <div class="titles">
      <h1 class="title">{{ title }}</h1>
      <p v-if="subtitle" class="subtitle">{{ subtitle }}</p>
    </div>
    <div class="trailing">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRouter, type RouteLocationRaw } from 'vue-router'
import Button from 'primevue/button'

const props = withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    showBack?: boolean
    backTo?: RouteLocationRaw
  }>(),
  { showBack: true },
)

const { t } = useI18n()
const router = useRouter()

function onBack() {
  console.log("props.backTo", props.backTo)
  if (props.backTo) {
    router.push(props.backTo)
    return
  }
  router.back()
}
</script>

<style scoped>
.page-header {
  position: sticky;
  top: 0;
  z-index: 20;
  display: grid;
  grid-template-columns: 2.5rem 1fr 2.5rem;
  align-items: center;
  gap: var(--app-space-sm);
  min-height: var(--app-header-h);
  padding: var(--app-space-sm) var(--app-space-lg);
  background: rgba(244, 241, 235, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--app-border);
}

.leading,
.trailing {
  display: flex;
  align-items: center;
}

.titles {
  min-width: 0;
  text-align: center;
}

.title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.subtitle {
  margin: 0.125rem 0 0;
  font-size: 0.75rem;
  color: var(--app-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.back-btn {
  color: var(--app-text);
}
</style>
