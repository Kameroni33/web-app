<script setup lang="ts">
import { useAppNavigation } from '@/composables/useAppNavigation'
import BackLink from '@/components/BackLink.vue'
import { computed, onMounted, ref, watch } from 'vue'
import { errorMessage } from '@/api/http'
import { useMyGiftsStore } from '../store'

const props = defineProps<{ id: string }>()
const nav = useAppNavigation()
const gifts = useMyGiftsStore()

const item = computed(() => gifts.items.find((i) => i.id === Number(props.id)))

const title = ref('')
const links = ref<string[]>([])
const category = ref('')
const notes = ref('')
const busy = ref(false)
const error = ref('')
const confirmingDelete = ref(false)

watch(
  item,
  (current) => {
    if (!current) return
    title.value = current.title
    links.value = current.links.length ? [...current.links] : ['']
    category.value = current.category ?? ''
    notes.value = current.notes ?? ''
  },
  { immediate: true },
)

/** Accept "amazon.com/..." without the https://. */
function normalise(link: string): string {
  const trimmed = link.trim()
  return trimmed && !/^https?:\/\//i.test(trimmed) ? `https://${trimmed}` : trimmed
}

async function run(action: () => Promise<void>): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    await action()
    await nav.back({ name: 'gifts' })
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

const save = () =>
  run(() =>
    gifts.update(Number(props.id), {
      title: title.value.trim(),
      links: links.value.map(normalise).filter(Boolean),
      category: category.value.trim() || null,
      notes: notes.value.trim() || null,
    }),
  )
const markReceived = () => run(() => gifts.update(Number(props.id), { received: true }))
const remove = () => run(() => gifts.remove(Number(props.id)))

onMounted(() => {
  if (!gifts.loaded) void gifts.load().catch((e) => (error.value = errorMessage(e)))
})
</script>

<template>
  <main class="narrow stack">
    <BackLink :to="{ name: 'gifts' }">‹ My list</BackLink>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="!gifts.loaded" class="muted">Loading…</p>
    <p v-else-if="!item" class="muted">Not found.</p>

    <form v-else class="stack" @submit.prevent="save">
      <input
        v-model="title"
        type="text"
        maxlength="200"
        required
        aria-label="Item"
        class="title-input"
      />

      <div class="stack tight">
        <input
          v-for="(_, index) in links"
          :key="index"
          v-model="links[index]"
          type="text"
          inputmode="url"
          autocapitalize="off"
          autocorrect="off"
          maxlength="2000"
          placeholder="Link"
          :aria-label="`Link ${index + 1}`"
        />
        <button v-if="links.length < 5" type="button" class="link add-link" @click="links.push('')">
          + Link
        </button>
      </div>

      <input
        v-model="category"
        type="text"
        maxlength="50"
        placeholder="Category"
        aria-label="Category"
        list="gift-categories"
      />
      <datalist id="gift-categories">
        <option v-for="c in gifts.categories" :key="c" :value="c" />
      </datalist>

      <textarea v-model="notes" maxlength="1000" rows="3" placeholder="Notes" aria-label="Notes" />

      <button type="submit" :disabled="busy || !title.trim()">Save</button>

      <div class="row secondary-actions">
        <button type="button" class="secondary" :disabled="busy" @click="markReceived">
          ✓ Received
        </button>
        <button
          v-if="!confirmingDelete"
          type="button"
          class="link danger-link"
          @click="confirmingDelete = true"
        >
          Delete
        </button>
        <button v-else type="button" class="danger" :disabled="busy" @click="remove">
          Delete for good
        </button>
      </div>
    </form>
  </main>
</template>

<style scoped>
.title-input {
  font-weight: 600;
}

.tight {
  gap: 0.4rem;
}

.add-link {
  align-self: flex-start;
}

textarea {
  width: 100%;
  padding: 0.7rem 0.8rem;
  font: inherit;
  font-size: max(16px, 1rem);
  color: var(--text);
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  resize: vertical;
}

.secondary-actions {
  justify-content: space-between;
}

.danger-link {
  color: var(--danger);
}
</style>
