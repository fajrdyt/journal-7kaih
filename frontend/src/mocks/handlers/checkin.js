import { http, HttpResponse } from 'msw'
import { checkinsDB } from '../data/checkins'
import { delay } from '../utils/delay'

export const checkinHandlers = [
  http.get('*/api/v1/student/checkins/today', async () => {
    await delay(500)

    return HttpResponse.json({
      message: 'Data check-in berhasil diambil',
      data: checkinsDB.todayCheckin,
    })
  }),

  http.post('*/api/v1/student/checkins', async ({ request }) => {
    await delay(500)

    const body = await request.json()

    if (!body.items) {
      return HttpResponse.json(
        {
          message: 'Items wajib diisi',
        },
        {
          status: 422,
        },
      )
    }

    checkinsDB.todayCheckin = {
      id: 1,
      checkin_date: body.checkin_date,
      notes: body.notes ?? '',
      habits: body.items.map((item) => ({
        id: item.habit_id,
        habit_id: item.habit_id,
        name: checkinsDB.todayCheckin.habits.find((habit) => habit.id === item.habit_id)?.name ?? 'Kebiasaan',
        completed: item.is_done,
        note: item.notes ?? '',
        activity_context: item.activity_context ?? 'rumah',
      })),
    }

    return HttpResponse.json(
      {
        message: 'Check-in berhasil disimpan',
        data: checkinsDB.todayCheckin,
      },
      {
        status: 201,
      },
    )
  }),

  http.get('*/api/v1/student/checkins', async () => {
    await delay(500)

    return HttpResponse.json({
      message: 'Riwayat check-in berhasil diambil',
      data: [checkinsDB.todayCheckin],
    })
  }),

  http.get('*/api/v1/student/recap', async () => {
    await delay(500)

    return HttpResponse.json({
      message: 'Rekap berhasil diambil',
      data: {
        total_days: 1,
        completed_days: 0,
        completion_percentage: 50,
        habits: checkinsDB.todayCheckin.habits.map((habit) => ({
          id: habit.id,
          name: habit.name,
          completed_count: habit.completed ? 1 : 0,
          percentage: habit.completed ? 100 : 0,
        })),
      },
    })
  }),
]
