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
      <div class="shot-type">
        <SelectButton v-model="shotType" :options="shotOptions" optionLabel="label" optionValue="value" size="small" />
      </div>
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

    <Dialog v-model:visible="scoreDialog" :modal="false" :dismissableMask="true" :header="t('play.endScore.title')" :closable="true">
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

    <div class="validate-end" v-if="currentEndComplete && !scoreDialog && !isFinished">
      <Button class="validate-end-btn" :label="t('play.actions.validateEnd')" icon="pi pi-check" @click="reopenEndDialog" />
    </div>

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
import type { MatchType, ShotType, StatisticsMode } from '../models/Match'
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

// Parse default shot types map from query (format: "pid:type,pid:type")
const defaultsParam = q.defaults || ''
const defaultShotTypes: Record<number, 'point' | 'tir'> = {}
if (defaultsParam) {
  for (const pair of defaultsParam.split(',')) {
    const [pidStr, st] = pair.split(':')
    const pid = Number(pidStr)
    if (pid && (st === 'point' || st === 'tir')) defaultShotTypes[pid] = st
  }
}

const setup = { id: matchId, type, targetScore, statisticsMode, teamA, teamB, trackedPlayers, defaultShotTypes }

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
  setNoteWithShot,
  setEndScore,
  notesOptions,
  currentEndComplete,
  colorFor,
  toSubmission,
} = useMatchPlay(setup)

const op = ref<InstanceType<typeof OverlayPanel> | null>(null)
const noteCtx = ref<{ playerId: number; noteIndex: number } | null>(null)
const shotType = ref<ShotType>('point')
const shotOptions = computed(() => [
  { label: t('play.shots.point'), value: 'point' as ShotType },
  { label: t('play.shots.tir'), value: 'tir' as ShotType },
])

function shotAt(playerId: number, idx: number): ShotType | undefined {
  const e = currentEnd.value
  const entry = e.balls.find((b) => b.playerId === playerId)
  const v = entry?.shotTypes[idx]
  return v as ShotType | undefined
}

function openNote(event: Event, playerId: number, noteIndex: number) {
  noteCtx.value = { playerId, noteIndex }
  // initialize shot type from existing note or default map
  const existing = shotAt(playerId, noteIndex)
  shotType.value = existing ?? (setup.defaultShotTypes?.[playerId] ?? 'point')
  op.value?.toggle(event)
}

function applyNote(val: -2 | -1 | 0 | 1 | 2) {
  if (!noteCtx.value) return
  setNoteWithShot(noteCtx.value.playerId, noteCtx.value.noteIndex, val, shotType.value)
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

function reopenEndDialog() {
  // Reopen the non-blocking dialog when user taps the bottom action
  winner.value = winner.value ?? null
  points.value = points.value ?? 1
  scoreDialog.value = true
}

function goPrev() { goPrevEnd() }
function goNext() { goNextEnd() }

async function onFinish() {
  const payload: CompleteMatchRequestDto = toSubmission()
  await matchesService.complete(matchId, payload)
  router.push({ name: 'matchSummary', params: { id: matchId } })
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
.validate-end { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #eee; padding: 0.5rem 0.5rem; display: grid; justify-items: center; }
.validate-end-btn { width: 100%; }
.finish { display: grid; justify-items: center; margin-top: 0.25rem; }
.finish-btn { width: 100%; }
</style>
