import { defineStore } from 'pinia'
import type { Player } from '../models/Player'

const STORAGE_KEY = 'impersonate_player'

function readStoredPlayer(): Player | null {
  const raw = sessionStorage.getItem(STORAGE_KEY)
  if (!raw) {
    return null
  }
  try {
    const parsed = JSON.parse(raw) as Player
    if (typeof parsed.id !== 'number') {
      return null
    }
    return parsed
  } catch {
    return null
  }
}

export const useImpersonationStore = defineStore('impersonation', {
  state: () => ({
    player: null as Player | null,
  }),
  getters: {
    isActive: (state) => state.player !== null,
  },
  actions: {
    initFromStorage() {
      this.player = readStoredPlayer()
    },
    setPlayer(player: Player | null) {
      this.player = player
      if (player) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(player))
      } else {
        sessionStorage.removeItem(STORAGE_KEY)
      }
    },
    clear() {
      this.setPlayer(null)
    },
  },
})
