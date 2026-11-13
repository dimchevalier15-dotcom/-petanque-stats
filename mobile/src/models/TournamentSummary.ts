export interface TournamentSummary {
	id: number;
	name: string;
	description: string | null;
	status: "upcoming" | "in_progress" | "finished";
	format: string;
	startsAt: string | null;
	endsAt: string | null;
}
