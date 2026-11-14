<script setup lang="ts">
import { useAppNavigation } from '@/composables/useAppNavigation'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { errorMessage } from '@/api/http'
import { listFamilies, listProfiles, type Family } from '@/api/profiles'
import { clientVersion, serverVersion, versionsMatch } from '@/api/version'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { useAppUpdate } from '@/composables/useAppUpdate'
import { useTheme, type Theme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const nav = useAppNavigation()
const { theme, setTheme } = useTheme()
const update = useAppUpdate()

const families = ref<Family[] | null>(null)
const familiesError = ref('')
const profileCount = ref(1)
const updateStatus = ref('')

const themes: { value: Theme; label: string }[] = [
  { value: 'system', label: 'System' },
  { value: 'light', label: 'Light' },
  { value: 'dark', label: 'Dark' },
]

async function load(): Promise<void> {
  familiesError.value = ''
  const [familyResult, profileResult] = await Promise.allSettled([listFamilies(), listProfiles()])
  if (familyResult.status === 'fulfilled') families.value = familyResult.value
  else familiesError.value = errorMessage(familyResult.reason)
  if (profileResult.status === 'fulfilled') profileCount.value = profileResult.value.length
}

async function checkForUpdate(): Promise<void> {
  updateStatus.value = ''
  if (!update.supported()) {
    updateStatus.value = 'Updates are checked automatically in the installed app.'
    return
  }
  const found = await update.checkForUpdate()
  updateStatus.value = found ? '' : 'You’re on the latest version.'
}

async function switchProfile(): Promise<void> {
  await router.push({ name: 'choose-profile' })
}

async function signOut(): Promise<void> {
  await auth.signOut().catch(() => undefined)
  await nav.startAt('/login')
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <h1>Settings</h1>

    <section v-if="auth.profile" class="card stack">
      <div class="me">
        <ProfileAvatar
          :name="auth.profile.name"
          :id="auth.profile.id"
          :src="auth.profile.avatar_url"
          :size="48"
        />
        <div class="who">
          <strong>{{ auth.profile.name }}</strong>
          <span class="muted small">{{ auth.user?.email }}</span>
        </div>
      </div>
      <div class="row">
        <RouterLink :to="{ name: 'settings-profiles' }">Manage profiles</RouterLink>
        <button v-if="profileCount > 1" type="button" class="link" @click="switchProfile">
          Switch profile
        </button>
      </div>
    </section>

    <section class="card stack">
      <h2>Families</h2>
      <p v-if="familiesError" class="error">{{ familiesError }}</p>
      <p v-else-if="families === null" class="muted">Loading…</p>
      <p v-else-if="families.length === 0" class="muted">
        You’re not in a family yet. Ask whoever runs Kam Apps to add you to one.
      </p>
      <ul v-else class="families">
        <li v-for="family in families" :key="family.id">
          <strong>{{ family.name }}</strong>
          <span class="muted small">{{ family.members.map((m) => m.name).join(', ') }}</span>
        </li>
      </ul>
    </section>

    <section v-if="auth.user?.can.manage_groups" class="card stack">
      <h2>Admin</h2>
      <p class="muted small">Create families and invite people to Kam Apps.</p>
      <RouterLink :to="{ name: 'admin-families' }">Manage families</RouterLink>
    </section>

    <section class="card stack">
      <h2 id="theme-label">Appearance</h2>
      <div class="segmented" role="radiogroup" aria-labelledby="theme-label">
        <button
          v-for="option in themes"
          :key="option.value"
          type="button"
          role="radio"
          :aria-checked="theme === option.value"
          :class="{ selected: theme === option.value }"
          @click="setTheme(option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </section>

    <section class="card stack">
      <h2>Devices</h2>
      <p class="muted small">See where you’re signed in, or sign out a lost phone.</p>
      <RouterLink :to="{ name: 'settings-devices' }">Manage devices</RouterLink>
    </section>

    <section class="card stack">
      <h2>About</h2>
      <dl class="versions">
        <dt>App version</dt>
        <dd>{{ clientVersion }}</dd>
        <dt>Server version</dt>
        <dd>{{ serverVersion ?? 'Unknown' }}</dd>
      </dl>
      <p v-if="!versionsMatch" class="warning" role="status">
        This app and the server are on different versions. Reload to get the latest version; if that
        doesn’t help, close and reopen the app.
      </p>
      <div class="row">
        <button v-if="update.needRefresh.value" type="button" @click="update.applyUpdate">
          Reload to update
        </button>
        <button
          v-else
          type="button"
          class="secondary"
          :disabled="update.checking.value"
          @click="checkForUpdate"
        >
          {{ update.checking.value ? 'Checking…' : 'Check for updates' }}
        </button>
        <span v-if="updateStatus" class="muted small">{{ updateStatus }}</span>
      </div>
    </section>

    <button type="button" class="secondary sign-out" @click="signOut">Sign out</button>
  </main>
</template>

<style scoped>
.me {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.who {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow-wrap: anywhere;
}

h2 {
  margin: 0;
}

.families {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.families li {
  display: flex;
  flex-direction: column;
}

.segmented {
  display: flex;
  padding: 0.2rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 10px;
}

.segmented button {
  flex: 1;
  padding: 0.5rem;
  color: var(--text);
  background: none;
  border-radius: 8px;
  font-weight: 500;
}

.segmented button.selected {
  color: var(--on-accent);
  background: var(--accent);
  font-weight: 600;
}

.versions {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.25rem 1rem;
  margin: 0;
}

.versions dd {
  margin: 0;
  font-variant-numeric: tabular-nums;
}

.small {
  font-size: 0.875rem;
}

.sign-out {
  align-self: flex-start;
}
</style>
