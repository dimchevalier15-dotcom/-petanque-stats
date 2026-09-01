export interface AppVersionConfigDto {
  latestVersion: string
  minimumVersion: string
  androidStoreUrl: string
}

export interface AppVersionConfig {
  latestVersion: string
  minimumVersion: string
  androidStoreUrl: string
}

function mapAppVersionConfig(dto: AppVersionConfigDto): AppVersionConfig {
  return {
    latestVersion: dto.latestVersion,
    minimumVersion: dto.minimumVersion,
    androidStoreUrl: dto.androidStoreUrl,
  }
}

export const appConfigService = {
  async getVersionConfig(): Promise<AppVersionConfig> {
    const { default: api } = await import('./http')
    const { data } = await api.get<AppVersionConfigDto>('/config')
    return mapAppVersionConfig(data)
  },
}
