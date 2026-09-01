import type { EndRecord } from '../models/MatchPlay'
import { isCochonnetAt } from './matchEndShots'

export function isCochonnetShot(end: EndRecord, playerId: number, slotIndex: number): boolean {
  return isCochonnetAt(end, playerId, slotIndex)
}
