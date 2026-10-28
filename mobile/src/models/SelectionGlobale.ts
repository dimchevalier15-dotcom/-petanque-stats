export interface SelectionGlobaleItem {
  id: number
  name: string
}

export interface SelectionGlobaleCoachItem {
  userId: number
  email: string
}

export interface SelectionGlobaleDetail {
  id: number
  name: string
  coaches: SelectionGlobaleCoachItem[]
  players: import('./Player').Player[]
}
