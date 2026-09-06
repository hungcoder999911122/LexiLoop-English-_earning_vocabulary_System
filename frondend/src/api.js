const API = 'http://localhost:8080/api'

export function getToken() {
  return localStorage.getItem('lexi_token')
}

export function getUser() {
  const raw = localStorage.getItem('lexi_user')
  return raw ? JSON.parse(raw) : null
}

export function setSession(auth) {
  localStorage.setItem('lexi_token', auth.token)
  localStorage.setItem('lexi_user', JSON.stringify(auth))
}

export function logout() {
  localStorage.removeItem('lexi_token')
  localStorage.removeItem('lexi_user')
}

export async function api(path, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`
  const res = await fetch(API + path, { ...options, headers })
  if (res.status === 204) return null
  const text = await res.text()
  const data = text ? JSON.parse(text) : null
  if (!res.ok) {
    throw new Error(data?.message || 'Yêu cầu thất bại')
  }
  return data
}
