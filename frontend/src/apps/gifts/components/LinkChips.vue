<script setup lang="ts">
import { linkHost } from '../api'

defineProps<{ links: string[] }>()
</script>

<template>
  <span v-if="links.length" class="links">
    <a
      v-for="link in links"
      :key="link"
      :href="link"
      target="_blank"
      rel="noopener noreferrer"
      class="link-chip"
      @click.stop
    >
      {{ linkHost(link) }} ↗
    </a>
  </span>
</template>

<style scoped>
.links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.link-chip {
  font-size: 0.8rem;
  text-decoration: none;
  overflow-wrap: anywhere;
}
</style>
