import { http } from './http'

export type LoggedUser = {
  id: number
  email: string
  playerId: number
}

export type AuthResponse = {
  token: string
  user: LoggedUser
}

export const AuthService = {
  async register(email: string, password: string): Promise<AuthResponse> {
    const { data } = await http.post<AuthResponse>('/api/auth/register', { email, password })
    return data
  },
  async login(email: string, password: string): Promise<AuthResponse> {
    const { data } = await http.post<AuthResponse>('/api/auth/login', { email, password })
    return data
  },
  async me(): Promise<LoggedUser> {
    const { data } = await http.get<LoggedUser>('/api/auth/me')
    return data
  },
}
