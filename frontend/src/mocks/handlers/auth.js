import { http } from 'msw'

import { users } from '../data/users'

import { delay } from '../utils/delay'
import { success, error } from '../utils/response'

export const authHandlers = [

  http.post('http://127.0.0.1:8000/api/login', async ({ request }) => {

    await delay()

    const body = await request.json()

    const user = users.find(
      item =>
        item.email === body.email &&
        item.password === body.password
    )

    if (!user) {
      return error('Invalid credentials', 401)
    }

    return success({
      token: 'mock-token-123',

      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      },
    })
  }),

  http.get('http://127.0.0.1:8000/api/me', async () => {

    await delay()

    const user = users[0]

    return success({
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.role,
    })
  }),

  http.post('http://127.0.0.1:8000/api/logout', async () => {

    await delay()

    return success({
      message: 'Logout success',
    })
  }),

]