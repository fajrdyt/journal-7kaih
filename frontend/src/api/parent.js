import api from './axios'

export function getParentChildren() {
  return api.get('/parent/children')
}

export function getChildCheckins(studentId, params = {}) {
  return api.get(`/parent/children/${studentId}/checkins`, {
    params,
  })
}

export function getParentCheckinDetail(checkinId) {
  return api.get(`/parent/checkins/${checkinId}`)
}

export function validateHomeCheckin(checkinId, payload = {}) {
  return api.post(
    `/parent/checkins/${checkinId}/validate-home`,
    payload,
  )
}

export function validateParentCheckinItem(itemId, payload = {}) {
  return api.post(
    `/parent/checkin-items/${itemId}/validate`,
    payload,
  )
}

export function getChildRecap(studentId, params = {}) {
  return api.get(`/parent/children/${studentId}/recap`, {
    params,
  })
}