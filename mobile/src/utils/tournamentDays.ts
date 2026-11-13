import type { TournamentDay } from "../models/TournamentDay";

export function currentChampionshipDayId(days: TournamentDay[]): number | null {
	const championship = days.filter((day) => day.phase === "championship");
	if (championship.length === 0) {
		return null;
	}
	const openDay = championship.find((day) => day.status !== "closed");
	return openDay ? openDay.id : championship[championship.length - 1].id;
}

export function currentPlayoffDayId(days: TournamentDay[]): number | null {
	const playoff = days.filter((day) => day.phase === "playoff");
	if (playoff.length === 0) {
		return null;
	}
	for (const day of playoff) {
		const pending = day.matches.some(
			(match) => !match.fictional && match.score1 === null,
		);
		if (pending) {
			return day.id;
		}
	}
	const openDay = playoff.find((day) => day.status !== "closed");
	if (openDay) {
		return openDay.id;
	}
	return playoff[playoff.length - 1].id;
}
