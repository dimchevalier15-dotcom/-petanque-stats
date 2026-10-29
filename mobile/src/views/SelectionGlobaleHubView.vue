<template>
  <AppPage>
    <PageHeader :title="t('selectionGlobale.title')" :back-to="{ name: 'home' }" />

    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <EmptyState
      v-else-if="selections.length === 0"
      :title="t('selectionGlobale.empty')"
      icon="pi pi-flag"
    />

    <ul v-else class="list">
      <li v-for="selection in selections" :key="selection.id">
        <button type="button" class="list-card app-card" @click="openSelection(selection.id)">
          <span class="name">{{ selection.name }}</span>
          <i class="pi pi-chevron-right" aria-hidden="true" />
        </button>
      </li>
    </ul>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import ProgressSpinner from 'primevue/progressspinner'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import type { SelectionGlobaleItem } from '../models/SelectionGlobale'
import { selectionGlobaleService } from '../services/selectionGlobale'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(true)
const selections = ref<SelectionGlobaleItem[]>([])

function openSelection(selectionId: number): void {
  router.push({ name: 'selectionGlobalePlayers', params: { selectionId } })
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const fromMe = auth.user?.coachSelectionGlobales ?? []
    if (fromMe.length > 0) {
      selections.value = fromMe
    } else {
      selections.value = await selectionGlobaleService.listForCoach()
    }
    if (selections.value.length === 1 && route.query.list !== '1') {
      openSelection(selections.value[0].id)
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<style scoped>
.loading {
  display: flex;
  justify-content: center;
  padding: var(--app-space-xl);
}

.list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-sm);
}

.list-card {
  width: 100%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-md);
  padding: var(--app-space-md);
  text-align: left;
  font: inherit;
  color: inherit;
}

.name {
  font-weight: 700;
  font-size: 1rem;
}
</style>
