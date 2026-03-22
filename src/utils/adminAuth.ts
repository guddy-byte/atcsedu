import { getAuthSession, loginUser, logoutUser } from './authSession'

export interface AdminSessionRecord {
  name: string
  email: string
  authenticatedAt: string
}

export const DEMO_ADMIN_CREDENTIALS = {
  email: 'admin@atcsedu.com',
  password: 'ATCSAdmin2026!',
} as const

export const getAdminSession = (): AdminSessionRecord | null => {
  const session = getAuthSession()

  if (!session || session.user.role !== 'admin') {
    return null
  }

  return {
    name: session.user.name,
    email: session.user.email,
    authenticatedAt: session.authenticatedAt,
  }
}

export const isAdminAuthenticated = () => Boolean(getAdminSession())

export const loginAdmin = async (email: string, password: string) => {
  await loginUser({ email, password }, 'admin')
  return true
}

export const logoutAdmin = async () => {
  await logoutUser()
}
