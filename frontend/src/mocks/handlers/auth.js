import { http, HttpResponse } from 'msw'
import { users } from '../data/users'
import { delay } from '../utils/delay'

export const authHandlers = [
  http.post('*/api/v1/auth/login', async ({ request }) => {
    await delay()

    const body = await request.json()

    const user = users.find(
      (item) =>
        (item.email === body.identifier || item.name === body.identifier) &&
        item.password === body.password,
    )

    if (!user) {
      return HttpResponse.json({ message: 'Invalid credentials' }, { status: 401 })
    }

    return HttpResponse.json({
      access_token: 'mock-token-123',
      token_type: 'Bearer',
      user: {
        id: user.id,
        name: user.name,
        full_name: user.name,
        email: user.email,
        role: user.role,
      },
    })
  }),

  http.get('*/api/v1/auth/me', async () => {
    await delay()

    const user = users[0]

    return HttpResponse.json({
      id: user.id,
      name: user.name,
      full_name: user.name,
      email: user.email,
      role: user.role,
    })
  }),

  http.post('*/api/v1/auth/logout', async () => {
    await delay()

    return HttpResponse.json({
      message: 'Logout success',
    })
  }),
]
