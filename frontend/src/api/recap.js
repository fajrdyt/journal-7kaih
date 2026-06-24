import api from './axios'

export const recapApi = {
  getTeacherClasses: () =>
    api.get('/teacher/classes'),

  getClassStudents: (classId, params = {}) =>
    api.get(`/teacher/classes/${classId}/students`, {
      params,
    }),

  getClassCheckins: (classId, params = {}) =>
    api.get(`/teacher/classes/${classId}/checkins`, {
      params,
    }),

  getWeeklyRecap: (classId, params = {}) =>
    api.get(`/teacher/classes/${classId}/weekly-recap`, {
      params,
    }),

  getMonthlyRecap: (classId, params = {}) =>
    api.get(`/teacher/classes/${classId}/monthly-recap`, {
      params,
    }),

  getClassHabitStatistics: (classId, params = {}) =>
    api.get(`/teacher/classes/${classId}/habit-statistics`, {
      params,
    }),

  getStudentRecap: (studentId, params = {}) =>
    api.get(`/teacher/students/${studentId}/recap`, {
      params,
    }),
}
