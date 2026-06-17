import api from './axios'

export function getHabits() {
  return api.get('/habits')
}

export function getTodayCheckin() {
  return api.get('/student/checkins/today')
}

export function saveCheckin(payload) {
  return api.post('/student/checkins', payload)
}

export function getCheckinHistory(params = {}) {
  return api.get('/student/checkins', { params })
}

export function getStudentRecap(params = {}) {
  return api.get('/student/recap', { params })
}