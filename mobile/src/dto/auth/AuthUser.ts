export interface AuthUserDto {
  id: number
  email: string
  playerId: number | null
  firstName?: string
  lastName?: string
  nickname?: string
  emailVerified?: boolean
  role?: string
  isAdmin?: boolean
  coachForClubId?: number | null
  coachForClubName?: string | null
}
