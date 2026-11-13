import type { CreateTournamentRequest } from "../dto/tournament/CreateTournamentRequest";
import type { TournamentDetailResponse } from "../dto/tournament/TournamentDetailResponse";
import type { TournamentSummaryResponse } from "../dto/tournament/TournamentSummaryResponse";
import type { TournamentUserSearchResponse } from "../dto/tournament/TournamentUserSearchResponse";
import type { TournamentDetail } from "../models/TournamentDetail";
import type { TournamentSummary } from "../models/TournamentSummary";
import api from "./http";

function toSummary(dto: TournamentSummaryResponse): TournamentSummary {
	return {
		id: dto.id,
		name: dto.name,
		description: dto.description,
		status: dto.status,
		format: dto.format,
		startsAt: dto.startsAt,
		endsAt: dto.endsAt,
	};
}

function toDetail(dto: TournamentDetailResponse): TournamentDetail {
	return {
		...toSummary(dto),
		minPlayersPerTeam: dto.minPlayersPerTeam,
		maxPlayersPerTeam: dto.maxPlayersPerTeam,
		pointsForWin: dto.pointsForWin,
		pointsForDraw: dto.pointsForDraw,
		drawsAllowed: dto.drawsAllowed,
		numberOfLanes: dto.numberOfLanes,
		excludedLaneNumbers: [...dto.excludedLaneNumbers],
		viewerIsAdmin: dto.viewerIsAdmin,
		viewerTeamId: dto.viewerTeamId,
		playoffsGenerated: dto.playoffsGenerated,
		teams: dto.teams.map((team) => ({
			id: team.id,
			name: team.name,
			players: team.players.map((player) => ({ ...player })),
		})),
		members: dto.members?.map((member) => ({ ...member })) ?? null,
		days: dto.days.map((day) => ({
			...day,
			excludedLaneNumbers: [...day.excludedLaneNumbers],
			usableLaneNumbers: [...day.usableLaneNumbers],
			matches: day.matches.map((match) => ({
				...match,
				team1: { ...match.team1 },
				team2: { ...match.team2 },
			})),
		})),
		ranking: {
			tieUnresolved: dto.ranking.tieUnresolved,
			rows: dto.ranking.rows.map((row) => ({ ...row })),
		},
	};
}

export const tournamentsService = {
	async list(): Promise<TournamentSummary[]> {
		const { data } = await api.get<TournamentSummaryResponse[]>("/tournaments");
		return data.map(toSummary);
	},
	async get(id: number): Promise<TournamentDetail> {
		const { data } = await api.get<TournamentDetailResponse>(
			`/tournaments/${id}`,
		);
		return toDetail(data);
	},
	async create(payload: CreateTournamentRequest): Promise<TournamentDetail> {
		const { data } = await api.post<TournamentDetailResponse>(
			"/tournaments",
			payload,
		);
		return toDetail(data);
	},
	async update(
		id: number,
		payload: Omit<CreateTournamentRequest, "administratorUserIds">,
	): Promise<TournamentDetail> {
		const { data } = await api.put<TournamentDetailResponse>(
			`/tournaments/${id}`,
			payload,
		);
		return toDetail(data);
	},
	async searchUsers(query: string): Promise<TournamentUserSearchResponse[]> {
		const { data } = await api.get<TournamentUserSearchResponse[]>(
			"/tournaments/user-search",
			{ params: { q: query } },
		);
		return data;
	},
	async addMember(
		tournamentId: number,
		userId: number,
		admin = false,
	): Promise<TournamentDetail> {
		const { data } = await api.post<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/members`,
			{ userId, admin },
		);
		return toDetail(data);
	},
	async updateMember(
		tournamentId: number,
		userId: number,
		admin: boolean,
	): Promise<TournamentDetail> {
		const { data } = await api.patch<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/members/${userId}`,
			{ admin },
		);
		return toDetail(data);
	},
	async removeMember(
		tournamentId: number,
		userId: number,
	): Promise<TournamentDetail> {
		const { data } = await api.delete<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/members/${userId}`,
		);
		return toDetail(data);
	},
	async createTeam(
		tournamentId: number,
		name: string,
		playerIds: number[],
	): Promise<TournamentDetail> {
		const { data } = await api.post<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/teams`,
			{ name, playerIds },
		);
		return toDetail(data);
	},
	async renameTeam(
		tournamentId: number,
		teamId: number,
		name: string,
	): Promise<TournamentDetail> {
		const { data } = await api.patch<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/teams/${teamId}`,
			{ name },
		);
		return toDetail(data);
	},
	async deleteTeam(
		tournamentId: number,
		teamId: number,
	): Promise<TournamentDetail> {
		const { data } = await api.delete<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/teams/${teamId}`,
		);
		return toDetail(data);
	},
	async addPlayer(
		tournamentId: number,
		teamId: number,
		playerId: number,
	): Promise<TournamentDetail> {
		const { data } = await api.post<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/teams/${teamId}/players`,
			{ playerId },
		);
		return toDetail(data);
	},
	async removePlayer(
		tournamentId: number,
		teamId: number,
		playerId: number,
	): Promise<TournamentDetail> {
		const { data } = await api.delete<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/teams/${teamId}/players/${playerId}`,
		);
		return toDetail(data);
	},
	async generateDay(
		tournamentId: number,
		payload: {
			absentTeamIds: number[];
			fakeTeamNames: string[];
			excludedLaneNumbers: number[];
		},
	): Promise<TournamentDetail> {
		await api.post(`/tournaments/${tournamentId}/days`, payload);
		return this.get(tournamentId);
	},
	async closeDay(
		tournamentId: number,
		dayId: number,
	): Promise<TournamentDetail> {
		await api.post(`/tournaments/${tournamentId}/days/${dayId}/close`);
		return this.get(tournamentId);
	},
	async updateTeams(
		tournamentId: number,
		matchId: number,
		team1Id: number,
		team2Id: number,
	): Promise<TournamentDetail> {
		await api.patch(`/tournaments/${tournamentId}/matches/${matchId}/teams`, {
			team1Id,
			team2Id,
		});
		return this.get(tournamentId);
	},
	async updateLane(
		tournamentId: number,
		matchId: number,
		laneNumber: number,
	): Promise<TournamentDetail> {
		await api.patch(`/tournaments/${tournamentId}/matches/${matchId}/lane`, {
			laneNumber,
		});
		return this.get(tournamentId);
	},
	async updateScore(
		tournamentId: number,
		matchId: number,
		payload: { score1: number; score2: number } | { clear: true },
	): Promise<TournamentDetail> {
		await api.patch(
			`/tournaments/${tournamentId}/matches/${matchId}/score`,
			payload,
		);
		return this.get(tournamentId);
	},
	async generatePlayoffs(tournamentId: number): Promise<TournamentDetail> {
		const { data } = await api.post<TournamentDetailResponse>(
			`/tournaments/${tournamentId}/playoffs`,
		);
		return toDetail(data);
	},
};
