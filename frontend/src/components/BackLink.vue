<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, type RouteLocationRaw } from 'vue-router'
import { useAppNavigation } from '@/composables/useAppNavigation'

/** "‹ Parent" link that pops history rather than piling a new entry on top. */
const props = defineProps<{ to: RouteLocationRaw }>()
const router = useRouter()
const nav = useAppNavigation()
const href = computed(() => router.resolve(props.to).href)
</script>

<template>
  <a :href="href" class="back-link" @click.prevent="nav.back(props.to)"><slot /></a>
</template>
