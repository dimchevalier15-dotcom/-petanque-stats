import type { TrainingType } from '../../models/Training'

export interface CreateTrainingSessionRequestDto {
  type: TrainingType
  distance: number
  plannedBalls: number
}

export interface RecordTrainingAttemptRequestDto {
  result: string
}
