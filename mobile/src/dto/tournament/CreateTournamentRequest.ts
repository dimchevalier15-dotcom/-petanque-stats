export interface CreateTournamentRequest {
	name: string;
	description: string | null;
	administratorUserIds: number[];
	minPlayersPerTeam: number;
	maxPlayersPerTeam: number;
	pointsForWin: number;
	pointsForDraw: number;
	drawsAllowed: boolean;
	numberOfLanes: number;
	excludedLaneNumbers: number[];
	startsAt: string | null;
	endsAt: string | null;
}
