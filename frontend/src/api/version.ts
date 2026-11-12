import { computed, ref } from 'vue'

/** This build's release (YEAR.M.P), baked in from package.json. */
export const clientVersion = __APP_VERSION__

/** The release of the server that last answered (X-App-Version header). */
export const serverVersion = ref<string | null>(null)

/**
 * False when the installed app and the server come from different releases,
 * usually because an update is waiting to be applied. Unknown counts as a match.
 */
export const versionsMatch = computed(
  () => serverVersion.value === null || serverVersion.value === clientVersion,
)

export function recordServerVersion(headers: Record<string, unknown> | undefined): void {
  const version = headers?.['x-app-version']
  if (typeof version === 'string' && version !== '') serverVersion.value = version
}
