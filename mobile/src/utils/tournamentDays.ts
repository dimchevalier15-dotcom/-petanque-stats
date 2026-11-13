import type { TournamentDay } from "../models/TournamentDay";

export function currentChampionshipDayId(days: TournamentDay[]): number | null {
	const championship = days.filter((day) => day.phase === "championship");
	if (championship.length === 0) {
		return null;
	}
	const openDay = championship.find((day) => day.status !== "closed");
	return openDay ? openDay.id : championship[championship.length - 1].id;
}
