import api from './http'
import type { AuthResponseDto } from '../dto/auth/AuthResponse'
import type { AuthUserDto } from '../dto/auth/AuthUser'
import type { AuthSession } from '../models/AuthSession'
import type { User } from '../models/User'

export const authService = {
  async register(email: string, password: string): Promise<AuthSession> {
    const { data } = await api.post<AuthResponseDto>('/auth/register', { email, password })
    // Map DTO -> Model (shapes currently equivalent)
    const session: AuthSession = { token: data.token, user: data.user as unknown as User }
    return session
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
}
