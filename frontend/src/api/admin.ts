import { ensureCsrfCookie, http } from './http'

/** Admin-account endpoints (backend: groups). The app calls groups "families". */

export type InviteStatus = 'active' | 'revoked' | 'expired' | 'used_up'

export interface AdminInvite {
  id: number
  status: InviteStatus
  uses: number
  /** null = unlimited */
  max_uses: number | null
  expires_at: string
  created_at: string
}

export interface AdminMember {
  id: number
  email: string
  role: 'member' | 'manager'
  profiles: string[]
}

export interface AdminFamily {
  id: number
  name: string
  member_count: number
  members?: AdminMember[]
  invites?: AdminInvite[]
}

export async function listAdminFamilies(): Promise<AdminFamily[]> {
  const { data } = await http.get<{ data: AdminFamily[] }>('/api/admin/groups')
  return data.data
}

export async function fetchAdminFamily(id: number): Promise<AdminFamily> {
  const { data } = await http.get<{ data: AdminFamily }>(`/api/admin/groups/${id}`)
  return data.data
}

export async function createFamily(name: string): Promise<AdminFamily> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: AdminFamily }>('/api/admin/groups', { name })
  return data.data
}

export async function renameFamily(id: number, name: string): Promise<AdminFamily> {
  await ensureCsrfCookie()
  const { data } = await http.patch<{ data: AdminFamily }>(`/api/admin/groups/${id}`, { name })
  return data.data
}

export async function deleteFamily(id: number): Promise<void> {
  await ensureCsrfCookie()
  await http.delete(`/api/admin/groups/${id}`)
}

export async function removeMember(familyId: number, accountId: number): Promise<void> {
  await ensureCsrfCookie()
  await http.delete(`/api/admin/groups/${familyId}/members/${accountId}`)
}

/** The returned url is shown only this once; the server keeps just a hash. */
export async function createInvite(
  familyId: number,
  maxUses: number | null,
  expiresInDays: number,
): Promise<{ invite: AdminInvite; url: string }> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: AdminInvite; url: string }>(
    `/api/admin/groups/${familyId}/invites`,
    { max_uses: maxUses, expires_in_days: expiresInDays },
  )
  return { invite: data.data, url: data.url }
}

export async function revokeInvite(id: number): Promise<void> {
  await ensureCsrfCookie()
  await http.delete(`/api/admin/invites/${id}`)
}
