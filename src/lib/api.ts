export class ApiError extends Error {
  status: number
  details?: unknown

  constructor(message: string, status = 500, details?: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.details = details
  }
}

const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1').replace(/\/+$/, '')
const AUTH_TOKEN_KEY = 'atcsedu-api-token'

export const getApiBaseUrl = () => API_BASE_URL

export const getApiToken = () => {
  if (typeof window === 'undefined') {
    return null
  }

  return window.localStorage.getItem(AUTH_TOKEN_KEY)
}

export const setApiToken = (token: string) => {
  if (typeof window === 'undefined') {
    return
  }

  window.localStorage.setItem(AUTH_TOKEN_KEY, token)
}

export const clearApiToken = () => {
  if (typeof window === 'undefined') {
    return
  }

  window.localStorage.removeItem(AUTH_TOKEN_KEY)
}

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | Record<string, unknown> | null
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const headers = new Headers(options.headers ?? {})
  const token = getApiToken()
  const body = options.body

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  if (!headers.has('Accept')) {
    headers.set('Accept', 'application/json')
  }

  if (body && !(body instanceof FormData) && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
    body: body && !(body instanceof FormData) ? JSON.stringify(body) : (body ?? null),
  })

  const payload = await response
    .json()
    .catch(() => null)

  if (!response.ok) {
    if (response.status === 401) {
      clearApiToken()
    }

    throw new ApiError(
      payload?.message ?? 'Something went wrong while talking to the API.',
      response.status,
      payload,
    )
  }

  return payload as T
}
