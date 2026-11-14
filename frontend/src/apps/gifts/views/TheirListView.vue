<script setup lang="ts">
import { useAppNavigation } from '@/composables/useAppNavigation'
import BackLink from '@/components/BackLink.vue'
import { computed, onMounted, ref } from 'vue'
import { errorMessage } from '@/api/http'
import { listProfiles, selectProfile, type Profile } from '@/api/profiles'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { useAuthStore } from '@/stores/auth'
import { claimItem, fetchList, unclaimItem, type TheirItem, type TheirList } from '../api'
import LinkChips from '../components/LinkChips.vue'

const props = defineProps<{ profileId: string }>()
const auth = useAuthStore()
const nav = useAppNavigation()

const list = ref<TheirList | null>(null)
const error = ref('')
const busyId = ref<number | null>(null)
/** Another profile on this same account: ask before showing anything. */
const sameAccount = ref<Profile | null>(null)
const checking = ref(true)

const id = computed(() => Number(props.profileId))

async function load(): Promise<void> {
  try {
    list.value = await fetchList(id.value)
  } catch (e) {
    error.value = errorMessage(e)
  }
}

async function viewAnyway(): Promise<void> {
  sameAccount.value = null
  await load()
}

async function switchTo(profile: Profile): Promise<void> {
  try {
    auth.setProfile(await selectProfile(profile.id))
    await nav.startAt({ name: 'gifts' })
  } catch (e) {
    error.value = errorMessage(e)
  }
}

async function toggle(item: TheirItem): Promise<void> {
  busyId.value = item.id
  error.value = ''
  try {
    const updated = item.claim === 'mine' ? await unclaimItem(item.id) : await claimItem(item.id)
    if (list.value) {
      list.value.items = list.value.items
        .map((i) => (i.id === updated.id ? updated : i))
        .filter((i) => !(i.removed && i.claim !== 'mine'))
    }
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busyId.value = null
  }
}

onMounted(async () => {
  // Decide before fetching, so nothing about the list is even loaded until confirmed.
  try {
    sameAccount.value = (await listProfiles()).find((p) => p.id === id.value) ?? null
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    checking.value = false
  }
  if (!sameAccount.value && !error.value) await load()
})
</script>

<template>
  <main class="narrow stack">
    <BackLink :to="{ name: 'gifts-people' }">‹ People</BackLink>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <section v-if="sameAccount" class="card stack guard">
      <ProfileAvatar
        :name="sameAccount.name"
        :id="sameAccount.id"
        :src="sameAccount.avatar_url"
        :size="56"
      />
      <p>
        <strong>{{ sameAccount.name }}’s list</strong><br />
        <span class="muted">You’re {{ auth.profile?.name }}.</span>
      </p>
      <!-- The safe choice is the big one: switching reveals nothing. -->
      <button type="button" @click="switchTo(sameAccount)">I’m {{ sameAccount.name }}</button>
      <button type="button" class="secondary" @click="viewAnyway">
        View as {{ auth.profile?.name }}
      </button>
    </section>

    <p v-else-if="checking || (!list && !error)" class="muted">Loading…</p>

    <template v-else-if="list">
      <header class="who">
        <ProfileAvatar
          :name="list.profile.name"
          :id="list.profile.id"
          :src="list.profile.avatar_url"
          :size="44"
        />
        <h1>{{ list.profile.name }}</h1>
      </header>

      <section v-if="list.wishlists.length" class="elsewhere">
        <a
          v-for="w in list.wishlists"
          :key="w.id"
          :href="w.url"
          target="_blank"
          rel="noopener noreferrer"
          class="pill"
        >
          {{ w.label }} ↗
        </a>
      </section>

      <p v-if="list.items.length === 0" class="muted empty">Nothing yet.</p>

      <ul v-else class="card items">
        <li
          v-for="item in list.items"
          :key="item.id"
          :class="{ taken: item.claim === 'taken', removed: item.removed }"
        >
          <div class="body">
            <span class="title">{{ item.title }}</span>
            <span v-if="item.category" class="tag">{{ item.category }}</span>
            <span v-if="item.removed" class="tag warn">Removed</span>
            <p v-if="item.notes" class="notes muted">{{ item.notes }}</p>
            <LinkChips :links="item.links" />
          </div>
          <button
            type="button"
            class="claim"
            :class="item.claim ?? 'open'"
            :disabled="item.claim === 'taken' || busyId === item.id"
            :aria-pressed="item.claim === 'mine'"
            :aria-label="
              item.claim === 'taken' ? 'Someone’s getting this' : `I’m getting ${item.title}`
            "
            @click="toggle(item)"
          >
            {{ item.claim === 'mine' ? '✓' : item.claim === 'taken' ? 'Taken' : '' }}
          </button>
        </li>
      </ul>
    </template>
  </main>
</template>

<style scoped>
.guard {
  align-items: center;
  text-align: center;
  padding: 1.5rem 1rem;
}

.guard button {
  width: 100%;
}

.who {
  display: flex;
  align-items: center;
  gap: 0.75rem;
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

.items {
  margin: 0;
  padding: 0;
  list-style: none;
}

.items li {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.9rem;
}

.items li + li {
  border-top: 1px solid var(--border);
}

.body {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.25rem 0.5rem;
  min-width: 0;
}

.title {
  font-weight: 500;
  overflow-wrap: anywhere;
}

.notes {
  flex-basis: 100%;
  font-size: 0.85rem;
  white-space: pre-line;
}

.body :deep(.links) {
  flex-basis: 100%;
}

.tag {
  padding: 0.05rem 0.5rem;
  font-size: 0.75rem;
  background: var(--accent-soft);
  border-radius: 999px;
}

.tag.warn {
  background: var(--warning-bg);
  color: var(--warning-text);
}

.taken .body,
.removed .title {
  opacity: 0.5;
}

.removed .title {
  text-decoration: line-through;
}

.claim {
  flex: none;
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
  font-size: 1.1rem;
  border-radius: 50%;
}

.claim.open {
  background: none;
  border: 2px solid var(--border);
}

.claim.taken {
  width: auto;
  padding: 0 0.6rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--muted);
  background: none;
  border-radius: 999px;
  opacity: 1;
}

.empty {
  text-align: center;
  padding: 2rem 0;
}
</style>
