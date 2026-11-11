import { defineConfig, minimal2023Preset } from '@vite-pwa/assets-generator/config'

// Generates favicon, apple-touch-icon and the 64/192/512 + maskable PNGs
// from one source SVG. Replace public/logo.svg to rebrand.
export default defineConfig({
  headLinkOptions: { preset: '2023' },
  preset: {
    ...minimal2023Preset,
    maskable: { ...minimal2023Preset.maskable, resizeOptions: { background: '#0f766e' } },
    apple: { ...minimal2023Preset.apple, resizeOptions: { background: '#0f766e' } },
  },
  images: ['public/logo.svg'],
})
