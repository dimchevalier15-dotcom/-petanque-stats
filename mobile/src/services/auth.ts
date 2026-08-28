import api from './http'
import type { AuthResponseDto } from '../dto/auth/AuthResponse'
import type { AuthUserDto } from '../dto/auth/AuthUser'
import type { AuthSession } from '../models/AuthSession'
import type { User } from '../models/User'
import type { RegisterRequest } from '../dto/auth/RegisterRequest'
import type { PlayerItemDto } from '../dto/player/PlayerItem'
import type { Player } from '../models/Player'
import axios from 'axios'

function toPlayer(dto: PlayerItemDto): Player {
  return {
    id: dto.id,
    firstName: dto.firstName,
    lastName: dto.lastName,
    nickname: dto.nickname,
    clubId: dto.clubId ?? null,
    clubName: dto.clubName ?? null,
  }
}

export class AuthValidationError extends Error {
  readonly fields: Record<string, string>

  constructor(fields: Record<string, string>) {
    super('auth.errors.validation')
    this.fields = fields
  }
}

function mapAuthError(error: unknown): Error {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as
      | { error?: string; details?: Array<{ field: string; message: string }> }
      | undefined

    if (data?.error === 'invalid_request' && Array.isArray(data.details)) {
      const fields: Record<string, string> = {}
      for (const detail of data.details) {
        fields[detail.field] = detail.message
      }
      return new AuthValidationError(fields)
    }

    const code = data?.error
    if (code === 'email_already_used') {
      return new Error('auth.errors.emailAlreadyUsed')
    }
    if (code === 'player_already_linked') {
      return new Error('auth.errors.playerAlreadyLinked')
    }
    if (code === 'player_not_found') {
      return new Error('auth.errors.playerNotFound')
    }
    if (code === 'too_many_requests') {
      return new Error('auth.errors.tooManyRequests')
    }
    if (code === 'invalid_token') {
      return new Error('auth.reset.invalidToken')
    }
  }
  return new Error('auth.errors.generic')
}

export const authService = {
  async register(payload: RegisterRequest): Promise<AuthSession> {
    try {
      const { data } = await api.post<AuthResponseDto>('/auth/register', payload)
      const session: AuthSession = { token: data.token, user: data.user as unknown as User }
      return session
    } catch (error) {
      throw mapAuthError(error)
    }
  },
  async login(email: string, password: string): Promise<AuthSession> {
    const { data } = await api.post<AuthResponseDto>('/auth/login', { email, password })
    const session: AuthSession = { token: data.token, user: data.user as unknown as User }
    return session
  },
  async me(): Promise<User> {
    const { data } = await api.get<AuthUserDto>('/auth/me')
    return data as unknown as User
  },
  async forgotPassword(email: string): Promise<void> {
    try {
      await api.post('/auth/forgot-password', { email })
    } catch (error) {
      throw mapAuthError(error)
    }
  },
  async resetPassword(token: string, password: string): Promise<void> {
    try {
      await api.post('/auth/reset-password', { token, password })
    } catch (error) {
      throw mapAuthError(error)
    }
  },
  async searchUnlinkedPlayers(q: string): Promise<Player[]> {
    const { data } = await api.get<PlayerItemDto[]>('/auth/unlinked-players/search', { params: { q } })
    return data.map(toPlayer)
  },
  async resendVerification(): Promise<{ alreadyVerified: boolean }> {
    try {
      const { data } = await api.post<{ alreadyVerified?: boolean }>('/auth/resend-verification')
      return { alreadyVerified: data.alreadyVerified === true }
    } catch (error) {
      throw mapAuthError(error)
    }
  },
}
