<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createFamily, listAdminFamilies, type AdminFamily } from '@/api/admin'
import { errorMessage } from '@/api/http'

/** Admin accounts: every family on Kam Apps. */
const router = useRouter()

const families = ref<AdminFamily[] | null>(null)
const name = ref('')
const busy = ref(false)
const error = ref('')

async function load(): Promise<void> {
  try {
    families.value = await listAdminFamilies()
  } catch (e) {
    error.value = errorMessage(e)
  }
}

async function create(): Promise<void> {
  busy.value = true
  error.value = ''
  try {
    const family = await createFamily(name.value.trim())
    await router.push({ name: 'admin-family', params: { id: family.id } })
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <RouterLink to="/settings" class="back-link">‹ Settings</RouterLink>
    <h1>Manage families</h1>
    <p class="muted">
      Families decide what’s shared with whom. People join a family through an invite link.
    </p>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="families === null && !error" class="muted">Loading…</p>

    <ul v-else-if="families && families.length > 0" class="card list">
      <li v-for="family in families" :key="family.id">
        <RouterLink :to="{ name: 'admin-family', params: { id: family.id } }" class="family-link">
          <strong>{{ family.name }}</strong>
          <span class="muted small">
            {{ family.member_count }} {{ family.member_count === 1 ? 'account' : 'accounts' }} ›
          </span>
        </RouterLink>
      </li>
    </ul>
    <p v-else-if="families" class="card muted">No families yet.</p>

    <form class="card stack" @submit.prevent="create">
      <h2>New family</h2>
      <label for="family-name">Name</label>
      <input
        id="family-name"
        v-model="name"
        type="text"
        maxlength="100"
        placeholder="e.g. Robinson family"
        required
      />
      <button type="submit" :disabled="busy || name.trim() === ''">Create family</button>
    </form>
  </main>
</template>

<style scoped>
.list {
  list-style: none;
  padding: 0;
}

.list li + li {
  border-top: 1px solid var(--border);
}

.family-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  color: var(--text);
  text-decoration: none;
}

.small {
  font-size: 0.875rem;
}
</style>
