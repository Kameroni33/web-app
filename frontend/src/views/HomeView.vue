<script setup lang="ts">
import InstallPrompt from '@/components/InstallPrompt.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <main class="narrow stack">
    <h1>Welcome</h1>
    <p class="muted">Signed in as {{ auth.user?.email }}</p>

    <InstallPrompt />

    <section class="card">
      <h2>Your tools</h2>
      <p class="muted">Nothing here yet. Gift lists are coming first.</p>
    </section>
  </main>
</template>
