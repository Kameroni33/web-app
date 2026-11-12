<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { errorMessage } from '@/api/http'
import {
  createProfile,
  deleteProfile,
  listProfiles,
  updateProfile,
  type Profile,
  type ProfileRole,
} from '@/api/profiles'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { useAuthStore } from '@/stores/auth'

/**
 * The people sharing this account. Admin profiles add, edit and remove them;
 * everyone else can only rename themselves. The server enforces the same rules.
 */
const auth = useAuthStore()

const profiles = ref<Profile[]>([])
const loading = ref(true)
const busy = ref(false)
const error = ref('')

const editingId = ref<number | null>(null)
const editName = ref('')
const editRole = ref<ProfileRole>('member')
const deletingId = ref<number | null>(null)

const newName = ref('')
const newRole = ref<ProfileRole>('member')

const isAdmin = computed(() => auth.profile?.role === 'admin')
const isMe = (profile: Profile) => profile.id === auth.profile?.id

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

const load = () =>
  run(async () => {
    profiles.value = await listProfiles()
  }).finally(() => {
    loading.value = false
  })

function startEdit(profile: Profile): void {
  editingId.value = profile.id
  editName.value = profile.name
  editRole.value = profile.role
  deletingId.value = null
}

const saveEdit = (profile: Profile) =>
  run(async () => {
    const changes: { name: string; role?: ProfileRole } = { name: editName.value.trim() }
    if (isAdmin.value && editRole.value !== profile.role) changes.role = editRole.value
    const updated = await updateProfile(profile.id, changes)
    profiles.value = profiles.value.map((p) => (p.id === updated.id ? updated : p))
    if (isMe(updated)) auth.setProfile(updated)
    editingId.value = null
  })

const confirmDelete = (profile: Profile) =>
  run(async () => {
    await deleteProfile(profile.id)
    profiles.value = profiles.value.filter((p) => p.id !== profile.id)
    deletingId.value = null
  })

const add = () =>
  run(async () => {
    const created = await createProfile(newName.value.trim(), newRole.value)
    profiles.value = [...profiles.value, created]
    newName.value = ''
    newRole.value = 'member'
  })

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <RouterLink to="/settings" class="back-link">‹ Settings</RouterLink>
    <h1>Profiles</h1>
    <p class="muted">
      Everyone who signs in with {{ auth.user?.email }}. Each person picks their profile on their
      own device.
    </p>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="loading" class="muted">Loading…</p>

    <ul v-else class="card profile-list">
      <li v-for="profile in profiles" :key="profile.id">
        <form v-if="editingId === profile.id" class="stack" @submit.prevent="saveEdit(profile)">
          <label :for="`name-${profile.id}`">Name</label>
          <input
            :id="`name-${profile.id}`"
            v-model="editName"
            type="text"
            maxlength="50"
            required
            autofocus
          />
          <template v-if="isAdmin">
            <label :for="`role-${profile.id}`">Role</label>
            <select :id="`role-${profile.id}`" v-model="editRole">
              <option value="member">Member</option>
              <option value="admin">Admin: can manage profiles</option>
            </select>
          </template>
          <div class="row">
            <button type="submit" :disabled="busy || editName.trim() === ''">Save</button>
            <button type="button" class="link" @click="editingId = null">Cancel</button>
          </div>
        </form>

        <div v-else-if="deletingId === profile.id" class="stack">
          <p>
            Delete <strong>{{ profile.name }}</strong>? Anything that belongs to this profile will
            be deleted too. This can’t be undone.
          </p>
          <div class="row">
            <button type="button" class="danger" :disabled="busy" @click="confirmDelete(profile)">
              Delete {{ profile.name }}
            </button>
            <button type="button" class="link" @click="deletingId = null">Cancel</button>
          </div>
        </div>

        <div v-else class="profile-row">
          <ProfileAvatar :name="profile.name" :id="profile.id" />
          <div class="who">
            <strong>{{ profile.name }}</strong>
            <span class="muted small">
              {{ profile.role === 'admin' ? 'Admin' : 'Member' }}{{ isMe(profile) ? ' · You' : '' }}
            </span>
          </div>
          <div class="actions">
            <button
              v-if="isAdmin || isMe(profile)"
              type="button"
              class="link"
              @click="startEdit(profile)"
            >
              Edit
            </button>
            <button
              v-if="isAdmin && !isMe(profile)"
              type="button"
              class="link danger-link"
              @click="deletingId = profile.id; editingId = null"
            >
              Delete
            </button>
          </div>
        </div>
      </li>
    </ul>

    <form v-if="isAdmin && !loading" class="card stack" @submit.prevent="add">
      <h2>Add a profile</h2>
      <p class="muted small">For someone who shares this email, like a partner or a child.</p>
      <label for="new-name">Name</label>
      <input id="new-name" v-model="newName" type="text" maxlength="50" required />
      <label for="new-role">Role</label>
      <select id="new-role" v-model="newRole">
        <option value="member">Member</option>
        <option value="admin">Admin: can manage profiles</option>
      </select>
      <button type="submit" :disabled="busy || newName.trim() === ''">Add profile</button>
    </form>
    <p v-else-if="!loading" class="muted small">Only an admin profile can add or remove profiles.</p>
  </main>
</template>

<style scoped>
.profile-list {
  list-style: none;
  padding: 0;
}

.profile-list li {
  padding: 0.75rem 1rem;
}

.profile-list li + li {
  border-top: 1px solid var(--border);
}

.profile-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.who {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  overflow-wrap: anywhere;
}

.actions {
  display: flex;
  gap: 1rem;
}

.danger-link {
  color: var(--danger);
}

.small {
  font-size: 0.875rem;
}
</style>
