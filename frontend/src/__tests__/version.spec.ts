import { describe, expect, it } from 'vitest'
import { clientVersion, recordServerVersion, serverVersion, versionsMatch } from '@/api/version'

describe('app/server version check', () => {
  it('matches until the server reports a different release', () => {
    expect(clientVersion).toMatch(/^\d{4}\.([1-9]|1[0-2])\.\d+$/)
    expect(versionsMatch.value).toBe(true)

    recordServerVersion({ 'x-app-version': clientVersion })
    expect(versionsMatch.value).toBe(true)

    recordServerVersion({ 'x-app-version': '1999.1.0' })
    expect(serverVersion.value).toBe('1999.1.0')
    expect(versionsMatch.value).toBe(false)

    recordServerVersion({}) // responses without the header (e.g. errors from a proxy) change nothing
    expect(serverVersion.value).toBe('1999.1.0')
  })
})
