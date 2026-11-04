<template>
  <AppPage compact>
    <PageHeader :title="t('rings.home.title')" :back-to="{ name: 'ringsHome' }">
      <template #actions>
        <Button
          class="rules-btn"
          icon="pi pi-question-circle"
          text
          rounded
          :aria-label="t('rings.play.rules')"
          @click="rulesDialog = true"
        />
      </template>
    </PageHeader>

    <header class="scoreboard app-card">
      <span class="ball-pill">
        {{ isComplete ? t('rings.play.allPlayed') : t('rings.play.ball', { current: currentBall, total: RINGS_THROWS_PER_SESSION }) }}
      </span>
      <strong class="score-value">{{ totalScore }}<span class="score-max">/{{ RINGS_MAX_SESSION_SCORE }}</span></strong>
      <span class="score-meta">{{ t('rings.play.successCount', { count: successCount, total: throws.length }) }}</span>
    </header>

    <div class="targets">
      <article v-for="target in RINGS_TARGETS" :key="target" class="target-card app-card">
        <div class="target-head">
          <span class="target-distance">{{ distanceLabel(target) }}</span>
          <Tag :value="pointsLabel(target)" severity="success" />
        </div>
        <div class="target-actions">
          <Button
            class="throw-btn"
            :label="t('rings.play.success')"
            icon="pi pi-check"
            severity="success"
            :disabled="isComplete"
            @click="addThrow(target, true)"
          />
          <Button
            class="throw-btn"
            :label="t('rings.play.missed')"
            icon="pi pi-times"
            severity="danger"
            outlined
            :disabled="isComplete"
            @click="addThrow(target, false)"
          />
        </div>
      </article>
    </div>

    <RingsThrowTrack :throws="throws" highlight-next />

    <footer class="play-actions">
      <Button
        class="primary-action"
        :label="t('rings.play.finish')"
        icon="pi pi-flag"
        :loading="finishing"
        :disabled="!isComplete"
        @click="finishSession"
      />
      <div class="secondary-actions">
        <Button
          :label="t('rings.play.undo')"
          icon="pi pi-undo"
          severity="secondary"
          text
          :disabled="throws.length === 0"
          @click="undoLast"
        />
        <Button :label="t('rings.play.abandon')" severity="secondary" text @click="abandonDialog = true" />
      </div>
    </footer>

    <Dialog
      v-model:visible="abandonDialog"
      :modal="true"
      :header="t('rings.abandon.title')"
      :closable="false"
      class="rings-dialog"
    >
      <div class="abandon-content">
        <p>{{ t('rings.abandon.message') }}</p>
        <div class="actions">
          <Button :label="t('rings.abandon.cancel')" severity="secondary" @click="abandonDialog = false" />
          <Button :label="t('rings.abandon.confirm')" severity="danger" :loading="abandoning" @click="confirmAbandon" />
        </div>
      </div>
    </Dialog>

    <RingsRulesDialog v-model:visible="rulesDialog" />
  </AppPage>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import RingsRulesDialog from '../components/rings/RingsRulesDialog.vue'
import RingsThrowTrack from '../components/rings/RingsThrowTrack.vue'
import { clearRingsDraft, useRingsSessionPlay } from '../composables/useRingsSessionPlay'
import { useRingsTargetLabels } from '../composables/useRingsTargetLabels'
import { RINGS_MAX_SESSION_SCORE, RINGS_TARGETS, RINGS_THROWS_PER_SESSION } from '../models/Rings'
import { ringsSessionsService } from '../services/ringsSessions'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { distanceLabel, pointsLabel } = useRingsTargetLabels()

const sessionId = Number(route.params.id)
const { throws, isComplete, totalScore, successCount, currentBall, addThrow, undoLast, toCompletionPayload } =
  useRingsSessionPlay(sessionId)

const rulesDialog = ref(false)
const abandonDialog = ref(false)
const abandoning = ref(false)
const finishing = ref(false)

async function finishSession(): Promise<void> {
  finishing.value = true
  try {
    await ringsSessionsService.complete(sessionId, toCompletionPayload())
    clearRingsDraft(sessionId)
    await router.push({ name: 'ringsSessionSummary', params: { id: sessionId } })
  } finally {
    finishing.value = false
  }
}

async function confirmAbandon(): Promise<void> {
  abandoning.value = true
  try {
    await ringsSessionsService.abandon(sessionId)
    clearRingsDraft(sessionId)
    await router.push({ name: 'ringsHome' })
  } finally {
    abandoning.value = false
    abandonDialog.value = false
  }
}
</script>

<style scoped>
.rules-btn {
  color: var(--app-primary);
}

.scoreboard {
  padding: var(--app-space-md);
  display: grid;
  justify-items: center;
  gap: 0.25rem;
  text-align: center;
}

.ball-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.1875rem 0.625rem;
  border-radius: 999px;
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.score-value {
  font-size: 2rem;
  font-weight: 800;
  color: var(--app-primary-dark);
  font-variant-numeric: tabular-nums;
}

.score-max {
  font-size: 1rem;
  font-weight: 600;
  color: var(--app-text-subtle);
}

.score-meta {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.targets {
  display: grid;
  gap: var(--app-space-sm);
}

.target-card {
  padding: var(--app-space-sm) var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.target-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.target-distance {
  font-size: 1.125rem;
  font-weight: 800;
}

.target-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.throw-btn {
  min-height: 3rem;
  font-weight: 700;
}

.play-actions {
  display: grid;
  gap: var(--app-space-xs);
}

.primary-action {
  min-height: 2.75rem;
  font-weight: 700;
}

.secondary-actions {
  display: flex;
  justify-content: space-between;
}

.abandon-content {
  display: grid;
  gap: var(--app-space-md);
}

.abandon-content p {
  margin: 0;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.abandon-content .actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}
</style>
