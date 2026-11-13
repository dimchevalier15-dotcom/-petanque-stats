import api from './http'

export interface PlayerMergeSide {
  id: number
  firstName: string
  lastName: string
  nickname: string
  clubName: string | null
  userId: number | null
  userEmail: string | null
}

export interface PlayerMergePreview {
  keep: PlayerMergeSide
  drop: PlayerMergeSide
  requiresUserChoice: boolean
}

export const adminPlayersService = {
  async mergePreview(keepPlayerId: number, dropPlayerId: number): Promise<PlayerMergePreview> {
    const { data } = await api.get<PlayerMergePreview>('/admin/players/merge-preview', {
      params: { keepPlayerId, dropPlayerId },
    })
    return data
  },

  async merge(keepPlayerId: number, dropPlayerId: number, keepUserId: number | null): Promise<void> {
    await api.post('/admin/players/merge', {
      keepPlayerId,
      dropPlayerId,
      keepUserId,
    })
  },
}
