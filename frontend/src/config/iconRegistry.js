// src/config/iconRegistry.js
import {
  Squares2X2Icon,
  CheckCircleIcon,
  ClockIcon,
  ChartBarIcon,
  Cog6ToothIcon,
  UsersIcon,
  AcademicCapIcon,
  UserGroupIcon,
  ClipboardDocumentCheckIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const iconMap = {
  'squares-2x2': Squares2X2Icon,
  'check-circle': CheckCircleIcon,
  'clock': ClockIcon,
  'chart-bar': ChartBarIcon,
  'cog-6-tooth': Cog6ToothIcon,
  'users': UsersIcon,
  'academic-cap': AcademicCapIcon,
  'user-group': UserGroupIcon,
  'clipboard-document-check': ClipboardDocumentCheckIcon,
  'shield-check': ShieldCheckIcon,
}

export function resolveIcon(name) {
  return iconMap[name] ?? Squares2X2Icon
}
