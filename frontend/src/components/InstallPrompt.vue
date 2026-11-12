<script setup lang="ts">
import { ref } from 'vue'
import { useInstallPrompt } from '@/composables/useInstallPrompt'

const DISMISS_KEY = 'install-prompt-dismissed'

const { canPrompt, needsIosInstructions, promptInstall } = useInstallPrompt()

function readDismissed(): boolean {
  try {
    return localStorage.getItem(DISMISS_KEY) === '1'
  } catch {
    return false
  }
}

const dismissed = ref(readDismissed())

function dismiss(): void {
  dismissed.value = true
  try {
    localStorage.setItem(DISMISS_KEY, '1')
  } catch {
    // Private mode or blocked storage: dismiss for this visit only.
  }
}
</script>

<template>
  <section v-if="!dismissed && (canPrompt || needsIosInstructions)" class="card install">
    <h2>Install the app</h2>
    <template v-if="canPrompt">
      <p>Add it to your home screen so it opens like any other app.</p>
      <div class="row">
        <button type="button" @click="promptInstall">Install</button>
        <button type="button" class="link" @click="dismiss">Not now</button>
      </div>
    </template>
    <template v-else>
      <p>
        In Safari, tap <strong>Share</strong>, then <strong>Add to Home Screen</strong>. After that,
        sign in inside the app using the 6-digit code from your email.
      </p>
      <div class="row">
        <RouterLink to="/install">Show me how</RouterLink>
        <button type="button" class="link" @click="dismiss">Not now</button>
      </div>
    </template>
  </section>
</template>

<style scoped>
.row {
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
