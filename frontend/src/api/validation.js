import api from './axios'

function buildValidationPayload(itemIds) {
  if (!Array.isArray(itemIds) || itemIds.length === 0) {
    return {}
  }

  return {
    item_ids: itemIds.map(Number),
  }
}

export function getChildren() {
  return api.get('/parent/children')
}

export function getChildCheckins(studentId, params = {}) {
  return api.get(`/parent/children/${studentId}/checkins`, {
    params,
  })
}

export function getChildRecap(studentId, params = {}) {
  return api.get(`/parent/children/${studentId}/recap`, {
    params,
  })
}

export function getParentCheckinDetail(checkinId) {
  return api.get(`/parent/checkins/${checkinId}`)
}

export function validateParentCheckin(checkinId, itemIds = null) {
  return api.post(
    `/parent/checkins/${checkinId}/validate-home`,
    buildValidationPayload(itemIds),
  )
}

export function validateParentItem(itemId) {
  return api.post(`/parent/checkin-items/${itemId}/validate`)
}

export function getTeacherCheckinDetail(checkinId) {
  return api.get(`/teacher/checkins/${checkinId}`)
}

export function validateTeacherCheckin(checkinId, itemIds = null) {
  return api.post(
    `/teacher/checkins/${checkinId}/validate-school`,
    buildValidationPayload(itemIds),
  )
}

export function validateTeacherItem(itemId) {
  return api.post(`/teacher/checkin-items/${itemId}/validate`)
}

export const validationApi = {
  getChildren,
  getChildCheckins,
  getChildRecap,
  getParentCheckinDetail,
  validateParentCheckin,
  validateParentItem,
  getTeacherCheckinDetail,
  validateTeacherCheckin,
  validateTeacherItem,
  validateHome: validateParentCheckin,
  validateHomeItem: validateParentItem,
  validateSchool: validateTeacherCheckin,
  validateSchoolItem: validateTeacherItem,
}
