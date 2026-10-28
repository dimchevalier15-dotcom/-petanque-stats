import type { UserRole } from './UserRole'

export interface User {
  id: number
  email: string
  playerId: number | null
  firstName?: string
  lastName?: string
  nickname?: string
  emailVerified?: boolean
  role?: UserRole
  isAdmin?: boolean
  coachForClubId?: number | null
  coachForClubName?: string | null
  coachSelectionGlobales?: { id: number; name: string }[]
  requiresMatchValidation?: boolean
  pendingValidationCount?: number
}
