<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { errorMessage } from '@/api/http'
import type { MyItem } from '../api'
import GiftsNav from '../components/GiftsNav.vue'
import LinkChips from '../components/LinkChips.vue'
import { useMyGiftsStore } from '../store'

const gifts = useMyGiftsStore()
const error = ref('')
const newTitle = ref('')
const adding = ref(false)
const filter = ref<string | null>(null)

// Local copy for dragging; the store saves the new order.
const order = ref<MyItem[]>([])
watch(
  () => gifts.wanted,
  (wanted) => {
    order.value = [...wanted]
  },
  { immediate: true },
)

const shown = computed(() =>
  filter.value ? order.value.filter((item) => item.category === filter.value) : order.value,
)

async function run(action: () => Promise<unknown>): Promise<void> {
  error.value = ''
  try {
    await action()
  } catch (e) {
    error.value = errorMessage(e)
  }
}

const add = () =>
  run(async () => {
    const title = newTitle.value.trim()
    if (!title) return
    adding.value = true
    try {
      await gifts.add({ title, category: filter.value })
      newTitle.value = ''
    } finally {
      adding.value = false
    }
  })

const saveOrder = () => run(() => gifts.reorder(order.value))
const undoReceived = (item: MyItem) => run(() => gifts.update(item.id, { received: false }))
const removeReceived = (item: MyItem) => run(() => gifts.remove(item.id))

onMounted(() => {
  if (!gifts.loaded) void run(gifts.load)
})
</script>

<template>
  <main class="narrow stack">
    <GiftsNav />

    <form class="add" @submit.prevent="add">
      <input
        v-model="newTitle"
        type="text"
        maxlength="200"
        placeholder="Add an item"
        aria-label="New item"
      />
      <button type="submit" :disabled="adding || !newTitle.trim()" aria-label="Add">+</button>
    </form>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <div v-if="gifts.categories.length" class="filters" role="group" aria-label="Category">
      <button type="button" :class="{ on: filter === null }" @click="filter = null">All</button>
      <button
        v-for="category in gifts.categories"
        :key="category"
        type="button"
        :class="{ on: filter === category }"
        @click="filter = category"
      >
        {{ category }}
      </button>
    </div>

    <p v-if="!gifts.loaded && !error" class="muted">Loading…</p>
    <p v-else-if="gifts.loaded && order.length === 0" class="muted empty">Nothing yet.</p>

    <VueDraggable
      v-else
      v-model="order"
      tag="ul"
      class="items card"
      handle=".handle"
      :disabled="filter !== null"
      :animation="150"
      @end="saveOrder"
    >
      <li v-for="item in shown" :key="item.id">
        <span v-if="filter === null" class="handle" aria-hidden="true">
          <svg width="10" height="16" viewBox="0 0 10 16" fill="currentColor">
            <circle cx="2" cy="2" r="1.5" />
            <circle cx="8" cy="2" r="1.5" />
            <circle cx="2" cy="8" r="1.5" />
            <circle cx="8" cy="8" r="1.5" />
            <circle cx="2" cy="14" r="1.5" />
            <circle cx="8" cy="14" r="1.5" />
          </svg>
        </span>
        <RouterLink :to="{ name: 'gifts-item', params: { id: item.id } }" class="item">
          <span class="title">{{ item.title }}</span>
          <span v-if="item.category && filter === null" class="tag">{{ item.category }}</span>
        </RouterLink>
        <LinkChips :links="item.links" />
      </li>
    </VueDraggable>

    <section class="elsewhere">
      <a
        v-for="list in gifts.wishlists"
        :key="list.id"
        :href="list.url"
        target="_blank"
        rel="noopener noreferrer"
        class="pill"
      >
        {{ list.label }} ↗
      </a>
      <RouterLink :to="{ name: 'gifts-wishlists' }" class="pill add-pill">
        {{ gifts.wishlists.length ? 'Edit lists' : '+ Amazon, Steam…' }}
      </RouterLink>
    </section>

    <details v-if="gifts.received.length" class="received">
      <summary class="muted">Received ({{ gifts.received.length }})</summary>
      <ul class="items card">
        <li v-for="item in gifts.received" :key="item.id">
          <span class="title done">{{ item.title }}</span>
          <button type="button" class="link" @click="undoReceived(item)">Undo</button>
          <button type="button" class="link danger-link" @click="removeReceived(item)">
            Delete
          </button>
        </li>
      </ul>
    </details>
  </main>
</template>

<style scoped>
.add {
  display: flex;
  gap: 0.5rem;
}

.add button {
  flex: none;
  width: 3rem;
  padding: 0;
  font-size: 1.4rem;
  line-height: 1;
}

.filters {
  display: flex;
  gap: 0.4rem;
  overflow-x: auto;
  scrollbar-width: none;
}

.filters button {
  flex: none;
  padding: 0.3rem 0.8rem;
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--text);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 999px;
}

.filters button.on {
  color: var(--on-accent);
  background: var(--accent);
  border-color: var(--accent);
}

.items {
  margin: 0;
  padding: 0;
  list-style: none;
}

.items li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.25rem 0.6rem;
  padding: 0.7rem 0.9rem;
  background: var(--surface);
}

.items li:first-child {
  border-radius: var(--radius) var(--radius) 0 0;
}

.items li:last-child {
  border-radius: 0 0 var(--radius) var(--radius);
}

.items li + li {
  border-top: 1px solid var(--border);
}

.handle {
  margin: -0.7rem 0 -0.7rem -0.9rem;
  padding: 0.7rem 0.2rem 0.7rem 0.7rem;
  display: flex;
  color: var(--muted);
  cursor: grab;
  touch-action: none;
  user-select: none;
}

.item {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
  color: var(--text);
  text-decoration: none;
}

.title {
  font-weight: 500;
  overflow-wrap: anywhere;
}

.done {
  flex: 1;
  color: var(--muted);
  text-decoration: line-through;
}

.tag {
  flex: none;
  padding: 0.05rem 0.5rem;
  font-size: 0.75rem;
  background: var(--accent-soft);
  border-radius: 999px;
}

.items :deep(.links) {
  flex-basis: 100%;
  padding-left: 1.4rem;
}

.elsewhere {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.pill {
  padding: 0.3rem 0.8rem;
  font-size: 0.9rem;
  text-decoration: none;
  background: var(--accent-soft);
  color: var(--text);
  border-radius: 999px;
}

.add-pill {
  color: var(--muted);
  background: none;
  border: 1px dashed var(--border);
}

.received summary {
  cursor: pointer;
  margin-bottom: 0.5rem;
}

.danger-link {
  color: var(--danger);
}

.empty {
  text-align: center;
  padding: 2rem 0;
}
</style>
