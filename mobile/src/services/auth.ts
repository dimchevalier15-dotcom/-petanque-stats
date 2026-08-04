import api from './http'

export type AuthUser = {
  id: number
  email: string
  playerId: number | null
}

export type AuthResponse = {
  token: string
  user: AuthUser
}

export const authService = {
  async register(email: string, password: string): Promise<AuthResponse> {
    const { data } = await api.post<AuthResponse>('/auth/register', { email, password })
    return data
  },
  async login(email: string, password: string): Promise<AuthResponse> {
    const { data } = await api.post<AuthResponse>('/auth/login', { email, password })
    return data
  },
  async me(): Promise<AuthUser> {
    const { data } = await api.get<AuthUser>('/auth/me')
    return data
  },
}
