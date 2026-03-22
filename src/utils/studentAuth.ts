import { getAuthSession, loginUser, logoutUser, registerUser } from './authSession'

export interface StudentSessionRecord {
  name: string
  email: string
  authenticatedAt: string
  source: 'api'
}

export const DEMO_STUDENT_CREDENTIALS = {
  email: 'student.demo@atcsedu.com',
  password: 'ATCSDemo2026',
} as const

export const getStudentSession = (): StudentSessionRecord | null => {
  const session = getAuthSession()

  if (!session || session.user.role !== 'student') {
    return null
  }

  return {
    name: session.user.name,
    email: session.user.email,
    authenticatedAt: session.authenticatedAt,
    source: 'api',
  }
}

export const isStudentAuthenticated = () => Boolean(getStudentSession())

export const registerStudent = async (name: string, email: string, password: string) => {
  await registerUser({
    name,
    email,
    password,
    password_confirmation: password,
  })
}

export const loginStudent = async (email: string, password: string) => {
  await loginUser({ email, password }, 'student')
  return true
}

export const logoutStudent = async () => {
  await logoutUser()
}

export const resolveProtectedStudentRoute = (targetPath: string) => {
  if (isStudentAuthenticated()) {
    return targetPath
  }

  const redirectQuery = `?redirect=${encodeURIComponent(targetPath)}`

  return `/auth/login${redirectQuery}`
}
