import api from './axios'

export const userApi = {
  // Profile akun login
  getProfile: () => api.get('/profile'),

  updateProfile: (data) => api.put('/profile', data),

  updatePassword: (data) => api.put('/profile/password', data),

  // Users
  getUsers: (params = {}) =>
    api.get('/admin/users', {
      params,
    }),

  getUser: (id) => api.get(`/admin/users/${id}`),

  createUser: (data) => api.post('/admin/users', data),

  updateUser: (id, data) => api.put(`/admin/users/${id}`, data),

  deleteUser: (id) => api.delete(`/admin/users/${id}`),

  resetPassword: (id, data) =>
    api.post(`/admin/users/${id}/reset-password`, data),

  // Classes
  getClasses: (params = {}) =>
    api.get('/admin/classes', {
      params,
    }),

  getClass: (id) => api.get(`/admin/classes/${id}`),

  createClass: (data) => api.post('/admin/classes', data),

  updateClass: (id, data) => api.put(`/admin/classes/${id}`, data),

  deleteClass: (id) => api.delete(`/admin/classes/${id}`),
}

