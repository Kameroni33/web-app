import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import * as api from './api'

/** The active profile's own list and wishlist links. */
export const useMyGiftsStore = defineStore('my-gifts', () => {
  const auth = useAuthStore()
  const items = ref<api.MyItem[]>([])
  const wishlists = ref<api.Wishlist[]>([])
  const loaded = ref(false)

  const wanted = computed(() => items.value.filter((item) => !item.received))
  const received = computed(() => items.value.filter((item) => item.received))
  const categories = computed(() =>
    [...new Set(items.value.map((item) => item.category).filter((c): c is string => !!c))].sort(
      (a, b) => a.localeCompare(b),
    ),
  )

  async function load(): Promise<void> {
    const [mine, lists] = await Promise.all([api.listMyItems(), api.listWishlists()])
    items.value = mine
    wishlists.value = lists
    loaded.value = true
  }

  function put(item: api.MyItem): void {
    const index = items.value.findIndex((i) => i.id === item.id)
    if (index === -1) items.value.push(item)
    else items.value[index] = item
  }

  async function add(input: api.ItemInput): Promise<api.MyItem> {
    const item = await api.createItem(input)
    put(item)
    return item
  }

  async function update(id: number, input: api.ItemInput): Promise<void> {
    put(await api.updateItem(id, input))
  }

  async function remove(id: number): Promise<void> {
    await api.deleteItem(id)
    items.value = items.value.filter((i) => i.id !== id)
  }

  /** New order for the wanted items (received ones keep their place at the end). */
  async function reorder(ordered: api.MyItem[]): Promise<void> {
    items.value = [...ordered, ...received.value]
    await api.reorderItems(ordered.map((i) => i.id))
  }

  async function addWishlist(input: Omit<api.Wishlist, 'id'>): Promise<void> {
    wishlists.value.push(await api.createWishlist(input))
  }

  async function removeWishlist(id: number): Promise<void> {
    await api.deleteWishlist(id)
    wishlists.value = wishlists.value.filter((w) => w.id !== id)
  }

  // Another profile's list must never linger on screen after switching.
  watch(
    () => auth.profile?.id,
    () => {
      items.value = []
      wishlists.value = []
      loaded.value = false
    },
  )

  return {
    items,
    wishlists,
    loaded,
    wanted,
    received,
    categories,
    load,
    add,
    update,
    remove,
    reorder,
    addWishlist,
    removeWishlist,
  }
})
