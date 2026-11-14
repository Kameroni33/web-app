<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import HomeLink from '@/components/HomeLink.vue'
import { useAppsStore } from '@/stores/apps'

/** Placeholder home page for an app that isn't built yet. */
const route = useRoute()
const appsStore = useAppsStore()
const app = computed(() => (route.meta.app ? appsStore.find(route.meta.app) : undefined))
</script>

<template>
  <main v-if="app" class="narrow stack">
    <div class="title">
      <img :src="app.icon" alt="" width="56" height="56" />
      <div>
        <h1>{{ app.name }}</h1>
        <p class="muted">{{ app.description }}</p>
      </div>
    </div>
    <section class="card coming-soon">
      <p><strong>Coming soon.</strong> This app is still being built.</p>
      <HomeLink>‹ All apps</HomeLink>
    </section>
  </main>
</template>

<style scoped>
.title {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.title img {
  flex: none;
  border-radius: 13px;
}

.coming-soon {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: var(--accent-soft);
  border-color: transparent;
}
</style>
