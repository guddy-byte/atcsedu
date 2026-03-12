export interface AdminSessionRecord {
  email: string
  authenticatedAt: string
}

export const DEMO_ADMIN_CREDENTIALS = {
  email: 'admin@atcsedu.com',
  password: 'ATCSAdmin2026!',
} as const

const ADMIN_SESSION_KEY = 'atcsedu-admin-session'

const canUseStorage = typeof window !== 'undefined'

const readJson = <T>(key: string, fallback: T): T => {
  if (!canUseStorage) {
    return fallback
  }

  const rawValue = window.localStorage.getItem(key)

  if (!rawValue) {
    return fallback
  }

  try {
    return JSON.parse(rawValue) as T
  } catch {
    return fallback
  }
}

const writeJson = (key: string, value: unknown) => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.setItem(key, JSON.stringify(value))
}

export const getAdminSession = (): AdminSessionRecord | null =>
  readJson<AdminSessionRecord | null>(ADMIN_SESSION_KEY, null)

export const isAdminAuthenticated = () => Boolean(getAdminSession())

export const loginAdmin = (email: string, password: string) => {
  const normalizedEmail = email.trim().toLowerCase()
  const normalizedPassword = password.trim()

  if (
    normalizedEmail !== DEMO_ADMIN_CREDENTIALS.email ||
    normalizedPassword !== DEMO_ADMIN_CREDENTIALS.password
  ) {
    return false
  }

  writeJson(ADMIN_SESSION_KEY, {
    email: normalizedEmail,
    authenticatedAt: new Date().toISOString(),
  } satisfies AdminSessionRecord)

  return true
}

export const logoutAdmin = () => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.removeItem(ADMIN_SESSION_KEY)
}
