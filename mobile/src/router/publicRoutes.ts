export const PUBLIC_ROUTE_NAMES = [
  'login',
  'register',
  'forgotPassword',
  'resetPassword',
  'privacy',
  'terms',
  'legal',
  'deleteAccount',
  'liveMatch',
  'newMatch',
  'matchScore',
  'guestMatchSummary',
] as const

export const GUEST_ONLY_ROUTE_NAMES = ['login', 'register'] as const

export const LEGAL_PATHS = {
  privacy: '/privacy',
  terms: '/terms',
  legal: '/legal',
  deleteAccount: '/delete-account',
} as const
