import { ApiError, apiRequest, clearApiToken, setApiToken } from '../lib/api'
import { ref, type Ref } from 'vue'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: 'student' | 'admin'
}

export interface AuthSessionRecord {
  token: string
  user: AuthUser
  authenticatedAt: string
}

interface AuthSuccessPayload {
  status: string
  message?: string
  data: {
    user: AuthUser
    token: string
  }
}

const AUTH_SESSION_KEY = 'atcsedu-auth-session'

const canUseStorage = typeof window !== 'undefined'

const readSession = (): AuthSessionRecord | null => {
  if (!canUseStorage) {
    return null
  }

  const rawValue = window.localStorage.getItem(AUTH_SESSION_KEY)

  if (!rawValue) {
    return null
  }

  try {
    return JSON.parse(rawValue) as AuthSessionRecord
  } catch {
    return null
  }
}

export const authSessionRef: Ref<AuthSessionRecord | null> = ref(canUseStorage ? readSession() : null)

const writeSession = (session: AuthSessionRecord) => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(session))
  setApiToken(session.token)
  authSessionRef.value = session
}

export const getAuthSession = () => {
  const session = authSessionRef.value ?? readSession()

  if (!session) {
    return null
  }

  setApiToken(session.token)
  authSessionRef.value = session

  return session
}

export const clearAuthSession = () => {
  if (canUseStorage) {
    window.localStorage.removeItem(AUTH_SESSION_KEY)
  }

  authSessionRef.value = null
  clearApiToken()
}

const storeLoginSession = (payload: AuthSuccessPayload) => {
  const session: AuthSessionRecord = {
    token: payload.data.token,
    user: payload.data.user,
    authenticatedAt: new Date().toISOString(),
  }

  writeSession(session)

  return session
}

export async function registerUser(payload: {
  name: string
  email: string
  password: string
  password_confirmation: string
}) {
  return apiRequest<AuthSuccessPayload>('/auth/register', {
    method: 'POST',
    body: payload,
  })
}

export async function loginUser(payload: {
  email: string
  password: string
}, expectedRole?: AuthUser['role']) {
  const response = await apiRequest<AuthSuccessPayload>('/auth/login', {
    method: 'POST',
    body: payload,
  })

  if (expectedRole && response.data.user.role !== expectedRole) {
    throw new ApiError(
      expectedRole === 'admin'
        ? 'This account does not have admin access.'
        : 'This account is not a student account.',
      403,
    )
  }

  return storeLoginSession(response)
}

export async function logoutUser() {
  try {
    await apiRequest('/auth/logout', {
      method: 'POST',
    })
  } finally {
    clearAuthSession()
  }
}
