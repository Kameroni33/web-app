<script setup lang="ts">
import giftsIcon from '@/assets/apps/gifts.svg'
</script>

<template>
  <main class="narrow stack">
    <div class="title">
      <img :src="giftsIcon" alt="" width="48" height="48" />
      <h1>Gift Lists</h1>
    </div>
    <section class="card">
      <p class="muted">Coming soon. This is where wish lists will live.</p>
    </section>
  </main>
</template>

<style scoped>
.title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.title img {
  border-radius: 11px;
}
</style>
