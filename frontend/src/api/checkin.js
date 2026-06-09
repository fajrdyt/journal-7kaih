import api from './axios'

export const getTodayCheckin = () =>
  api.get('/checkins/today')

export const submitCheckin = (payload) =>
  api.post('/checkins', payload)

export const getHistory = () =>
  api.get('/checkins/history')