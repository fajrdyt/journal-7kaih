import api from './axios'

export const habitApi = {
  getHabits: (params = {}) => api.get('/habits', { params }),

  // Ini akan jalan kalau backend admin habits sudah dibuat
  createHabit: (data) => api.post('/admin/habits', data),
  updateHabit: (id, data) => api.put(`/admin/habits/${id}`, data),
  deleteHabit: (id) => api.delete(`/admin/habits/${id}`),
}
