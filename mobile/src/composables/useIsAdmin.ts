import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export function useIsAdmin() {
  const auth = useAuthStore()

  return computed(() => auth.user?.isAdmin === true)
}
