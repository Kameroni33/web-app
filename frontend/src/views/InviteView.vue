<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorMessage } from '@/api/http'
import { acceptInvite, fetchInvite, registerFromInvite, type InvitePreview } from '@/api/invites'
import { useAuthStore } from '@/stores/auth'

/**
 * Landing page for an invite link. Newcomers enter their email, sign in with
 * the emailed code, and come back here to join; signed-in people join with
 * one tap.
 */
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const token = computed(() => String(route.params.token))
const invite = ref<InvitePreview | null>(null)
const invalid = ref('')
const email = ref('')
const busy = ref(false)
const error = ref('')

async function load(): Promise<void> {
  try {
    invite.value = await fetchInvite(token.value)
  } catch (e) {
    invalid.value = errorMessage(e, 'This invite link isn’t valid.')
  }
}

async function register(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    const address = email.value.trim()
    await registerFromInvite(token.value, address)
    await router.push({
      name: 'login',
      query: { email: address, sent: '1', redirect: route.fullPath },
    })
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

async function join(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    await acceptInvite(token.value)
    // New accounts are asked for their name next (router guard).
    await router.replace({ name: 'home' })
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

async function signOut(): Promise<void> {
  await auth.signOut().catch(() => undefined)
  await load()
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <p v-if="!invite && !invalid" class="muted">Loading…</p>

    <template v-else-if="invalid">
      <h1>Invite link not valid</h1>
      <p class="card">{{ invalid }}</p>
      <RouterLink to="/">Go to Kam Apps</RouterLink>
    </template>

    <template v-else-if="invite">
      <h1>You’re invited</h1>
      <p>
        Join <strong>{{ invite.group.name }}</strong> on Kam Apps to share gift lists and more with
        your family.
      </p>

      <div v-if="auth.isSignedIn && invite.is_member" class="card stack">
        <p>You’re already in {{ invite.group.name }}.</p>
        <RouterLink :to="{ name: 'home' }">Go to your apps</RouterLink>
      </div>

      <div v-else-if="auth.isSignedIn" class="card stack">
        <p class="muted">Signed in as {{ auth.user?.email }}</p>
        <button type="button" :disabled="busy" @click="join">
          {{ busy ? 'Joining…' : `Join ${invite.group.name}` }}
        </button>
        <button type="button" class="link" @click="signOut">Not you? Sign out</button>
      </div>

      <form v-else class="card stack" @submit.prevent="register">
        <label for="invite-email">Your email address</label>
        <input
          id="invite-email"
          v-model="email"
          type="email"
          inputmode="email"
          autocomplete="email"
          autocapitalize="off"
          required
          autofocus
        />
        <button type="submit" :disabled="busy">{{ busy ? 'Sending…' : 'Continue' }}</button>
        <p class="muted small">
          We’ll email you a 6-digit code. No password needed. Already have an account? Use the same
          email.
        </p>
      </form>

      <p v-if="error" class="error" role="alert">{{ error }}</p>
    </template>
  </main>
</template>

<style scoped>
.small {
  font-size: 0.875rem;
}
</style>
