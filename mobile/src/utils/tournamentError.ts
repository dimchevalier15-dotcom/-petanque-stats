import axios from "axios";

export function tournamentErrorMessage(
	error: unknown,
	translate: (key: string) => string,
	hasKey: (key: string) => boolean,
): string {
	if (!axios.isAxiosError(error)) {
		return translate("tournaments.errors.generic");
	}
	const data = error.response?.data as
		| { error?: string; errors?: Record<string, string> }
		| undefined;
	if (data?.error && hasKey(`tournaments.errors.${data.error}`)) {
		return translate(`tournaments.errors.${data.error}`);
	}
	if (data?.errors) {
		return Object.values(data.errors).join(" ");
	}
	return translate("tournaments.errors.generic");
}
