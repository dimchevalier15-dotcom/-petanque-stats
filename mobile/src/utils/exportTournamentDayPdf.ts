export interface TournamentDayPdfRow {
  lane: number
  team1: string
  team2: string
}

export interface TournamentDayPdfColumns {
  lane: string
  team1: string
  team2: string
  score: string
}

export async function downloadTournamentDayPdf(
  fileBaseName: string,
  tournamentName: string,
  dayTitle: string,
  columns: TournamentDayPdfColumns,
  rows: TournamentDayPdfRow[],
): Promise<void> {
  const [{ jsPDF }, { default: autoTable }] = await Promise.all([
    import('jspdf'),
    import('jspdf-autotable'),
  ])
  const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
  const margin = 14
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(16)
  doc.text(tournamentName, margin, 18)
  doc.setFont('helvetica', 'normal')
  doc.setFontSize(12)
  doc.text(dayTitle, margin, 26)

  const sorted = [...rows].sort((left, right) => left.lane - right.lane)
  autoTable(doc, {
    startY: 32,
    head: [[columns.lane, columns.team1, columns.team2, columns.score]],
    body: sorted.map((row) => [String(row.lane), row.team1, row.team2, '']),
    styles: {
      fontSize: 11,
      cellPadding: { top: 4, right: 3, bottom: 4, left: 3 },
      overflow: 'linebreak',
      valign: 'middle',
    },
    headStyles: {
      fillColor: [30, 41, 59],
      textColor: 255,
      fontStyle: 'bold',
    },
    columnStyles: {
      0: { cellWidth: 22, halign: 'center' },
      3: { cellWidth: 26, halign: 'center' },
    },
    alternateRowStyles: { fillColor: [248, 250, 252] },
  })

  doc.save(`${sanitizeTournamentDayPdfFileName(fileBaseName)}.pdf`)
}

export function sanitizeTournamentDayPdfFileName(fileBaseName: string): string {
  const safe = fileBaseName
    .trim()
    .replace(/[^\p{L}\p{N}\-_]+/gu, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 80)

  return safe || 'journee'
}
