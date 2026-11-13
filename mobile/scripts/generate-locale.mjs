/**
 * One-off: generate cs.json / de.json from en.json via Google Translate (unofficial API).
 * Preserves vue-i18n placeholders: {name}, @:link, HTML tags, and plural sub-keys.
 */
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { translate } from 'google-translate-api-x'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const localesDir = path.join(__dirname, '../src/i18n/locales')
const sourcePath = path.join(localesDir, 'en.json')

const PLACEHOLDER_RE = /\{[^{}]+\}|@:\w+|<[^>]+>/g

function protectPlaceholders(text) {
  const tokens = []
  const safe = text.replace(PLACEHOLDER_RE, (match) => {
    const id = tokens.length
    tokens.push(match)
    return `⟦${id}⟧`
  })
  return { safe, tokens }
}

function restorePlaceholders(text, tokens) {
  return text.replace(/⟦(\d+)⟧/g, (_, id) => tokens[Number(id)] ?? _)
}

function collectLeaves(obj, out = []) {
  if (obj === null || typeof obj !== 'object' || Array.isArray(obj)) {
    return out
  }
  for (const value of Object.values(obj)) {
    if (typeof value === 'string') {
      out.push(value)
    } else if (value && typeof value === 'object') {
      collectLeaves(value, out)
    }
  }
  return out
}

function setLeavesFromMap(obj, map) {
  if (obj === null || typeof obj !== 'object' || Array.isArray(obj)) {
    return obj
  }
  const next = Array.isArray(obj) ? [...obj] : { ...obj }
  for (const [key, value] of Object.entries(obj)) {
    if (typeof value === 'string') {
      next[key] = map.get(value) ?? value
    } else if (value && typeof value === 'object') {
      next[key] = setLeavesFromMap(value, map)
    }
  }
  return next
}

async function translateOne(text, to) {
  const { safe, tokens } = protectPlaceholders(text)
  const result = await translate(safe, {
    from: 'en',
    to,
    forceBatch: false,
    requestsPerSecond: 0.5,
  })
  return restorePlaceholders(result.text, tokens)
}

async function translateBatch(texts, to) {
  const prepared = texts.map((t) => protectPlaceholders(t))
  const input = prepared.map((p) => p.safe)
  let arr
  try {
    const results = await translate(input, {
      from: 'en',
      to,
      requestsPerSecond: 0.75,
      rejectOnPartialFail: false,
    })
    arr = Array.isArray(results) ? results : [results]
  } catch {
    arr = await Promise.all(input.map((s) => translate(s, { from: 'en', to, forceBatch: false })))
  }
  const out = []
  for (let i = 0; i < texts.length; i++) {
    const text = arr[i]?.text
    if (text) {
      out.push(restorePlaceholders(text, prepared[i].tokens))
    } else {
      out.push(await translateOne(texts[i], to))
    }
  }
  return out
}

async function buildLocale(to, outFile) {
  const source = JSON.parse(fs.readFileSync(sourcePath, 'utf8'))
  const unique = [...new Set(collectLeaves(source))]
  console.log(`Translating ${unique.length} unique strings → ${to}…`)

  const map = new Map()
  const batchSize = 25
  for (let i = 0; i < unique.length; i += batchSize) {
    const batch = unique.slice(i, i + batchSize)
    const translated = await translateBatch(batch, to)
    batch.forEach((orig, j) => map.set(orig, translated[j]))
    process.stdout.write(`  ${Math.min(i + batchSize, unique.length)}/${unique.length}\r`)
  }
  console.log()

  const output = setLeavesFromMap(source, map)
  fs.writeFileSync(outFile, `${JSON.stringify(output, null, 2)}\n`, 'utf8')
  console.log(`Wrote ${outFile}`)
}

const target = process.argv[2]
if (!target || !['cs', 'de'].includes(target)) {
  console.error('Usage: node scripts/generate-locale.mjs <cs|de>')
  process.exit(1)
}

await buildLocale(target, path.join(localesDir, `${target}.json`))
