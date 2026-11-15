<script setup lang="ts">
import { onMounted, ref } from 'vue'
import InstallPrompt from '@/components/InstallPrompt.vue'
import { errorMessage } from '@/api/http'
import { useAppsStore } from '@/stores/apps'

const appsStore = useAppsStore()
const error = ref('')

async function load(): Promise<void> {
  error.value = ''
  try {
    await appsStore.ensureLoaded()
  } catch (e) {
    error.value = errorMessage(e)
  }
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <h1>Choose an app</h1>

    <InstallPrompt />

    <div v-if="error" class="card stack" role="alert">
      <p class="error">Couldn't load your apps. {{ error }}</p>
      <button type="button" @click="load">Try again</button>
    </div>

    <p v-else-if="!appsStore.loaded" class="muted">Loading…</p>

    <p v-else-if="appsStore.apps.length === 0" class="card muted">
      You don't have access to any apps yet.
    </p>

    <ul v-else class="app-grid">
      <li v-for="app in appsStore.apps" :key="app.key">
        <RouterLink :to="app.home" class="app-tile">
          <img :src="app.icon" alt="" width="64" height="64" />
          <span class="name">{{ app.name }}</span>
        </RouterLink>
      </li>
    </ul>
  </main>
</template>

<style scoped>
.app-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr));
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.app-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  height: 100%;
  padding: 1.25rem 0.75rem 1rem;
  text-align: center;
  text-decoration: none;
  color: var(--text);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition:
    border-color 0.15s,
    transform 0.15s;
}

.app-tile:hover {
  border-color: var(--accent);
}

.app-tile:active {
  transform: scale(0.97);
}

.app-tile img {
  margin-bottom: 0.4rem;
  border-radius: 15px;
}

.name {
  font-weight: 600;
}
</style>
