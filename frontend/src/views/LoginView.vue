<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { requestLoginCode } from '@/api/auth'
import { errorMessage } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const RESEND_COOLDOWN_S = 30

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const step = ref<'email' | 'code'>('email')
const email = ref('')
const code = ref('')
const busy = ref(false)
const error = ref('')
const resendIn = ref(0)
let resendTimer: ReturnType<typeof setInterval> | undefined

/** Only same-app paths, so ?redirect= can't send people to another site. */
function safeRedirect(): string {
  const target = route.query.redirect
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//')
    ? target
    : '/'
}

function startResendCooldown(): void {
  resendIn.value = RESEND_COOLDOWN_S
  clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendIn.value -= 1
    if (resendIn.value <= 0) clearInterval(resendTimer)
  }, 1000)
}

async function sendCode(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    await requestLoginCode(email.value.trim())
    step.value = 'code'
    code.value = ''
    startResendCooldown()
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

async function verify(): Promise<void> {
  if (busy.value || code.value.length !== 6) return
  busy.value = true
  error.value = ''
  try {
    await auth.signInWithCode(email.value.trim(), code.value)
    await router.replace(safeRedirect())
  } catch (e) {
    error.value = errorMessage(e)
    code.value = ''
  } finally {
    busy.value = false
  }
}

function onCodeInput(event: Event): void {
  code.value = (event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 6)
  if (code.value.length === 6) void verify()
}

function changeEmail(): void {
  step.value = 'email'
  error.value = ''
}
</script>

<template>
  <main class="narrow">
    <h1>Sign in</h1>

    <form v-if="step === 'email'" class="card stack" @submit.prevent="sendCode">
      <label for="email">Email address</label>
      <input
        id="email"
        v-model="email"
        type="email"
        inputmode="email"
        autocomplete="email"
        autocapitalize="off"
        required
        autofocus
      />
      <button type="submit" :disabled="busy">{{ busy ? 'Sending…' : 'Email me a code' }}</button>
      <p class="muted">No password needed. We’ll email you a 6-digit code.</p>
    </form>

    <form v-else class="card stack" @submit.prevent="verify">
      <p>
        If <strong>{{ email }}</strong> has an account, we sent it a code. Check your inbox and spam
        folder.
      </p>
      <label for="code">6-digit code</label>
      <input
        id="code"
        :value="code"
        class="code-input"
        type="text"
        inputmode="numeric"
        autocomplete="one-time-code"
        pattern="\d{6}"
        maxlength="6"
        required
        autofocus
        @input="onCodeInput"
      />
      <button type="submit" :disabled="busy || code.length !== 6">
        {{ busy ? 'Checking…' : 'Sign in' }}
      </button>
      <div class="row">
        <button type="button" class="link" @click="changeEmail">Use a different email</button>
        <button type="button" class="link" :disabled="busy || resendIn > 0" @click="sendCode">
          {{ resendIn > 0 ? `Resend in ${resendIn}s` : 'Resend code' }}
        </button>
      </div>
    </form>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
  </main>
</template>

<style scoped>
.code-input {
  font-size: 1.75rem;
  letter-spacing: 0.5em;
  text-align: center;
  font-variant-numeric: tabular-nums;
}

.row {
  display: flex;
  justify-content: space-between;
}
</style>
