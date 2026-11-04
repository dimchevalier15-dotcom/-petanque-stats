import { ref, watch } from 'vue'

export const JUMP_SHOT_PROGRESSION_MODES = ['easy', 'standard', 'hard'] as const
export type JumpShotProgressionMode = (typeof JUMP_SHOT_PROGRESSION_MODES)[number]

const STORAGE_KEY = 'jump_shot_progression_mode'

function readStoredMode(): JumpShotProgressionMode {
  const raw = localStorage.getItem(STORAGE_KEY)
  if (raw === 'simplified') {
    return 'easy'
  }
  if (raw === 'easy' || raw === 'standard' || raw === 'hard') {
    return raw
  }
  return 'standard'
}

export function useJumpShotProgressionMode() {
  const progressionMode = ref<JumpShotProgressionMode>(readStoredMode())

  watch(
    progressionMode,
    (mode) => {
      localStorage.setItem(STORAGE_KEY, mode)
    },
    { immediate: false },
  )

  return { progressionMode }
}
