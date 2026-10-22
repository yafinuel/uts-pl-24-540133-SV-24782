import { clearAuth, getToken } from './auth'
import router from '../router'

export interface ApiError {
  status: number
  message: string
  errors?: Record<string, string[]>
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken()
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(options.headers as Record<string, string>),
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const response = await fetch(`/api${path}`, { ...options, headers })

  if (response.status === 401 && !path.includes('/login')) {
    clearAuth()
    router.push('/login')
    const err: ApiError = { status: 401, message: 'Sesi habis, silakan login kembali' }
    throw err
  }

  const data = await response.json()

  if (!response.ok) {
    const err: ApiError = {
      status: response.status,
      message: data.message ?? 'Terjadi kesalahan',
      errors: data.errors,
    }
    throw err
  }

  return data as T
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, body: unknown) =>
    request<T>(path, { method: 'POST', body: JSON.stringify(body) }),
  put: <T>(path: string, body: unknown) =>
    request<T>(path, { method: 'PUT', body: JSON.stringify(body) }),
  delete: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
}
