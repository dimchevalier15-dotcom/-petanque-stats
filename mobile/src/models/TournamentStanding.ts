export interface TournamentStanding {
	position: number;
	teamId: number;
	teamName: string;
	played: number;
	wins: number;
	draws: number;
	losses: number;
	points: number;
	pointsFor: number;
	pointsAgainst: number;
	pointsDifference: number;
	tieUnresolved: boolean;
	qualifiedForPlayoffs: boolean;
}

export interface TournamentRanking {
	rows: TournamentStanding[];
	tieUnresolved: boolean;
}
