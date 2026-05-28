import api from './axios'

export async function login(payload) {

  const response = await api.post('/login', payload)

  return response.data
}

export async function logout() {

  const response = await api.post('/logout')

  return response.data
}

export async function getMe() {

  const response = await api.get('/me')

  return response.data
}