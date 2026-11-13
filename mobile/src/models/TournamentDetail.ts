import type { TournamentDay } from "./TournamentDay";
import type { TournamentMember } from "./TournamentMember";
import type { TournamentStanding } from "./TournamentStanding";
import type { TournamentSummary } from "./TournamentSummary";
import type { TournamentTeam } from "./TournamentTeam";

export interface TournamentDetail extends TournamentSummary {
	publicUuid: string;
	minPlayersPerTeam: number;
	maxPlayersPerTeam: number;
	pointsForWin: number;
	pointsForDraw: number;
	drawsAllowed: boolean;
	numberOfLanes: number;
	excludedLaneNumbers: number[];
	viewerIsAdmin: boolean;
	viewerTeamId: number | null;
	playoffsGenerated: boolean;
	teams: TournamentTeam[];
	members: TournamentMember[] | null;
	days: TournamentDay[];
	ranking: { rows: TournamentStanding[]; tieUnresolved: boolean };
}
