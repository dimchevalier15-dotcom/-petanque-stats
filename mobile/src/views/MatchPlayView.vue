<template>
  <section class="play">
    <header class="banner">
      <Button icon="pi pi-chevron-left" text @click="goPrev" :disabled="currentEndIndex === 0" aria-label="prev" />
      <div class="info">
        <span>{{ t('play.end') }} {{ currentEnd.index }}</span>
        <span>·</span>
        <span>{{ t('play.score') }} {{ scoreA }} - {{ scoreB }}</span>
      </div>
      <Button icon="pi pi-chevron-right" text @click="goNext" :disabled="currentEndIndex >= ends.length - 1" aria-label="next" />
    </header>

    <div class="teams">
      <div class="team">
        <h3>{{ t('matches.teams.a') }}</h3>
        <div v-for="pid in setup.teamA" :key="pid" class="player">
          <div class="player-name">{{ nameFor(pid) }}</div>
          <div class="balls" v-if="isTracked(pid)">
            <Button
              v-for="i in ballsPerPlayer"
              :key="i"
              :severity="severityFor(noteAt(pid, i - 1))"
              :label="ballLabel(pid, i - 1)"
              text
              rounded
              class="ball"
              @click="openNote($event, pid, i - 1)"
            />
          </div>
        </div>
      </div>

      <div class="team">
        <h3>{{ t('matches.teams.b') }}</h3>
        <div v-for="pid in setup.teamB" :key="pid" class="player">
          <div class="player-name">{{ nameFor(pid) }}</div>
          <div class="balls" v-if="isTracked(pid)">
            <Button
              v-for="i in ballsPerPlayer"
              :key="i"
              :severity="severityFor(noteAt(pid, i - 1))"
              :label="ballLabel(pid, i - 1)"
              text
              rounded
              class="ball"
              @click="openNote($event, pid, i - 1)"
            />
          </div>
        </div>
      </div>
    </div>

    <OverlayPanel ref="op">
      <div class="note-picker">
        <Button
          v-for="opt in notesOptions()"
          :key="String(opt)"
          :label="formatNote(opt)"
          :severity="severityFor(opt)"
          size="small"
          @click="applyNote(opt)"
        />
      </div>
    </OverlayPanel>

    <Dialog v-model:visible="scoreDialog" modal :header="t('play.endScore.title')" :closable="false">
      <div class="end-score">
        <div class="winner">
          <SelectButton v-model="winner" :options="winnerOptions" optionLabel="label" optionValue="value" />
        </div>
        <div class="points">
          <InputText v-model.number="points" type="number" min="1" max="13" />
        </div>
        <div class="actions">
          <Button :label="t('play.actions.saveEnd')" @click="confirmEndScore" :disabled="!winner || !points" />
        </div>
      </div>
    </Dialog>

    <div class="finish" v-if="isFinished">
      <Button class="finish-btn" :label="t('play.actions.finish')" icon="pi pi-check" @click="onFinish" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import OverlayPanel from 'primevue/overlaypanel'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import type { TeamSide } from '../models/MatchPlay'
import type { MatchType, StatisticsMode } from '../models/Match'
import { useMatchPlay } from '../composables/useMatchPlay'
import { matchesService } from '../services/matches'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import { playersService } from '../services/players'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

// The setup is reconstructed from query params provided by NewMatchView navigation after creation
const matchId = Number(route.params.id)
const q = route.query as Record<string, string | undefined>
const type = (q.type as MatchType) || 'doublette'
const statisticsMode = (q.statisticsMode as StatisticsMode) || 'standard'
const targetScore = q.targetScore ? Number(q.targetScore) : 13
const teamA = q.teamA ? q.teamA.split(',').map((x) => Number(x)) : []
const teamB = q.teamB ? q.teamB.split(',').map((x) => Number(x)) : []
const trackedPlayers = q.tracked ? q.tracked.split(',').map((x) => Number(x)) : [...teamA, ...teamB]

const setup = { id: matchId, type, targetScore, statisticsMode, teamA, teamB, trackedPlayers }

const {
  currentEndIndex,
  currentEnd,
  ends,
  scoreA,
  scoreB,
  isFinished,
  ballsPerPlayer,
  goPrevEnd,
  goNextEnd,
  setNote,
  setEndScore,
  notesOptions,
  currentEndComplete,
  colorFor,
  toSubmission,
} = useMatchPlay(setup)

const op = ref<InstanceType<typeof OverlayPanel> | null>(null)
const noteCtx = ref<{ playerId: number; noteIndex: number } | null>(null)

function openNote(event: Event, playerId: number, noteIndex: number) {
  noteCtx.value = { playerId, noteIndex }
  op.value?.toggle(event)
}

function applyNote(val: -2 | -1 | 0 | 1 | 2) {
  if (!noteCtx.value) return
  setNote(noteCtx.value.playerId, noteCtx.value.noteIndex, val)
  op.value?.hide()
}

function formatNote(n: number): string {
  return n > 0 ? `+${n}` : String(n)
}

function severityFor(n?: number): 'danger' | 'warn' | 'secondary' | 'success' | 'help' | undefined {
  switch (n) {
    case -2:
      return 'danger'
    case -1:
      return 'warn'
    case 0:
      return 'secondary'
    case 1:
      return 'success'
    case 2:
      return 'help'
    default:
      return undefined
  }
}

function noteAt(playerId: number, idx: number): number | undefined {
  const e = currentEnd.value
  const entry = e.balls.find((b) => b.playerId === playerId)
  return entry?.notes[idx]
}

function ballLabel(playerId: number, idx: number): string {
  const n = noteAt(playerId, idx)
  if (n === undefined) return '⚪'
  return formatNote(n)
}

function isTracked(playerId: number): boolean {
  return setup.trackedPlayers.includes(playerId)
}

const scoreDialog = ref(false)
const winner = ref<TeamSide | null>(null)
const points = ref<number | null>(null)

const winnerOptions = computed(() => [
  { label: t('matches.teams.a'), value: 'A' as TeamSide },
  { label: t('matches.teams.b'), value: 'B' as TeamSide },
])

watch(currentEndComplete, (v) => {
  if (v && !currentEnd.value.points) {
    winner.value = null
    points.value = 1
    scoreDialog.value = true
  }
})

function confirmEndScore() {
  if (!winner.value || !points.value) return
  setEndScore(winner.value, points.value)
  scoreDialog.value = false
}

function goPrev() { goPrevEnd() }
function goNext() { goNextEnd() }

async function onFinish() {
  const payload: CompleteMatchRequestDto = toSubmission()
  await matchesService.complete(matchId, payload)
  router.push({ name: 'home' })
}

const names = ref<Record<number, string>>({})

function nameFor(pid: number): string {
  return names.value[pid] ?? `#${pid}`
}

onMounted(async () => {
  // If essential data missing, go back
  if (!matchId || teamA.length === 0 || teamB.length === 0) {
    router.replace({ name: 'home' })
    return
  }
  // Fetch names for all players displayed on screen for clarity during entry
  const ids = Array.from(new Set([...teamA, ...teamB]))
  try {
    const results = await Promise.all(ids.map((id) => playersService.getById(id)))
    for (const p of results) {
      const full = `${p.firstName} ${p.lastName}`.trim()
      names.value[p.id] = p.nickname ? `${p.nickname} (${full})` : full
    }
  } catch {
    // ignore errors; fallback labels remain
  }
})
</script>

<style scoped>
.play { max-width: 520px; margin: 0.5rem auto 1rem; display: grid; gap: 0.75rem; }
.banner { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 0.25rem; }
.banner .info { justify-self: center; display: flex; gap: 0.5rem; font-weight: 600; }
.teams { display: grid; gap: 0.75rem; grid-template-columns: 1fr; }
.team { border: 1px solid #eee; border-radius: 10px; padding: 0.5rem; }
.player { display: grid; gap: 0.25rem; padding: 0.25rem 0; }
.balls { display: flex; gap: 0.25rem; }
.ball { min-width: 2.25rem; }
.note-picker { display: flex; gap: 0.5rem; }
.end-score { display: grid; gap: 0.75rem; }
.end-score .winner { display: flex; justify-content: center; }
.end-score .points { display: grid; gap: 0.25rem; justify-items: center; }
.finish { display: grid; justify-items: center; margin-top: 0.25rem; }
.finish-btn { width: 100%; }
@media (min-width: 640px) { .teams { grid-template-columns: repeat(2, 1fr); gap: 1rem; } }
</style>
