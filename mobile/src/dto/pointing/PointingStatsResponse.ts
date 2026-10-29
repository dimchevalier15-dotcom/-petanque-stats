export interface PointingStatsSummaryDto {
  sessionsCount: number
  totalShots: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface PointingStatsEvolutionPointDto {
  sessionId: number
  date: string
  totalScore: number
}

export interface PointingStatsWorkshopDto {
  workshop: number
  shotCount: number
  averageScore: number
}

export interface PointingStatsDistanceDto {
  distance: number
  shotCount: number
  averageScore: number
}

export interface PointingStatsResultDto {
  result: string
  count: number
}

export interface PointingStatsCellDto {
  workshop: number
  distance: number
  shotCount: number
  averageScore: number
}

export interface PointingStatsResponseDto {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: PointingStatsSummaryDto
  evolution: PointingStatsEvolutionPointDto[]
  byWorkshop: PointingStatsWorkshopDto[]
  byDistance: PointingStatsDistanceDto[]
  byResult: PointingStatsResultDto[]
  heatmap: PointingStatsCellDto[]
}
