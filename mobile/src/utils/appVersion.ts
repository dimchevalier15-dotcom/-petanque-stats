import { App } from '@capacitor/app'
import { Capacitor } from '@capacitor/core'

export async function getInstalledAppVersion(): Promise<string> {
  if (Capacitor.isNativePlatform()) {
    const info = await App.getInfo()
    return info.version
  }

  return import.meta.env.VITE_APP_VERSION ?? '0.0.0'
}
