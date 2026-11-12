<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { errorMessage } from '@/api/http'
import { linkHost } from '../api'
import { useMyGiftsStore } from '../store'

const gifts = useMyGiftsStore()
const label = ref('')
const url = ref('')
const busy = ref(false)
const error = ref('')

async function run(action: () => Promise<void>): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    await action()
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

const add = () =>
  run(async () => {
    const link = url.value.trim()
    await gifts.addWishlist({
      label: label.value.trim(),
      url: /^https?:\/\//i.test(link) ? link : `https://${link}`,
    })
    label.value = ''
    url.value = ''
  })

onMounted(() => {
  if (!gifts.loaded) void run(gifts.load)
})
</script>

<template>
  <main class="narrow stack">
    <RouterLink :to="{ name: 'gifts' }" class="back-link">‹ My list</RouterLink>
    <h1>Other lists</h1>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <ul v-if="gifts.wishlists.length" class="card lists">
      <li v-for="list in gifts.wishlists" :key="list.id">
        <a :href="list.url" target="_blank" rel="noopener noreferrer">
          <strong>{{ list.label }}</strong>
          <span class="muted small">{{ linkHost(list.url) }}</span>
        </a>
        <button
          type="button"
          class="link danger-link"
          :disabled="busy"
          @click="run(() => gifts.removeWishlist(list.id))"
        >
          Remove
        </button>
      </li>
    </ul>

    <form class="stack" @submit.prevent="add">
      <input
        v-model="label"
        type="text"
        maxlength="50"
        required
        placeholder="Name (Amazon, Steam…)"
        aria-label="Name"
        list="wishlist-sites"
      />
      <datalist id="wishlist-sites">
        <option value="Amazon" />
        <option value="Steam" />
        <option value="Etsy" />
        <option value="Target" />
        <option value="Walmart" />
      </datalist>
      <input
        v-model="url"
        type="text"
        inputmode="url"
        autocapitalize="off"
        autocorrect="off"
        maxlength="2000"
        required
        placeholder="Link"
        aria-label="Link"
      />
      <button type="submit" :disabled="busy || !label.trim() || !url.trim()">Add</button>
    </form>
  </main>
</template>

<style scoped>
.lists {
  margin: 0;
  padding: 0;
  list-style: none;
}

.lists li {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.9rem;
}

.lists li + li {
  border-top: 1px solid var(--border);
}

.lists a {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  color: var(--text);
  text-decoration: none;
  overflow-wrap: anywhere;
}

.small {
  font-size: 0.85rem;
}

.danger-link {
  color: var(--danger);
}
</style>
