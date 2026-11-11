<script setup lang="ts">
import { useRegisterSW } from 'virtual:pwa-register/vue'

/**
 * Shows "A new version is available" after a deploy. The new service worker
 * waits until the user taps Reload, so nobody loses a half-filled form.
 *
 * Installed PWAs can stay open for days, so besides the browser's own
 * checks we look for updates hourly and whenever the app returns to the
 * foreground.
 */
const CHECK_INTERVAL_MS = 60 * 60 * 1000

const { needRefresh, updateServiceWorker } = useRegisterSW({
  onRegisteredSW(swUrl, registration) {
    if (!registration) return

    const check = async () => {
      if (registration.installing || !navigator.onLine) return
      // Skip if the server is unreachable or sw.js is gone, rather than throwing.
      const response = await fetch(swUrl, { cache: 'no-store' }).catch(() => null)
      if (response?.status === 200) await registration.update()
    }

    setInterval(check, CHECK_INTERVAL_MS)
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') void check()
    })
  },
})

function dismiss(): void {
  needRefresh.value = false
}
</script>

<template>
  <div v-if="needRefresh" class="update-banner" role="status">
    <span>A new version is available.</span>
    <div class="actions">
      <button type="button" class="link" @click="dismiss">Later</button>
      <button type="button" @click="updateServiceWorker(true)">Reload</button>
    </div>
  </div>
</template>

<style scoped>
.update-banner {
  position: fixed;
  inset: auto 1rem calc(1rem + env(safe-area-inset-bottom)) 1rem;
  max-width: 30rem;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius);
  background: var(--text);
  color: var(--bg);
  box-shadow: 0 6px 24px rgb(0 0 0 / 0.2);
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.update-banner .link {
  color: inherit;
}
</style>
