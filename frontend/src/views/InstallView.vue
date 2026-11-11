<script setup lang="ts">
import { useInstallPrompt } from '@/composables/useInstallPrompt'

const { installed, canPrompt, promptInstall } = useInstallPrompt()
</script>

<template>
  <main class="narrow stack">
    <h1>Install the app</h1>

    <p v-if="installed" class="card">You’re already using the installed app.</p>

    <section v-if="canPrompt" class="card stack">
      <p>Your browser can install it directly.</p>
      <button type="button" @click="promptInstall">Install</button>
    </section>

    <section class="card">
      <h2>iPhone or iPad</h2>
      <ol>
        <li>Open this page in <strong>Safari</strong>.</li>
        <li>Tap the <strong>Share</strong> button (the square with an arrow).</li>
        <li>Scroll down and tap <strong>Add to Home Screen</strong>, then <strong>Add</strong>.</li>
        <li>
          Open the app from your home screen and sign in with the
          <strong>6-digit code</strong> from your email. The email’s link only signs in Safari, not
          the app.
        </li>
      </ol>
    </section>

    <section class="card">
      <h2>Android</h2>
      <ol>
        <li>Open this page in <strong>Chrome</strong>.</li>
        <li>
          Tap <strong>Install</strong> when it’s offered, or open the <strong>⋮</strong> menu and
          tap <strong>Add to Home screen</strong>.
        </li>
      </ol>
    </section>

    <section class="card">
      <h2>Computer</h2>
      <p>
        In Chrome or Edge, click the install icon at the right end of the address bar. Other
        browsers can just bookmark the page.
      </p>
    </section>

    <RouterLink to="/">Back to the app</RouterLink>
  </main>
</template>

<style scoped>
ol {
  padding-left: 1.25rem;
}

li + li {
  margin-top: 0.4rem;
}
</style>
