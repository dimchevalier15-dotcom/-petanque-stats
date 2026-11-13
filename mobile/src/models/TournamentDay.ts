export interface TournamentMatchSide {
	teamId: number | null;
	fakeTeamId: number | null;
	name: string | null;
	fictional: boolean;
	pendingWinnerOfRound: string | null;
	pendingWinnerOfSlot: number | null;
}

export interface TournamentMatch {
	id: number;
	team1: TournamentMatchSide;
	team2: TournamentMatchSide;
	score1: number | null;
	score2: number | null;
	laneNumber: number;
	status: "scheduled" | "finished";
	fictional: boolean;
	phase: "championship" | "playoff";
	playoffRound: string | null;
	playoffSlot: number | null;
	highlighted: boolean;
	viewerCanEditScore: boolean;
}

export interface TournamentDay {
	id: number;
	number: number;
	phase: "championship" | "playoff";
	status: "generated" | "in_progress" | "closed";
	playoffRound: string | null;
	closedAt: string | null;
	excludedLaneNumbers: number[];
	usableLaneNumbers: number[];
	readyToClose: boolean;
	deletable: boolean;
	matches: TournamentMatch[];
}
