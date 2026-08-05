export interface User {
  id: number
  email: string
  playerId: number | null
  firstName?: string
  lastName?: string
  nickname?: string
}
