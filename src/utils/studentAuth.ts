export interface StudentAccountRecord {
  email: string
  password: string
  createdAt: string
}

export interface StudentSessionRecord {
  email: string
  authenticatedAt: string
  source: 'registered' | 'demo'
}

export const DEMO_STUDENT_CREDENTIALS = {
  email: 'student.demo@atcsedu.com',
  password: 'ATCSDemo2026',
} as const

const STUDENT_ACCOUNT_KEY = 'atcsedu-student-account'
const STUDENT_SESSION_KEY = 'atcsedu-student-session'

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

export const getRegisteredStudent = (): StudentAccountRecord | null =>
  readJson<StudentAccountRecord | null>(STUDENT_ACCOUNT_KEY, null)

export const hasRegisteredStudent = () => Boolean(getRegisteredStudent())

export const getStudentSession = (): StudentSessionRecord | null => {
  const session = readJson<StudentSessionRecord | boolean | null>(STUDENT_SESSION_KEY, null)

  if (!session) {
    return null
  }

  if (typeof session === 'boolean') {
    if (!session) {
      return null
    }

    const registeredStudent = getRegisteredStudent()

    if (!registeredStudent) {
      return null
    }

    return {
      email: registeredStudent.email,
      authenticatedAt: new Date().toISOString(),
      source: 'registered',
    }
  }

  return session
}

export const isStudentAuthenticated = () => Boolean(getStudentSession())

export const registerStudent = (email: string, password: string) => {
  if (!canUseStorage) {
    return
  }

  writeJson(STUDENT_ACCOUNT_KEY, {
    email,
    password,
    createdAt: new Date().toISOString(),
  })
  window.localStorage.removeItem(STUDENT_SESSION_KEY)
}

export const loginStudent = (email: string, password: string) => {
  const normalizedEmail = email.trim().toLowerCase()
  const normalizedPassword = password.trim()

  if (
    normalizedEmail === DEMO_STUDENT_CREDENTIALS.email &&
    normalizedPassword === DEMO_STUDENT_CREDENTIALS.password
  ) {
    writeJson(STUDENT_SESSION_KEY, {
      email: normalizedEmail,
      authenticatedAt: new Date().toISOString(),
      source: 'demo',
    } satisfies StudentSessionRecord)
    return true
  }

  const existingStudent = getRegisteredStudent()

  if (!existingStudent) {
    return false
  }

  if (
    normalizedEmail !== existingStudent.email.toLowerCase() ||
    normalizedPassword !== existingStudent.password
  ) {
    return false
  }

  writeJson(STUDENT_SESSION_KEY, {
    email: existingStudent.email,
    authenticatedAt: new Date().toISOString(),
    source: 'registered',
  } satisfies StudentSessionRecord)
  return true
}

export const logoutStudent = () => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.removeItem(STUDENT_SESSION_KEY)
}

export const resolveProtectedStudentRoute = (targetPath: string) => {
  if (isStudentAuthenticated()) {
    return targetPath
  }

  const redirectQuery = `?redirect=${encodeURIComponent(targetPath)}`

  return hasRegisteredStudent() ? `/auth/login${redirectQuery}` : `/auth/signup${redirectQuery}`
}