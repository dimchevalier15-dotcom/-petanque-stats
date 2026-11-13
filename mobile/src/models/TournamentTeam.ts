export interface TournamentTeamPlayer {
	id: number;
	firstName: string;
	lastName: string;
	nickname: string;
	userId: number | null;
}

export interface TournamentTeam {
	id: number;
	name: string;
	players: TournamentTeamPlayer[];
}
