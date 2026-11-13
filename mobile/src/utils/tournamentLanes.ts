export function usableLaneNumbers(
	numberOfLanes: number,
	excludedLaneNumbers: number[],
): number[] {
	const excluded = new Set(excludedLaneNumbers);
	const usable: number[] = [];
	let candidate = 1;
	while (usable.length < numberOfLanes && candidate < 500) {
		if (!excluded.has(candidate)) {
			usable.push(candidate);
		}
		candidate += 1;
	}
	return usable;
}

export function parseLaneNumbers(value: string): number[] {
	if (value.trim() === "") {
		return [];
	}
	return value
		.split(/[,\s]+/)
		.map((part) => Number(part))
		.filter((lane) => Number.isInteger(lane) && lane > 0);
}
