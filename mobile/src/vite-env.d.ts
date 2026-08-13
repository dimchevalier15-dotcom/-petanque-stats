/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** API base URL (e.g. `/api` behind nginx, or `https://api.example.com`) */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
