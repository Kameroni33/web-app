import { ensureCsrfCookie, http } from './http'

export interface InvitePreview {
  group: { name: string }
  expires_at: string
  /** True when the signed-in account is already in this family. */
  is_member: boolean
}

export async function fetchInvite(token: string): Promise<InvitePreview> {
  const { data } = await http.get<{ data: InvitePreview }>(`/api/invites/${token}`)
  return data.data
}

/** New here: creates the account if needed and emails a sign-in code. */
export async function registerFromInvite(token: string, email: string): Promise<void> {
  await ensureCsrfCookie()
  await http.post(`/api/invites/${token}/register`, { email })
}

export async function acceptInvite(token: string): Promise<{ id: number; name: string }> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: { id: number; name: string } }>(
    `/api/invites/${token}/accept`,
  )
  return data.data
}
