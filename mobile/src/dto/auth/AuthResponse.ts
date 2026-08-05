import type { AuthUserDto } from './AuthUser'

export interface AuthResponseDto {
  token: string
  user: AuthUserDto
}
