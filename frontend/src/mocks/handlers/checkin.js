// src/mocks/handlers/checkin.js

import { http, HttpResponse } from 'msw'
import { checkinsDB } from '../data/checkins'
import { delay } from '../utils/delay'

export const checkinHandlers = [
  http.get('/api/checkins/today', async () => {
    await delay(500)

    return HttpResponse.json({
      success: true,
      message: 'Data check-in berhasil diambil',
      data: checkinsDB.todayCheckin,
    })
  }),

  http.post('/api/checkins', async ({ request }) => {
    await delay(500)

    const body = await request.json()

    if (!body.habits) {
      return HttpResponse.json(
        {
          success: false,
          message: 'Habits wajib diisi',
        },
        {
          status: 422,
        },
      )
    }

    checkinsDB.todayCheckin = {
      habits: body.habits,
      notes: body.notes ?? '',
    }

    return HttpResponse.json(
      {
        success: true,
        message: 'Check-in berhasil disimpan',
      },
      {
        status: 201,
      },
    )
  }),

  http.get('/api/checkins/history', async () => {
    await delay(500)

    return HttpResponse.json({
      success: true,
      message: 'Riwayat check-in berhasil diambil',
      data: [],
    })
  }),
]