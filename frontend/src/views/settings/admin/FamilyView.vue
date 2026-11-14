<script setup lang="ts">
import { useAppNavigation } from '@/composables/useAppNavigation'
import BackLink from '@/components/BackLink.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  createInvite,
  deleteFamily,
  fetchAdminFamily,
  removeMember,
  renameFamily,
  revokeInvite,
  type AdminFamily,
  type AdminInvite,
  type AdminMember,
} from '@/api/admin'
import { errorMessage } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

/** Admin accounts: one family's members and invite links. */
const auth = useAuthStore()
const route = useRoute()
const nav = useAppNavigation()
const id = computed(() => Number(route.params.id))

const family = ref<AdminFamily | null>(null)
const busy = ref(false)
const error = ref('')

const renaming = ref(false)
const newName = ref('')
const confirmingDelete = ref(false)
const removingId = ref<number | null>(null)

const inviteUses = ref<'1' | '5' | 'unlimited'>('1')
const inviteDays = ref(7)
const newLink = ref('')
const copied = ref(false)
const canShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function'

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
    family.value = await fetchAdminFamily(id.value)
  })

const saveName = () =>
  run(async () => {
    const updated = await renameFamily(id.value, newName.value.trim())
    if (family.value) family.value.name = updated.name
    renaming.value = false
  })

const destroy = () =>
  run(async () => {
    await deleteFamily(id.value)
    await nav.back({ name: 'admin-families' })
  })

const remove = (member: AdminMember) =>
  run(async () => {
    await removeMember(id.value, member.id)
    removingId.value = null
    await load()
  })

const makeInvite = () =>
  run(async () => {
    const maxUses = inviteUses.value === 'unlimited' ? null : Number(inviteUses.value)
    const { url } = await createInvite(id.value, maxUses, inviteDays.value)
    newLink.value = url
    copied.value = false
    await load()
  })

const cancelInvite = (invite: AdminInvite) =>
  run(async () => {
    await revokeInvite(invite.id)
    await load()
  })

async function copyLink(): Promise<void> {
  try {
    await navigator.clipboard.writeText(newLink.value)
    copied.value = true
  } catch {
    error.value = 'Couldn’t copy. Select the link and copy it instead.'
  }
}

async function shareLink(): Promise<void> {
  await navigator
    .share({ title: `Join ${family.value?.name} on Kam Apps`, url: newLink.value })
    .catch(() => undefined) // Closing the share sheet isn't an error.
}

function describeInvite(invite: AdminInvite): string {
  const uses =
    invite.max_uses === null ? `${invite.uses} joined` : `${invite.uses} of ${invite.max_uses} used`
  const expires = new Date(invite.expires_at).toLocaleDateString(undefined, { dateStyle: 'medium' })
  return `${uses} · expires ${expires}`
}

function memberLabel(member: AdminMember): string {
  return member.profiles.length > 0 ? member.profiles.join(', ') : 'No profile yet'
}

onMounted(load)
</script>

<template>
  <main class="narrow stack">
    <BackLink :to="{ name: 'admin-families' }">‹ Families</BackLink>

    <p v-if="!family && !error" class="muted">Loading…</p>
    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <template v-if="family">
      <form v-if="renaming" class="row" @submit.prevent="saveName">
        <input v-model="newName" type="text" maxlength="100" required aria-label="Family name" />
        <button type="submit" :disabled="busy || newName.trim() === ''">Save</button>
        <button type="button" class="link" @click="renaming = false">Cancel</button>
      </form>
      <div v-else class="title-row">
        <h1>{{ family.name }}</h1>
        <button type="button" class="link" @click="((renaming = true), (newName = family.name))">
          Rename
        </button>
      </div>

      <section class="card stack">
        <h2>Invite someone</h2>
        <p class="muted small">
          Anyone with the link can join {{ family.name }}. New people create their account with it.
        </p>
        <div class="invite-options">
          <label>
            Who can use it
            <select v-model="inviteUses">
              <option value="1">One person</option>
              <option value="5">Up to 5 people</option>
              <option value="unlimited">Anyone with the link</option>
            </select>
          </label>
          <label>
            Expires after
            <select v-model.number="inviteDays">
              <option :value="1">1 day</option>
              <option :value="7">7 days</option>
              <option :value="30">30 days</option>
            </select>
          </label>
        </div>
        <button type="button" :disabled="busy" @click="makeInvite">Create invite link</button>

        <div v-if="newLink" class="new-link stack">
          <label for="invite-link">Send this link. It’s only shown once.</label>
          <input
            id="invite-link"
            :value="newLink"
            type="text"
            readonly
            @focus="($event.target as HTMLInputElement).select()"
          />
          <div class="row">
            <button type="button" class="secondary" @click="copyLink">
              {{ copied ? 'Copied' : 'Copy link' }}
            </button>
            <button v-if="canShare" type="button" class="secondary" @click="shareLink">
              Share…
            </button>
          </div>
        </div>

        <template v-if="family.invites && family.invites.length > 0">
          <h3>Open invite links</h3>
          <ul class="list">
            <li v-for="invite in family.invites" :key="invite.id" class="item">
              <span class="small">{{ describeInvite(invite) }}</span>
              <button
                type="button"
                class="link danger-link"
                :disabled="busy"
                @click="cancelInvite(invite)"
              >
                Cancel
              </button>
            </li>
          </ul>
        </template>
      </section>

      <section class="card stack">
        <h2>Members</h2>
        <p v-if="!family.members?.length" class="muted">No one yet. Send an invite link.</p>
        <ul v-else class="list">
          <li v-for="member in family.members" :key="member.id">
            <div v-if="removingId === member.id" class="stack">
              <p>Remove {{ member.email }} from {{ family.name }}? Their account stays.</p>
              <div class="row">
                <button type="button" class="danger" :disabled="busy" @click="remove(member)">
                  Remove
                </button>
                <button type="button" class="link" @click="removingId = null">Cancel</button>
              </div>
            </div>
            <div v-else class="item">
              <div class="who">
                <strong>{{ memberLabel(member) }}</strong>
                <span class="muted small">
                  {{ member.email }}{{ member.id === auth.user?.id ? ' · You' : '' }}
                </span>
              </div>
              <button type="button" class="link danger-link" @click="removingId = member.id">
                Remove
              </button>
            </div>
          </li>
        </ul>
      </section>

      <section class="card stack">
        <h2>Delete family</h2>
        <template v-if="confirmingDelete">
          <p>
            Delete {{ family.name }}? Its members keep their accounts but stop sharing with each
            other. Open invite links stop working.
          </p>
          <div class="row">
            <button type="button" class="danger" :disabled="busy" @click="destroy">
              Delete {{ family.name }}
            </button>
            <button type="button" class="link" @click="confirmingDelete = false">Cancel</button>
          </div>
        </template>
        <button v-else type="button" class="danger delete" @click="confirmingDelete = true">
          Delete family
        </button>
      </section>
    </template>
  </main>
</template>

<style scoped>
.title-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
}

h2,
h3 {
  margin: 0;
}

h3 {
  font-size: 0.95rem;
}

.invite-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
  gap: 0.75rem;
}

.invite-options label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.new-link {
  padding: 0.75rem;
  border-radius: 10px;
  background: var(--accent-soft);
}

.new-link input {
  font-size: 0.9rem;
}

.list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.list li + li {
  border-top: 1px solid var(--border);
}

.list li {
  padding: 0.6rem 0;
}

.item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.who {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow-wrap: anywhere;
}

.danger-link {
  color: var(--danger);
}

.small {
  font-size: 0.875rem;
}

.delete {
  align-self: flex-start;
}
</style>
