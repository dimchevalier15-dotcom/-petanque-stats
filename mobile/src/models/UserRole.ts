export const USER_ROLES = {
  SIMPLE_PLAYER: 'SIMPLE_PLAYER',
  MASTER: 'MASTER',
} as const

export type UserRole = (typeof USER_ROLES)[keyof typeof USER_ROLES]

export function userHasMasterAccess(user: { role?: string; isAdmin?: boolean } | null | undefined): boolean {
  return user?.role === USER_ROLES.MASTER || user?.isAdmin === true
}
