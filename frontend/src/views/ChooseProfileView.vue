<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorMessage } from '@/api/http'
import { createProfile, listProfiles, selectProfile, type Profile } from '@/api/profiles'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { useAuthStore } from '@/stores/auth'

/**
 * "Who's using Kam Apps?" after sign-in when the account has several
 * profiles, or first-time setup (name the admin profile) when it has none.
 */
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const profiles = ref<Profile[] | null>(null)
const name = ref('')
const busy = ref(false)
const error = ref('')

function safeRedirect(): string {
  const target = route.query.redirect
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//')
    ? target
    : '/'
}

async function load(): Promise<void> {
  error.value = ''
  try {
    profiles.value = await listProfiles()
  } catch (e) {
    error.value = errorMessage(e)
  }
}

async function choose(profile: Profile): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    auth.setProfile(await selectProfile(profile.id))
    await router.replace(safeRedirect())
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

async function setUp(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    auth.setProfile(await createProfile(name.value.trim()))
    await router.replace(safeRedirect())
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

async function signOut(): Promise<void> {
  await auth.signOut().catch(() => undefined)
  await router.replace('/login')
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <p v-if="profiles === null && !error" class="muted">Loading…</p>

    <form v-else-if="profiles?.length === 0" class="stack" @submit.prevent="setUp">
      <h1>Welcome to Kam Apps</h1>
      <p class="muted">What should we call you? This is how your family will see you.</p>
      <div class="card stack">
        <label for="profile-name">Your name</label>
        <input
          id="profile-name"
          v-model="name"
          type="text"
          autocomplete="given-name"
          maxlength="50"
          required
          autofocus
        />
        <button type="submit" :disabled="busy || name.trim() === ''">
          {{ busy ? 'Saving…' : 'Continue' }}
        </button>
      </div>
      <p class="muted small">
        Sharing this email with others in your household? You can add a profile for each person
        later in Settings.
      </p>
    </form>

    <template v-else-if="profiles">
      <h1>Who’s using Kam Apps?</h1>
      <ul class="profile-grid">
        <li v-for="profile in profiles" :key="profile.id">
          <button type="button" class="profile-tile" :disabled="busy" @click="choose(profile)">
            <ProfileAvatar :name="profile.name" :id="profile.id" :size="64" />
            <span class="name">{{ profile.name }}</span>
          </button>
        </li>
      </ul>
    </template>

    <p v-if="error" class="error" role="alert">
      {{ error }} <button type="button" class="link" @click="load">Try again</button>
    </p>

    <button type="button" class="link sign-out" @click="signOut">
      Not you? Sign out of {{ auth.user?.email }}
    </button>
  </main>
</template>

<style scoped>
.profile-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr));
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.profile-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  padding: 1.25rem 0.5rem 1rem;
  color: var(--text);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.profile-tile:hover {
  border-color: var(--accent);
}

.name {
  font-weight: 600;
  overflow-wrap: anywhere;
}

.small {
  font-size: 0.875rem;
}

.sign-out {
  align-self: flex-start;
  margin-top: 1rem;
  font-size: 0.875rem;
}
</style>
