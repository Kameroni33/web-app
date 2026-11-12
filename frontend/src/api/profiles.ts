import { ensureCsrfCookie, http } from './http'

export type ProfileRole = 'admin' | 'member'

/** A person using the account. */
export interface Profile {
  id: number
  name: string
  role: ProfileRole
  /** Versioned photo URL, or null to show the initial. */
  avatar_url: string | null
}

/** A family (backend: group) and everyone in it. */
export interface Family {
  id: number
  name: string
  members: { id: number; name: string }[]
}

export async function listProfiles(): Promise<Profile[]> {
  const { data } = await http.get<{ data: Profile[] }>('/api/profiles')
  return data.data
}

export async function createProfile(name: string, role?: ProfileRole): Promise<Profile> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: Profile }>('/api/profiles', { name, role })
  return data.data
}

export async function updateProfile(
  id: number,
  changes: Partial<Pick<Profile, 'name' | 'role'>>,
): Promise<Profile> {
  await ensureCsrfCookie()
  const { data } = await http.patch<{ data: Profile }>(`/api/profiles/${id}`, changes)
  return data.data
}

export async function deleteProfile(id: number): Promise<void> {
  await ensureCsrfCookie()
  await http.delete(`/api/profiles/${id}`)
}

/** Use this profile on this device. */
export async function selectProfile(id: number): Promise<Profile> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: Profile }>(`/api/profiles/${id}/select`)
  return data.data
}

export async function listFamilies(): Promise<Family[]> {
  const { data } = await http.get<{ data: Family[] }>('/api/groups')
  return data.data
}

/** Set a profile's photo; the server crops it square and shrinks it. */
export async function uploadAvatar(id: number, image: Blob): Promise<Profile> {
  await ensureCsrfCookie()
  const form = new FormData()
  form.append('image', image, 'avatar.jpg')
  const { data } = await http.post<{ data: Profile }>(`/api/profiles/${id}/avatar`, form)
  return data.data
}

export async function removeAvatar(id: number): Promise<Profile> {
  await ensureCsrfCookie()
  const { data } = await http.delete<{ data: Profile }>(`/api/profiles/${id}/avatar`)
  return data.data
}
