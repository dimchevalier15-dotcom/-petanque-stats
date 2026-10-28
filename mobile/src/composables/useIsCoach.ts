import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import type { User } from '../models/User'

export function useIsCoach() {
  const auth = useAuthStore()

  return computed(() => userIsClubCoach(auth.user))
}

export function useIsSelectionGlobaleCoach() {
  const auth = useAuthStore()

  return computed(() => userIsSelectionGlobaleCoach(auth.user))
}

export function userIsClubCoach(user: Pick<User, 'coachForClubId'> | null | undefined): boolean {
  return user?.coachForClubId != null
}

export function userIsSelectionGlobaleCoach(
  user: Pick<User, 'coachSelectionGlobales'> | null | undefined,
): boolean {
  return (user?.coachSelectionGlobales?.length ?? 0) > 0
}

/** @deprecated Prefer userIsClubCoach or userIsSelectionGlobaleCoach */
export function userIsCoach(user: User | null | undefined): boolean {
  return userIsClubCoach(user)
}
