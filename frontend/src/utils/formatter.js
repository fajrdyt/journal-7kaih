const INDONESIA_LOCALE = 'id-ID'
const JAKARTA_TIME_ZONE = 'Asia/Jakarta'

function parseDate(value) {
  if (!value) {
    return null
  }

  const date = value instanceof Date
    ? value
    : new Date(value)

  if (Number.isNaN(date.getTime())) {
    return null
  }

  return date
}

export function formatDateJakarta(value, options = {}) {
  const date = parseDate(value)

  if (!date) {
    return '-'
  }

  return new Intl.DateTimeFormat(INDONESIA_LOCALE, {
    timeZone: JAKARTA_TIME_ZONE,
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    ...options,
  }).format(date)
}

export function formatDateTimeJakarta(value, options = {}) {
  const date = parseDate(value)

  if (!date) {
    return '-'
  }

  return new Intl.DateTimeFormat(INDONESIA_LOCALE, {
    timeZone: JAKARTA_TIME_ZONE,
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    ...options,
  }).format(date)
}

export function formatNumber(value) {
  const number = Number(value)

  if (!Number.isFinite(number)) {
    return '0'
  }

  return new Intl.NumberFormat(INDONESIA_LOCALE).format(number)
}

export function formatRoleLabel(role) {
  const rawRole =
    typeof role === 'object' && role !== null
      ? role.name ?? role.code ?? role.slug ?? ''
      : role

  const normalizedRole = String(rawRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const labels = {
    siswa: 'Siswa',
    student: 'Siswa',
    guru: 'Guru',
    teacher: 'Guru',
    orang_tua: 'Orang Tua',
    orangtua: 'Orang Tua',
    parent: 'Orang Tua',
    admin: 'Admin',
  }

  return labels[normalizedRole] ?? rawRole ?? '-'
}

export function normalizeBoolean(value) {
  return (
    value === true ||
    value === 1 ||
    value === '1' ||
    value === 'true'
  )
}

export function formatStatus(value) {
  return normalizeBoolean(value) ? 'Aktif' : 'Nonaktif'
}