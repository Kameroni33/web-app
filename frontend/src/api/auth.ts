import { ensureCsrfCookie, http } from './http'

export interface User {
  id: number
  email: string
}

export interface DeviceSession {
  ip_address: string | null
  user_agent: string | null
  last_active_at: string
  is_current: boolean
}

export async function requestLoginCode(email: string): Promise<void> {
  await ensureCsrfCookie()
  await http.post('/api/auth/login-code', { email })
}

export async function verifyLoginCode(email: string, code: string): Promise<User> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: User }>('/api/auth/verify-code', { email, code })
  return data.data
}

export async function verifyLoginLink(token: string): Promise<User> {
  await ensureCsrfCookie()
  const { data } = await http.post<{ data: User }>('/api/auth/verify-link', { token })
  return data.data
}

/** The signed-in user, or null when there is no session. */
export async function fetchCurrentUser(): Promise<User | null> {
  try {
    const { data } = await http.get<{ data: User }>('/api/me')
    return data.data
  } catch (error) {
    if ((error as { response?: { status?: number } }).response?.status === 401) return null
    throw error
  }
}

export async function logout(): Promise<void> {
  await ensureCsrfCookie()
  await http.post('/api/auth/logout')
}

export async function listSessions(): Promise<DeviceSession[]> {
  const { data } = await http.get<{ data: DeviceSession[] }>('/api/sessions')
  return data.data
}

export async function signOutOtherSessions(): Promise<void> {
  await ensureCsrfCookie()
  await http.delete('/api/sessions/others')
}
