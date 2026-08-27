import { computed } from 'vue'
import { userHasMasterAccess } from '../models/UserRole'
import { useAuthStore } from '../stores/auth'

export function useIsAdmin() {
  const auth = useAuthStore()

  return computed(() => userHasMasterAccess(auth.user))
}
