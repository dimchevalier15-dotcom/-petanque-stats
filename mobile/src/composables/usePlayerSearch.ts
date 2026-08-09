import type { Player } from '../models/Player'

export function formatPlayerLabel(player: Player): string {
  const name = `${player.firstName} ${player.lastName}`.trim()
  return player.nickname ? `${player.nickname} (${name})` : name
}

export interface PlayerSearchOption {
  id: number
  label: string
}

export function playerToSearchOption(player: Player): PlayerSearchOption {
  return { id: player.id, label: formatPlayerLabel(player) }
}
