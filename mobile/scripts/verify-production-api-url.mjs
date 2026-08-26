import { readFileSync, readdirSync } from 'node:fs'
import { join } from 'node:path'

const expected = 'https://api.petanque-analytics.com/api'
const assetsDir = join(process.cwd(), 'dist', 'assets')
const bundles = readdirSync(assetsDir).filter((name) => name.startsWith('index-') && name.endsWith('.js'))

if (bundles.length === 0) {
  console.error('Android build guard: no dist/assets/index-*.js bundle found.')
  process.exit(1)
}

const hasProductionApi = bundles.some((name) => readFileSync(join(assetsDir, name), 'utf8').includes(expected))

if (!hasProductionApi) {
  console.error(
    'Android build guard: VITE_API_URL was not baked into the production bundle.',
  )
  console.error(`Expected ${expected} in dist/assets/index-*.js`)
  console.error('Ensure mobile/.env.production exists before running npm run android:bundle.')
  process.exit(1)
}

console.log(`Android build guard: production API URL found in ${bundles.length} bundle(s).`)
