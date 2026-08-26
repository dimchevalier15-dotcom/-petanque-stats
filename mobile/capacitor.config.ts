import type { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'com.petanquestats.app',
  appName: 'Pétanque Analytics',
  webDir: 'dist',
  server: {
    androidScheme: 'https',
  },
  // Native HTTP so Android can call the production API without changing Symfony CORS
  // (WebView origin is https://localhost, which is not the API host).
  plugins: {
    CapacitorHttp: {
      enabled: true,
    },
  },
}

export default config
