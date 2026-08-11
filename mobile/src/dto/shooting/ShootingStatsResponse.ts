export interface ShootingStatsSummaryDto {
  sessionsCount: number
  totalShots: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface ShootingStatsEvolutionPointDto {
  sessionId: number
  date: string
  totalScore: number
}

export interface ShootingStatsWorkshopDto {
  workshop: number
  shotCount: number
  averageScore: number
}

export interface ShootingStatsDistanceDto {
  distance: number
  shotCount: number
  averageScore: number
}

export interface ShootingStatsResultDto {
  result: string
  count: number
}

export interface ShootingStatsCellDto {
  workshop: number
  distance: number
  shotCount: number
  averageScore: number
}

export interface ShootingStatsResponseDto {
  status: 'ok' | 'no_sessions'
  summary: ShootingStatsSummaryDto
  evolution: ShootingStatsEvolutionPointDto[]
  byWorkshop: ShootingStatsWorkshopDto[]
  byDistance: ShootingStatsDistanceDto[]
  byResult: ShootingStatsResultDto[]
  heatmap: ShootingStatsCellDto[]
}
