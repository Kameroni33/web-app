<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{ name: string; id: number; src?: string | null; size?: number }>(),
  { src: null, size: 40 },
)

// Fixed per profile id, so each person keeps their colour.
const COLORS = ['#0f766e', '#b45309', '#7c3aed', '#be123c', '#1d4ed8', '#4d7c0f', '#a21caf']

const initial = computed(() => props.name.trim().charAt(0).toUpperCase() || '?')
const background = computed(() => COLORS[props.id % COLORS.length])

// Offline or deleted photo: fall back to the initial.
const failed = ref(false)
watch(
  () => props.src,
  () => {
    failed.value = false
  },
)
</script>

<template>
  <img
    v-if="src && !failed"
    class="avatar"
    :src="src"
    alt=""
    :width="size"
    :height="size"
    @error="failed = true"
  />
  <span
    v-else
    class="avatar"
    aria-hidden="true"
    :style="{ width: `${size}px`, height: `${size}px`, fontSize: `${size * 0.45}px`, background }"
  >
    {{ initial }}
  </span>
</template>

<style scoped>
.avatar {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #ffffff;
  font-weight: 700;
  line-height: 1;
}

img.avatar {
  object-fit: cover;
  background: var(--surface);
}
</style>
