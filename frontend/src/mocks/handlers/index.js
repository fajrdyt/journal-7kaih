import { authHandlers } from './auth'
import { checkinHandlers } from './checkin'
import { validationHandlers } from './validation'
import { recapHandlers } from './recap'

export const handlers = [

  ...authHandlers,
  ...checkinHandlers,
  ...validationHandlers,
  ...recapHandlers,

]