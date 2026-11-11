/// <reference types="vite/client" />
/// <reference types="vite-plugin-pwa/vue" />
/// <reference types="vite-plugin-pwa/info" />
/// <reference types="vite-plugin-pwa/pwa-assets" />

/** Release version (YEAR.M.P) from package.json, shown in the footer. */
declare const __APP_VERSION__: string

/** ISO timestamp of the build, shown when hovering the version. */
declare const __APP_BUILD_TIME__: string
