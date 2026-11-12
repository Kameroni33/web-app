import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'
import {
  defaultAssetName,
  defineConfig,
  minimal2023Preset,
} from '@vite-pwa/assets-generator/config'

// Generates favicon, apple-touch-icon and the 64/192/512 + maskable PNGs
// from one source SVG. Replace public/logo.svg to rebrand.
const SOURCE = 'public/logo.svg'
const BACKGROUND = '#0f766e'

// Icon filenames carry a hash of the logo, so a new logo gets new URLs and
// browsers (and Android's installed-app icon check) can't reuse old copies.
const logoHash = createHash('sha256').update(readFileSync(SOURCE)).digest('hex').slice(0, 8)
const versioned = (name: string) => name.replace(/\.(png|ico)$/, `-${logoHash}.$1`)

export default defineConfig({
  headLinkOptions: { preset: '2023' },
  preset: {
    ...minimal2023Preset,
    // The logo already has its own margin; don't shrink it further.
    transparent: {
      ...minimal2023Preset.transparent,
      padding: 0,
      favicons: [[48, versioned('favicon.ico')]],
    },
    // Android may crop these to a circle: keep the K inside the safe zone.
    maskable: {
      ...minimal2023Preset.maskable,
      padding: 0.15,
      resizeOptions: { background: BACKGROUND },
    },
    // iOS rounds the corners itself, so fill the whole square.
    apple: { ...minimal2023Preset.apple, padding: 0, resizeOptions: { background: BACKGROUND } },
    assetName: (type, size) => versioned(defaultAssetName(type, size)),
  },
  images: [SOURCE],
})
