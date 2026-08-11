import { computed, ref } from 'vue'

function startOfDay(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate())
}

function subtractMonths(date: Date, months: number): Date {
  const result = new Date(date)
  result.setMonth(result.getMonth() - months)
  return startOfDay(result)
}

export function toInputDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export interface StatsDateRangeParams {
  from: string
  to: string
}

export function useStatsDateRange() {
  const today = startOfDay(new Date())
  const defaultFrom = subtractMonths(today, 1)

  const dateFrom = ref(toInputDate(defaultFrom))
  const dateTo = ref(toInputDate(today))
  const maxDate = toInputDate(today)

  const isValid = computed(() => dateFrom.value <= dateTo.value)

  function normalizeRange(): void {
    if (dateFrom.value > dateTo.value) {
      const previousFrom = dateFrom.value
      dateFrom.value = dateTo.value
      dateTo.value = previousFrom
    }
    if (dateTo.value > maxDate) {
      dateTo.value = maxDate
    }
    if (dateFrom.value > maxDate) {
      dateFrom.value = maxDate
    }
  }

  function queryParams(): { from: string; to: string } {
    normalizeRange()
    return { from: dateFrom.value, to: dateTo.value }
  }

  return {
    dateFrom,
    dateTo,
    maxDate,
    isValid,
    normalizeRange,
    queryParams,
  }
}
