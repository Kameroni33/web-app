<script setup lang="ts">
import { useAppUpdate } from '@/composables/useAppUpdate'

/** Shows "A new version is available" after a deploy; see useAppUpdate. */
const { needRefresh, applyUpdate } = useAppUpdate()

function dismiss(): void {
  needRefresh.value = false
}
</script>

<template>
  <div v-if="needRefresh" class="update-banner" role="status">
    <span>A new version is available.</span>
    <div class="actions">
      <button type="button" class="link" @click="dismiss">Later</button>
      <button type="button" @click="applyUpdate">Reload</button>
    </div>
  </div>
</template>

<style scoped>
.update-banner {
  position: fixed;
  inset: auto 1rem calc(1rem + env(safe-area-inset-bottom)) 1rem;
  max-width: 30rem;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius);
  background: var(--text);
  color: var(--bg);
  box-shadow: 0 6px 24px rgb(0 0 0 / 0.2);
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.update-banner .link {
  color: inherit;
}
</style>
