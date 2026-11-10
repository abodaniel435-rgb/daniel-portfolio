// The SPA always talks to the same origin by default (/api), which is what the
// cPanel deployment does. During development Vite proxies /api to Django on
// 127.0.0.1:8001, and VITE_API_URL can point anywhere else when needed.
export const API = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '')

export function formatError(body) {
  if (!body || typeof body !== 'object') return 'Something went wrong.'
  if (typeof body.detail === 'string') return body.detail
  if (Array.isArray(body.detail)) return body.detail.join(' ')
  const parts = []
  for (const [key, value] of Object.entries(body)) {
    const message = Array.isArray(value) ? value.join(' ') : typeof value === 'string' ? value : ''
    if (message) parts.push(key === 'non_field_errors' ? message : `${label(key)}: ${message}`)
  }
  return parts.join(' ') || 'Something went wrong.'
}

/**
 * Per-field messages from a DRF error body, so forms can show inline errors
 * next to the input that caused them instead of one wall of text.
 */
export function fieldErrors(body) {
  if (!body || typeof body !== 'object') return {}
  const out = {}
  for (const [key, value] of Object.entries(body)) {
    const message = Array.isArray(value) ? value.join(' ') : typeof value === 'string' ? value : ''
    if (message) out[key] = message
  }
  return out
}

function label(key) {
  return key.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
}

async function parse(response) {
  const text = await response.text()
  if (!text) return {}
  try {
    return JSON.parse(text)
  } catch {
    return { detail: `Unexpected ${response.status} from ${response.url}` }
  }
}

function fail(response, body) {
  const error = new Error(formatError(body))
  error.status = response.status
  error.body = body
  error.fields = fieldErrors(body)
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
  const path = window.location.pathname
  if (path === '/' || path === '/login' || path === '/register' || path === '/reset-password') return
  window.location.assign('/login')
}

export async function publicJson(path, payload, method = 'POST') {
  let response
  try {
    response = await fetch(`${API}${path}`, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: payload === undefined ? undefined : JSON.stringify(payload),
    })
  } catch {
    throw new Error('Cannot reach Campus Coin. Check your connection and try again.')
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
    throw new Error('Cannot reach Campus Coin. Check your connection and try again.')
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
