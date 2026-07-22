// src/config/navConfig.js
//
// Sumber kebenaran tunggal untuk menu tiap role.
// Path dikonfirmasi dari authStore.js (fungsi dashboardByRole) dan
// settingsPath yang sebelumnya ada di AppSidebar.vue.
//
// Catatan: guru & admin memakai '/settings' (generic, tanpa prefix role),
// sedangkan siswa & orang_tua punya '/student/settings' dan '/parent/settings'
// sendiri — ini bukan salah ketik, memang begitu di kode aslinya.
 
export const navConfig = {
  siswa: {
    items: [
      { label: 'Beranda', to: '/student/dashboard', icon: 'squares-2x2', exact: true },
      { label: 'Check-in', to: '/student/checkin', icon: 'check-circle' },
      { label: 'Riwayat', to: '/student/history', icon: 'clock' },
      { label: 'Rekap', to: '/student/recap', icon: 'chart-bar' },
    ],
    settings: { label: 'Pengaturan', to: '/student/settings', icon: 'cog-6-tooth' },
  },
 
  guru: {
    items: [
      { label: 'Beranda', to: '/teacher/dashboard', icon: 'squares-2x2', exact: true },
      { label: 'Monitoring & Validasi', mobileLabel: 'Monitoring', to: '/teacher/monitoring', icon: 'users' },
      { label: 'Rekap Kelas', mobileLabel: 'Rekap', to: '/teacher/recap', icon: 'chart-bar' },
    ],
    settings: { label: 'Pengaturan', to: '/settings', icon: 'cog-6-tooth' },
  },
 
  orang_tua: {
    items: [
      { label: 'Beranda', to: '/parent/dashboard', icon: 'squares-2x2', exact: true },
      { label: 'Validasi', to: '/parent/validation', icon: 'shield-check' },
      { label: 'Riwayat', to: '/parent/history', icon: 'clock' },
      { label: 'Rekap', to: '/parent/recap', icon: 'chart-bar' },
    ],
    settings: { label: 'Pengaturan', to: '/parent/settings', icon: 'cog-6-tooth' },
  },
 
  admin: {
    items: [
      { label: 'Dashboard', to: '/admin/dashboard', icon: 'squares-2x2', exact: true },
      { label: 'Pengguna', to: '/admin/users', icon: 'users' },
      { label: 'Kelas', to: '/admin/classes', icon: 'academic-cap' },
      { label: 'Relasi Orang Tua', mobileLabel: 'Relasi', to: '/admin/relations', icon: 'user-group' },
      { label: 'Daftar Kebiasaan', mobileLabel: 'Kebiasaan', to: '/admin/habits', icon: 'clipboard-document-check' },
    ],
    settings: { label: 'Pengaturan', to: '/settings', icon: 'cog-6-tooth' },
  },
}
 
export const brandInfo = {
  name: 'Jurnal 7KAIH',
  subtitle: 'SMA N 1 Mirit',
}
