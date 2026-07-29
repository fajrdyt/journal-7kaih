import {
  SunIcon,
  StarIcon,
  BoltIcon,
  CakeIcon,
  BookOpenIcon,
  UserGroupIcon,
  MoonIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const habitIconMap = {
  BANGUN_PAGI: SunIcon,
  BERIBADAH: StarIcon,
  BEROLAHRAGA: BoltIcon,
  MAKAN_SEHAT_BERGIZI: CakeIcon,
  GEMAR_BELAJAR: BookOpenIcon,
  BERMASYARAKAT: UserGroupIcon,
  TIDUR_CEPAT: MoonIcon,
}

export function resolveHabitIcon(code) {
  const normalizedCode = String(code ?? '')
    .trim()
    .toUpperCase()
    .replace(/[\s-]+/g, '_')

  return habitIconMap[normalizedCode] ?? CheckCircleIcon
}