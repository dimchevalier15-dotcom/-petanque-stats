import { http } from './http'
import type { AuthResponseDto } from '../dto/auth/AuthResponse'
import type { AuthUserDto } from '../dto/auth/AuthUser'

export const AuthService = {
  async register(email: string, password: string): Promise<AuthResponseDto> {
    const { data } = await http.post<AuthResponseDto>('/api/auth/register', { email, password })
    return data
  },
  async login(email: string, password: string): Promise<AuthResponseDto> {
    const { data } = await http.post<AuthResponseDto>('/api/auth/login', { email, password })
    return data
  },
  async me(): Promise<AuthUserDto> {
    const { data } = await http.get<AuthUserDto>('/api/auth/me')
    return data
  },
}
