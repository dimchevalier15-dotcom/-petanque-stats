<template>
  <AppPage :title="t('validation.title')">
    <EmptyState
      v-if="items.length === 0 && !loading"
      :title="t('validation.empty')"
      icon="pi pi-check-circle"
    />

    <ul v-else class="list">
      <li v-for="item in items" :key="item.matchPlayerId" class="validation-card app-card">
        <div class="head">
          <span class="date">{{ formatDate(item.date) }}</span>
          <span class="type">{{ typeLabel(item.type) }}</span>
        </div>

        <div v-if="hasContext(item)" class="context-row">
          <Tag v-if="contextLabels(item).nature" :value="contextLabels(item).nature" severity="secondary" />
          <span v-if="contextLabels(item).competition" class="context-competition">
            {{ contextLabels(item).competition }}
          </span>
          <Tag v-if="contextLabels(item).stage" :value="contextLabels(item).stage" severity="info" />
        </div>

        <div class="teams">
          <span class="team">{{ item.teamALabel }}</span>
          <span class="vs">{{ t('validation.vs') }}</span>
          <span class="team">{{ item.teamBLabel }}</span>
        </div>

        <div class="score">{{ item.scoreA }} — {{ item.scoreB }}</div>

        <div class="actions">
          <Button
            :label="t('validation.accept')"
            icon="pi pi-check"
            severity="success"
            :loading="actingId === item.matchPlayerId && actingValidated === true"
            :disabled="actingId !== null"
            @click="onValidate(item, true)"
          />
          <Button
            :label="t('validation.reject')"
            icon="pi pi-times"
            severity="danger"
            outlined
            :loading="actingId === item.matchPlayerId && actingValidated === false"
            :disabled="actingId !== null"
            @click="onValidate(item, false)"
          />
        </div>
      </li>
    </ul>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import { useMatchHistoryContext } from '../composables/useMatchHistoryContext'
import { matchesService } from '../services/matches'
import { useAuthStore } from '../stores/auth'
import type { PendingValidationItem } from '../models/PendingValidation'

const { t, d } = useI18n()
const auth = useAuthStore()
const { contextLabels, hasContext } = useMatchHistoryContext(t)

const items = ref<PendingValidationItem[]>([])
const loading = ref(false)
const actingId = ref<number | null>(null)
const actingValidated = ref<boolean | null>(null)

function typeLabel(type: PendingValidationItem['type']): string {
  switch (type) {
    case 'tete_a_tete':
      return t('matches.types.teteATete')
    case 'doublette':
      return t('matches.types.doublette')
    case 'triplette':
      return t('matches.types.triplette')
    default:
      return String(type)
  }
}

function formatDate(iso: string): string {
  try {
    return d(new Date(iso), 'short') as string
  } catch {
    return new Date(iso).toLocaleDateString()
  }
}

async function load() {
  loading.value = true
  try {
    const res = await matchesService.getPendingValidation()
    items.value = res.items
    if (auth.user) {
      auth.user = { ...auth.user, pendingValidationCount: res.total }
    }
  } finally {
    loading.value = false
  }
}

async function onValidate(item: PendingValidationItem, validated: boolean) {
  actingId.value = item.matchPlayerId
  actingValidated.value = validated
  try {
    await matchesService.updateValidation(item.matchPlayerId, validated)
    items.value = items.value.filter((i) => i.matchPlayerId !== item.matchPlayerId)
    if (auth.user) {
      auth.user = {
        ...auth.user,
        pendingValidationCount: Math.max(0, (auth.user.pendingValidationCount ?? 0) - 1),
      }
    }
  } finally {
    actingId.value = null
    actingValidated.value = null
  }
}

onMounted(load)
</script>

<style scoped>
.list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-md);
}

.validation-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--app-space-sm);
}

.date {
  font-weight: 700;
  font-size: 0.9375rem;
}

.type {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.context-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
  align-items: center;
}

.context-competition {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.teams {
  display: grid;
  gap: 0.25rem;
  text-align: center;
  font-size: 0.9375rem;
}

.team {
  font-weight: 600;
}

.vs {
  font-size: 0.75rem;
  color: var(--app-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.score {
  text-align: center;
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.actions {
  display: grid;
  gap: var(--app-space-sm);
  margin-top: var(--app-space-xs);
}
</style>
