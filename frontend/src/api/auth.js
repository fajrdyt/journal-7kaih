import api from './axios'

export async function login(payload) {
  const response = await api.post('/auth/login', payload)

  return response.data
}

export async function logout() {
  const response = await api.post('/auth/logout')

  return response.data
}

export async function getMe() {
  const response = await api.get('/auth/me')

  return response.data
}


export function getProfile() {
  return api.get('/profile')
}

export function updateProfile(payload) {
  return api.put('/profile', payload)
}

export function updatePassword(payload) {
  return api.put('/profile/password', payload)
}

