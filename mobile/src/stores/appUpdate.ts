import { defineStore } from 'pinia'

const DISMISS_STORAGE_KEY = 'app_update_dismissed_latest'

export type AppUpdateStatus = 'idle' | 'upToDate' | 'recommended' | 'required'

function readDismissedLatestVersion(): string | null {
  return localStorage.getItem(DISMISS_STORAGE_KEY)
}

export const useAppUpdateStore = defineStore('appUpdate', {
  state: () => ({
    status: 'idle' as AppUpdateStatus,
    storeUrl: null as string | null,
    latestVersion: null as string | null,
    checkStarted: false,
    dismissedLatestVersion: readDismissedLatestVersion(),
  }),
  getters: {
    showRequiredBlock(state): boolean {
      return state.status === 'required'
    },
    showRecommendedBanner(state): boolean {
      if (state.status !== 'recommended' || !state.latestVersion) {
        return false
      }
      return state.dismissedLatestVersion !== state.latestVersion
    },
  },
  actions: {
    async checkOnce(): Promise<void> {
      if (this.checkStarted) {
        return
      }
      this.checkStarted = true

      const { Capacitor } = await import('@capacitor/core')
      if (Capacitor.getPlatform() !== 'android') {
        this.status = 'upToDate'
        return
      }

      try {
        const [{ getInstalledAppVersion }, { appConfigService }, { isVersionLowerThan }] = await Promise.all([
          import('../utils/appVersion'),
          import('../services/appConfig'),
          import('../utils/compareVersions'),
        ])

        const [installedVersion, config] = await Promise.all([
          getInstalledAppVersion(),
          appConfigService.getVersionConfig(),
        ])

        this.storeUrl = config.androidStoreUrl
        this.latestVersion = config.latestVersion

        if (isVersionLowerThan(installedVersion, config.minimumVersion)) {
          this.status = 'required'
          return
        }

        if (isVersionLowerThan(installedVersion, config.latestVersion)) {
          this.status = 'recommended'
          return
        }

        this.status = 'upToDate'
      } catch {
        this.status = 'upToDate'
      }
    },
    dismissRecommended(): void {
      if (!this.latestVersion) {
        return
      }
      this.dismissedLatestVersion = this.latestVersion
      localStorage.setItem(DISMISS_STORAGE_KEY, this.latestVersion)
    },
    openStore(): void {
      if (!this.storeUrl) {
        return
      }
      window.open(this.storeUrl, '_blank', 'noopener,noreferrer')
    },
  },
})
