import { HttpResponse } from 'msw'

export function success(data, status = 200) {

  return HttpResponse.json(data, {
    status,
  })
}

export function error(message, status = 400) {

  return HttpResponse.json(
    { message },
    { status }
  )
}