import { defineStore } from 'pinia'

const GUEST_SESSION_KEY = 'guest_session'

export const useGuestStore = defineStore('guest', {
  state: () => ({
    isGuestSession: false,
  }),
  actions: {
    initFromStorage() {
      this.isGuestSession = localStorage.getItem(GUEST_SESSION_KEY) === '1'
    },
    enterGuestMode() {
      this.isGuestSession = true
      localStorage.setItem(GUEST_SESSION_KEY, '1')
    },
    leaveGuestMode() {
      this.isGuestSession = false
      localStorage.removeItem(GUEST_SESSION_KEY)
    },
  },
})
