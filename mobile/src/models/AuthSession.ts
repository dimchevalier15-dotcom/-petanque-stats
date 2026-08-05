import type { User } from './User'

export interface AuthSession {
  token: string
  user: User
}
