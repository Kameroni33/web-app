<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { listSessions, signOutOtherSessions, type DeviceSession } from '@/api/auth'
import { errorMessage } from '@/api/http'

const sessions = ref<DeviceSession[]>([])
const loading = ref(true)
const busy = ref(false)
const confirming = ref(false)
const error = ref('')

const others = computed(() => sessions.value.filter((s) => !s.is_current).length)

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    sessions.value = await listSessions()
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

async function signOutOthers(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    await signOutOtherSessions()
    confirming.value = false
    await load()
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

/** A readable device name from a user agent, good enough for "which one is this?". */
function describe(userAgent: string | null): string {
  if (!userAgent) return 'Unknown device'
  const os =
    [
      [/iPhone/, 'iPhone'],
      [/iPad/, 'iPad'],
      [/Android/, 'Android'],
      [/Mac OS X|Macintosh/, 'Mac'],
      [/Windows/, 'Windows'],
      [/Linux/, 'Linux'],
    ].find(([pattern]) => (pattern as RegExp).test(userAgent))?.[1] ?? 'Unknown device'
  const browser =
    [
      [/Edg\//, 'Edge'],
      [/Firefox|FxiOS/, 'Firefox'],
      [/Chrome|CriOS/, 'Chrome'],
      [/Safari/, 'Safari'],
    ].find(([pattern]) => (pattern as RegExp).test(userAgent))?.[1] ?? ''
  return browser ? `${browser} on ${os}` : (os as string)
}

function lastActive(iso: string): string {
  return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <RouterLink to="/settings" class="back-link">‹ Settings</RouterLink>
    <h1>Your devices</h1>
    <p class="muted">Everywhere you’re signed in. Lost a phone? Sign out everywhere else.</p>

    <p v-if="loading" class="muted">Loading…</p>

    <ul v-else class="card device-list">
      <li v-for="(session, index) in sessions" :key="index">
        <div>
          <strong>{{ describe(session.user_agent) }}</strong>
          <span v-if="session.is_current" class="badge">This device</span>
        </div>
        <div class="muted small">
          Last active {{ lastActive(session.last_active_at) }}
          <template v-if="session.ip_address"> · {{ session.ip_address }}</template>
        </div>
      </li>
    </ul>

    <div v-if="!loading && others > 0">
      <button v-if="!confirming" type="button" class="danger" @click="confirming = true">
        Sign out all other devices
      </button>
      <div v-else class="card stack">
        <p>
          Sign out of {{ others }} other {{ others === 1 ? 'device' : 'devices' }}? They’ll need a
          new code to get back in.
        </p>
        <div class="row">
          <button type="button" class="danger" :disabled="busy" @click="signOutOthers">
            {{ busy ? 'Signing out…' : 'Yes, sign them out' }}
          </button>
          <button type="button" class="link" @click="confirming = false">Cancel</button>
        </div>
      </div>
    </div>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
  </main>
</template>

<style scoped>
.device-list {
  list-style: none;
  padding: 0;
}

.device-list li {
  padding: 0.75rem 1rem;
}

.device-list li + li {
  border-top: 1px solid var(--border);
}

.badge {
  margin-left: 0.5rem;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 0.8rem;
}

.small {
  font-size: 0.875rem;
}
</style>
