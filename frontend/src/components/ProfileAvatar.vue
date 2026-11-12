<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{ name: string; id: number; size?: number }>(), {
  size: 40,
})

// Fixed per profile id, so each person keeps their colour.
const COLORS = ['#0f766e', '#b45309', '#7c3aed', '#be123c', '#1d4ed8', '#4d7c0f', '#a21caf']

const initial = computed(() => props.name.trim().charAt(0).toUpperCase() || '?')
const background = computed(() => COLORS[props.id % COLORS.length])
</script>

<template>
  <span
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
</style>
