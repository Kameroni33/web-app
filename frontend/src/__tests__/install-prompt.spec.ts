import { describe, expect, it } from 'vitest'
import { isIos, isStandalone } from '@/composables/useInstallPrompt'

function nav(userAgent: string, maxTouchPoints = 0): Navigator {
  return { userAgent, maxTouchPoints } as Navigator
}

describe('isIos', () => {
  it('detects iPhone', () => {
    expect(isIos(nav('Mozilla/5.0 (iPhone; CPU iPhone OS 26_1 like Mac OS X) Safari'))).toBe(true)
  })

  it('detects iPadOS, which reports itself as a Mac with touch', () => {
    expect(isIos(nav('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari', 5))).toBe(true)
  })

  it('does not flag a desktop Mac or Android', () => {
    expect(isIos(nav('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari', 0))).toBe(false)
    expect(isIos(nav('Mozilla/5.0 (Linux; Android 15) Chrome'))).toBe(false)
  })
})

describe('isStandalone', () => {
  const win = (displayModeStandalone: boolean, iosStandalone?: boolean) =>
    ({
      matchMedia: () => ({ matches: displayModeStandalone }),
      navigator: { standalone: iosStandalone },
    }) as unknown as Window

  it('is true when launched from the home screen', () => {
    expect(isStandalone(win(true))).toBe(true)
    expect(isStandalone(win(false, true))).toBe(true)
  })

  it('is false in a browser tab', () => {
    expect(isStandalone(win(false))).toBe(false)
  })
})
