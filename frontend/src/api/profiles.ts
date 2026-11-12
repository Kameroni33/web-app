import { ensureCsrfCookie, http } from './http'

export type ProfileRole = 'admin' | 'member'

/** A person using the account. */
export interface Profile {
  id: number
  name: string
  role: ProfileRole
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
