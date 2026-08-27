import api from './http'
import type { AuthUserDto } from '../dto/auth/AuthUser'
import type { User } from '../models/User'

export interface UpdateUserCoachClubRequest {
  email: string
  clubId: number | null
}

export const adminUsersService = {
  async updateCoachClub(payload: UpdateUserCoachClubRequest): Promise<User> {
    const { data } = await api.put<AuthUserDto>('/admin/users/coach-club', payload)
    return data as unknown as User
  },
}
