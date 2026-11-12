<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorMessage } from '@/api/http'
import { isIos } from '@/composables/useInstallPrompt'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const busy = ref(false)
const error = ref('')

// Nothing happens on page load: email scanners open links, and a GET that
// signed in would burn the token. The user's tap sends the POST.
async function signIn(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    await auth.signInWithLink(token.value)
    await router.replace('/')
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <main class="narrow">
    <h1>Sign in</h1>

    <div v-if="!token" class="card stack">
      <p>This sign-in link is incomplete. Request a new code instead.</p>
      <RouterLink to="/login">Go to sign in</RouterLink>
    </div>

    <div v-else class="card stack">
      <p>Tap the button to finish signing in on this device.</p>
      <button type="button" :disabled="busy" @click="signIn">
        {{ busy ? 'Signing in…' : 'Sign in' }}
      </button>
      <p v-if="isIos()" class="muted">
        Using the app from your home screen? Open it and type the 6-digit code from the email
        instead. This link only signs in Safari.
      </p>
    </div>

    <template v-if="error">
      <p class="error" role="alert">{{ error }}</p>
      <RouterLink to="/login">Request a new code</RouterLink>
    </template>
  </main>
</template>
