<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { errorMessage } from '@/api/http'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { listPeople, type People } from '../api'
import GiftsNav from '../components/GiftsNav.vue'

const people = ref<People | null>(null)
const error = ref('')

onMounted(async () => {
  try {
    people.value = await listPeople()
  } catch (e) {
    error.value = errorMessage(e)
  }
})
</script>

<template>
  <main class="narrow stack">
    <GiftsNav />

    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-else-if="!people" class="muted">Loading…</p>
    <p v-else-if="!people.account.length && !people.families.length" class="muted empty">
      No one yet.
    </p>

    <template v-else>
      <section
        v-for="group in [
          ...(people.account.length
            ? [{ id: 0, name: 'This account', people: people.account }]
            : []),
          ...people.families,
        ]"
        :key="group.id"
        class="stack tight"
      >
        <h2 class="muted">{{ group.name }}</h2>
        <ul class="card people">
          <li v-for="person in group.people" :key="person.id">
            <RouterLink :to="{ name: 'gifts-list', params: { profileId: person.id } }">
              <ProfileAvatar
                :name="person.name"
                :id="person.id"
                :src="person.avatar_url"
                :size="36"
              />
              <span>{{ person.name }}</span>
              <span class="chevron" aria-hidden="true">›</span>
            </RouterLink>
          </li>
        </ul>
      </section>
    </template>
  </main>
</template>

<style scoped>
.tight {
  gap: 0.4rem;
}

h2 {
  margin: 0;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.people {
  margin: 0;
  padding: 0;
  list-style: none;
}

.people li + li {
  border-top: 1px solid var(--border);
}

.people a {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.9rem;
  color: var(--text);
  text-decoration: none;
  font-weight: 500;
}

.chevron {
  margin-left: auto;
  color: var(--muted);
  font-size: 1.3rem;
}

.empty {
  text-align: center;
  padding: 2rem 0;
}
</style>
