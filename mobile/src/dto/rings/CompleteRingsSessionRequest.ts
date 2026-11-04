import type { RingsTarget } from '../../models/Rings'

export interface RingsThrowInputDto {
  target: RingsTarget
  success: boolean
}

export interface CompleteRingsSessionRequestDto {
  throws: RingsThrowInputDto[]
}
