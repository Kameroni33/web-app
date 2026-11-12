import { ensureCsrfCookie, http } from '@/api/http'

/** One of your own items. Says nothing about claims, by design. */
export interface MyItem {
  id: number
  title: string
  links: string[]
  category: string | null
  notes: string | null
  received: boolean
}

export type ItemInput = Partial<Omit<MyItem, 'id'>>

/** Someone else's item. claim: 'mine', 'taken' (by someone) or null. */
export interface TheirItem {
  id: number
  title: string
  links: string[]
  category: string | null
  notes: string | null
  claim: 'mine' | 'taken' | null
  /** Deleted by its owner after you claimed it. */
  removed: boolean
}

export interface Wishlist {
  id: number
  label: string
  url: string
}

export interface Person {
  id: number
  name: string
  avatar_url: string | null
}

export interface People {
  account: Person[]
  families: { id: number; name: string; people: Person[] }[]
}

export interface TheirList {
  profile: Person & { same_account: boolean }
  items: TheirItem[]
  wishlists: Wishlist[]
}

type Data<T> = { data: T }

export async function listMyItems(): Promise<MyItem[]> {
  return (await http.get<Data<MyItem[]>>('/api/gifts/items')).data.data
}

export async function createItem(input: ItemInput): Promise<MyItem> {
  await ensureCsrfCookie()
  return (await http.post<Data<MyItem>>('/api/gifts/items', input)).data.data
}

export async function updateItem(id: number, input: ItemInput): Promise<MyItem> {
  await ensureCsrfCookie()
  return (await http.patch<Data<MyItem>>(`/api/gifts/items/${id}`, input)).data.data
}

export async function deleteItem(id: number): Promise<void> {
  await ensureCsrfCookie()
  await http.delete(`/api/gifts/items/${id}`)
}

export async function reorderItems(ids: number[]): Promise<void> {
  await ensureCsrfCookie()
  await http.put('/api/gifts/items/order', { ids })
}

export async function listWishlists(): Promise<Wishlist[]> {
  return (await http.get<Data<Wishlist[]>>('/api/gifts/wishlists')).data.data
}

export async function createWishlist(input: Omit<Wishlist, 'id'>): Promise<Wishlist> {
  await ensureCsrfCookie()
  return (await http.post<Data<Wishlist>>('/api/gifts/wishlists', input)).data.data
}

export async function deleteWishlist(id: number): Promise<void> {
  await ensureCsrfCookie()
  await http.delete(`/api/gifts/wishlists/${id}`)
}

export async function listPeople(): Promise<People> {
  return (await http.get<Data<People>>('/api/gifts/people')).data.data
}

export async function fetchList(profileId: number): Promise<TheirList> {
  return (await http.get<Data<TheirList>>(`/api/gifts/lists/${profileId}`)).data.data
}

export async function claimItem(id: number): Promise<TheirItem> {
  await ensureCsrfCookie()
  return (await http.post<Data<TheirItem>>(`/api/gifts/items/${id}/claim`)).data.data
}

export async function unclaimItem(id: number): Promise<TheirItem> {
  await ensureCsrfCookie()
  return (await http.delete<Data<TheirItem>>(`/api/gifts/items/${id}/claim`)).data.data
}

/** "amazon.com" from a link, for compact link chips. */
export function linkHost(url: string): string {
  try {
    return new URL(url).hostname.replace(/^www\./, '')
  } catch {
    return url
  }
}
