// The server injects window.__CC_API__ when it mounts the app under a sub-path,
// so a cPanel deployment does not need a rebuilt bundle per host.
const INJECTED_API = typeof window !== 'undefined' ? window.__CC_API__ : ''
export const API = INJECTED_API || import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
export const APP_BASE = INJECTED_API ? INJECTED_API.replace(/\/api\/?$/, '') : ''

export function formatError(body) {
  if (!body || typeof body !== 'object') return 'Something went wrong.'
  if (typeof body.detail === 'string') return body.detail
  if (Array.isArray(body.detail)) return body.detail.join(' ')
  const parts = []
  for (const [key, value] of Object.entries(body)) {
    const message = Array.isArray(value) ? value.join(' ') : typeof value === 'string' ? value : ''
    if (message) parts.push(key === 'non_field_errors' ? message : `${key}: ${message}`)
  }
  return parts.join(' ') || 'Something went wrong.'
}

async function parse(response) {
  const text = await response.text()
  if (!text) return {}
  try {
    return JSON.parse(text)
  } catch {
    return { detail: `Unexpected ${response.status} from ${response.url} (starts: ${text.slice(0, 120)})` }
  }
}

function fail(response, body) {
  const error = new Error(formatError(body))
  error.status = response.status
  error.body = body
  return error
}

async function refreshAccess() {
  const refresh = localStorage.getItem('campusCoinRefresh')
  if (!refresh) return false
  const response = await fetch(`${API}/auth/refresh/`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refresh }),
  })
  if (!response.ok) return false
  const body = await parse(response)
  if (!body.access) return false
  localStorage.setItem('campusCoinToken', body.access)
  return true
}

export async function restoreSession() {
  if (localStorage.getItem('campusCoinToken')) return true
  return refreshAccess()
}

function bounceToLogin() {
  const path = window.location.pathname.slice(APP_BASE.length) || '/'
  if (path === '/' || path === '/login' || path === '/register' || path === '/reset-password') return
  window.location.assign(`${APP_BASE}/login`)
}

export async function publicJson(path, payload) {
  let response
  try {
    response = await fetch(`${API}${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
  } catch {
    throw new Error('Cannot reach Campus Coin. Start the API server and try again.')
  }
  const body = await parse(response)
  if (!response.ok) throw fail(response, body)
  return body
}

export async function api(path, options = {}, retried = false) {
  const headers = { ...(options.headers || {}) }
  const token = localStorage.getItem('campusCoinToken')
  if (token) headers.Authorization = `Bearer ${token}`
  let body = options.body
  if (body instanceof FormData) {
    delete headers['Content-Type']
  } else if (body && typeof body !== 'string') {
    headers['Content-Type'] = 'application/json'
    body = JSON.stringify(body)
  }
  let response
  try {
    response = await fetch(`${API}${path}`, { ...options, headers, body })
  } catch {
    throw new Error('Cannot reach Campus Coin. Start the API server and try again.')
  }
  if (response.status === 401 && !retried && !path.startsWith('/auth/login')) {
    const refreshed = await refreshAccess().catch(() => false)
    if (refreshed) return api(path, options, true)
    localStorage.removeItem('campusCoinToken')
    localStorage.removeItem('campusCoinRefresh')
    bounceToLogin()
  }
  const payload = await parse(response)
  if (!response.ok) throw fail(response, payload)
  return payload
}
