import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { recordServerVersion } from './version'

/**
 * Same-origin client for Laravel. Sanctum SPA auth: the session lives in an
 * httpOnly cookie and axios echoes the XSRF-TOKEN cookie back as a header.
 */
export const http = axios.create({
  baseURL: '/',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
})

let csrfReady: Promise<unknown> | null = null

/** Fetch the CSRF cookie once per page load (again after it expires). */
export function ensureCsrfCookie(force = false): Promise<unknown> {
  if (force || csrfReady === null) {
    csrfReady = http.get('/sanctum/csrf-cookie').catch((error) => {
      csrfReady = null
      throw error
    })
  }
  return csrfReady
}

// Every Laravel response says which release answered; Settings compares it with ours.
http.interceptors.response.use(
  (response) => {
    recordServerVersion(response.headers)
    return response
  },
  (error: unknown) => {
    if (error instanceof AxiosError) recordServerVersion(error.response?.headers)
    throw error
  },
)

type RetriableConfig = InternalAxiosRequestConfig & { _csrfRetried?: boolean }

// 419 = CSRF token mismatch (e.g. the token rotated after sign-out): refresh it and retry once.
http.interceptors.response.use(undefined, async (error: AxiosError) => {
  const config = error.config as RetriableConfig | undefined
  if (error.response?.status === 419 && config && !config._csrfRetried) {
    config._csrfRetried = true
    await ensureCsrfCookie(true)
    return http(config)
  }
  throw error
})

/** The first validation message from a Laravel 422, or a generic fallback. */
export function errorMessage(
  error: unknown,
  fallback = 'Something went wrong. Please try again.',
): string {
  if (error instanceof AxiosError) {
    const status = error.response?.status
    if (status === 429) return 'Too many attempts. Please wait a few minutes and try again.'
    const data = error.response?.data as
      { message?: string; errors?: Record<string, string[]> } | undefined
    const first = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined
    if (first) return first
    if (!error.response) return 'Can’t reach the server. Check your connection.'
  }
  return fallback
}
