import api from './axios'

function createLoginPayload(payloadOrIdentifier, password = null) {
  if (
    typeof payloadOrIdentifier === 'object' &&
    payloadOrIdentifier !== null
  ) {
    return payloadOrIdentifier
  }

  return {
    identifier: payloadOrIdentifier,
    password,
  }
}

export async function login(payloadOrIdentifier, password = null) {
  const response = await api.post(
    '/auth/login',
    createLoginPayload(payloadOrIdentifier, password),
  )

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

export async function getProfile() {
  const response = await api.get('/profile')

  return response.data
}

export async function updateProfile(payload) {
  const response = await api.put('/profile', payload)

  return response.data
}

export async function updatePassword(payload) {
  const response = await api.put('/profile/password', payload)

  return response.data
}

export async function uploadAvatar(file) {
  const response = await api.postForm('/profile/avatar', {
    avatar: file,
  })

  return response.data
}

export async function deleteAvatar() {
  const response = await api.delete('/profile/avatar')

  return response.data
}

export const authApi = {
  login: (identifier, password) =>
    api.post(
      '/auth/login',
      createLoginPayload(identifier, password),
    ),

  me: () => api.get('/auth/me'),

  logout: () => api.post('/auth/logout'),

  getProfile: () => api.get('/profile'),

  updateProfile: (payload) =>
    api.put('/profile', payload),

  updatePassword: (payload) =>
    api.put('/profile/password', payload),

  uploadAvatar: (file) =>
    api.postForm('/profile/avatar', {
      avatar: file,
    }),

  deleteAvatar: () =>
    api.delete('/profile/avatar'),
}