import api from './axios'

export const analyticsApi = {
  trackEvent: (data) => api.post('/events/track', data),
}
