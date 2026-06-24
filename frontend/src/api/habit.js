import api from './axios'

export const habitApi = {
  getHabits: (params = {}) =>
    api.get('/habits', { params }),
}