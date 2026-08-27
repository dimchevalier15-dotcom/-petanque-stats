import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export function useIsCoach() {
  const auth = useAuthStore()

  return computed(() => auth.user?.coachForClubId != null)
}

export function userIsCoach(user: { coachForClubId?: number | null } | null | undefined): boolean {
  return user?.coachForClubId != null
}
