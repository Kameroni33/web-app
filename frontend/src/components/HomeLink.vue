<script setup lang="ts">
import { useAppNavigation } from '@/composables/useAppNavigation'

/** Link to the app picker that clears the back stack, like a native app's root. */
const nav = useAppNavigation()
</script>

<template>
  <a href="/" @click.prevent="nav.startAt({ name: 'home' })"><slot /></a>
</template>
