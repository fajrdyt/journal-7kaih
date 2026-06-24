<template>
  <div class="page">

    <!-- ── Greeting Banner ────────────────────────────────── -->
    <div class="greeting">
      <div class="greeting-text">
        <p class="greeting-hi">Hallo, {{ adminName }}</p>
        <p class="greeting-sub">Selamat Datang</p>
      </div>
      <div class="greeting-actions">
        <button class="icon-btn" aria-label="Notifikasi">
          <IconBell />
        </button>
        <button class="icon-btn" aria-label="Opsi">
          <IconDots />
        </button>
        <button class="avatar-btn" aria-label="Profil" @click="$router.push('/profile')">
          <IconUser />
        </button>
      </div>
    </div>

    <!-- ── Stat Cards ─────────────────────────────────────── -->
    <div class="stats-grid">
      <div
        v-for="stat in stats"
        :key="stat.key"
        class="stat-card"
        :class="{ loading: statsLoading }"
      >
        <span class="stat-label">{{ stat.label }}</span>
        <span class="stat-value">
          <template v-if="statsLoading">—</template>
          <template v-else>{{ stat.value }}</template>
        </span>
      </div>
    </div>

    <!-- ── Activity ───────────────────────────────────────── -->
    <section class="activity">
      <h2 class="section-title">Activity</h2>

      <div class="activity-grid">

        <!-- Bar Chart -->
        <div class="chart-card">
          <div class="chart-header">
            <span class="chart-title">Grafik Journal ter Validasi</span>
            <button class="period-btn" @click="togglePeriod">
              {{ activePeriod }}
              <IconChevron />
            </button>
          </div>

          <div class="chart-body">
            <!-- Y-axis -->
            <div class="y-axis">
              <span v-for="tick in yTicks" :key="tick">{{ tick }}</span>
            </div>

            <!-- Bars -->
            <div class="bars-area">
              <div
                v-for="bar in chartData"
                :key="bar.day"
                class="bar-col"
              >
                <div class="bar-wrapper">
                  <div
                    class="bar"
                    :style="{ height: toPercent(bar.value) }"
                    :title="`${bar.day}: ${bar.value}`"
                  ></div>
                </div>
                <span class="bar-label">{{ bar.day }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Log -->
        <div class="log-card">
          <h3 class="log-title">Log Aktivitas Terbaru</h3>

          <p v-if="logLoading" class="log-empty">Memuat...</p>
          <p v-else-if="!activityLog.length" class="log-empty">Belum ada aktivitas.</p>

          <ul v-else class="log-list">
            <li
              v-for="log in activityLog"
              :key="log.id"
              class="log-item"
            >
              <span class="log-dot"></span>
              <div>
                <p class="log-text">{{ log.description }}</p>
                <span class="log-time">{{ log.time }}</span>
              </div>
            </li>
          </ul>
        </div>

      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'

// ── Icon Components (inline SVG) ──────────────────────────
// Taruh di components/common/ jika dipakai di banyak tempat
const IconBell = {
  template: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
  </svg>`
}
const IconDots = {
  template: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
    <circle cx="12" cy="5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="19" r="1.2"/>
  </svg>`
}
const IconUser = {
  template: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
    <circle cx="12" cy="7" r="4"/>
  </svg>`
}
const IconChevron = {
  template: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
    <polyline points="6 9 12 15 18 9"/>
  </svg>`
}

// ── Auth ──────────────────────────────────────────────────
const auth = useAuthStore()
const adminName = computed(() => auth.user?.name ?? 'Admin')

// ── Stat Cards ────────────────────────────────────────────
const statsLoading = ref(false)

const stats = ref([
  { key: 'siswa',   label: 'Total Siswa',     value: 0 },
  { key: 'guru',    label: 'Total Guru',       value: 0 },
  { key: 'kelas',   label: 'Total Kelas',      value: 0 },
  { key: 'jurnal',  label: 'Jurnal Diajukan',  value: 0 },
])

async function fetchStats() {
  statsLoading.value = true
  try {
    // TODO: ganti dengan API call nyata, contoh:
    // const { data } = await import('@/api/user.js').then(m => m.getAdminStats())
    // stats.value.find(s => s.key === 'siswa').value  = data.total_siswa
    // stats.value.find(s => s.key === 'guru').value   = data.total_guru
    // stats.value.find(s => s.key === 'kelas').value  = data.total_kelas
    // stats.value.find(s => s.key === 'jurnal').value = data.jurnal_diajukan

    // Data dummy sementara
    await new Promise(r => setTimeout(r, 400))
    stats.value.find(s => s.key === 'siswa').value  = 800
    stats.value.find(s => s.key === 'guru').value   = 115
    stats.value.find(s => s.key === 'kelas').value  = 15
    stats.value.find(s => s.key === 'jurnal').value = 800
  } finally {
    statsLoading.value = false
  }
}

// ── Chart ─────────────────────────────────────────────────
const CHART_MAX   = 800
const yTicks      = [800, 600, 400, 200, 50, 0]
const activePeriod = ref('Minggu ini')
const periods      = ['Minggu ini', 'Bulan ini', '3 Bulan']

function togglePeriod() {
  const idx = periods.indexOf(activePeriod.value)
  activePeriod.value = periods[(idx + 1) % periods.length]
  // TODO: fetch chart data baru berdasarkan activePeriod.value
}

const chartData = ref([
  { day: 'Senin',  value: 790 },
  { day: 'Selasa', value: 730 },
  { day: 'Rabu',   value: 745 },
  { day: 'Kamis',  value: 720 },
  { day: "Jum'at", value: 780 },
  { day: 'Sabtu',  value: 760 },
])

function toPercent(value) {
  return `${Math.round((value / CHART_MAX) * 100)}%`
}

// ── Activity Log ──────────────────────────────────────────
const logLoading  = ref(false)
const activityLog = ref([])

async function fetchActivityLog() {
  logLoading.value = true
  try {
    // TODO: ganti dengan API call nyata, contoh:
    // const { data } = await import('@/api/user.js').then(m => m.getActivityLog())
    // activityLog.value = data

    // Data dummy sementara
    await new Promise(r => setTimeout(r, 400))
    activityLog.value = [
      { id: 1, description: 'Data Guru di Perbarui ( Radit )',          time: '2 menit yang lalu'  },
      { id: 2, description: 'Siswa Telah ditambahkan Kelas ( X-A )',    time: '1 Jam yang lalu'    },
      { id: 3, description: 'Pengaturan Sistem Telah Diubah',           time: '3 Hari yang lalu'   },
      { id: 4, description: 'Validasi Semua Selesai Tervalidasi',       time: '5 Jam yang lalu'    },
    ]
  } finally {
    logLoading.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchStats()
  fetchActivityLog()
})
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────────── */
.page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-height: 100%;
  padding-bottom: 40px;
}

/* ── Greeting ──────────────────────────────────────────── */
.greeting {
  background: #42b0f5;
  border-radius: 0 0 20px 20px;
  padding: 28px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.greeting-hi,
.greeting-sub {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #fff;
  line-height: 1.5;
}

.greeting-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.icon-btn {
  background: rgba(255,255,255,0.2);
  border: none;
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  cursor: pointer;
  transition: background 0.18s;
}
.icon-btn:hover { background: rgba(255,255,255,0.32); }

.avatar-btn {
  background: rgba(255,255,255,0.25);
  border: 2px solid rgba(255,255,255,0.5);
  border-radius: 50%;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  cursor: pointer;
  transition: background 0.18s;
}
.avatar-btn:hover { background: rgba(255,255,255,0.38); }

/* ── Stats ─────────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 0 24px;
}

.stat-card {
  background: #fff;
  border-radius: 16px;
  padding: 20px 24px 22px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: 0 4px 16px rgba(15,23,42,0.06);
  transition: opacity 0.2s;
}
.stat-card.loading { opacity: 0.5; }

.stat-label {
  font-size: 13.5px;
  font-weight: 600;
  color: #64748b;
}

.stat-value {
  font-size: 34px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.03em;
  line-height: 1;
}

/* ── Activity ───────────────────────────────────────────── */
.activity {
  padding: 0 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.section-title {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.activity-grid {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 16px;
  align-items: start;
}

/* ── Chart ─────────────────────────────────────────────── */
.chart-card {
  background: #fff;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 4px 16px rgba(15,23,42,0.06);
}

.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.chart-title {
  font-size: 14.5px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.4;
  max-width: 160px;
}

.period-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 6px 12px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.18s;
}
.period-btn:hover { background: #e2e8f0; }

.chart-body {
  display: flex;
  gap: 8px;
  height: 220px;
}

.y-axis {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
  padding-bottom: 26px;
  min-width: 32px;
}
.y-axis span {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
}

.bars-area {
  flex: 1;
  display: flex;
  align-items: flex-end;
  gap: 10px;
  border-left: 1px solid #e2e8f0;
  padding-left: 10px;
}

.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  height: 100%;
}

.bar-wrapper {
  flex: 1;
  width: 100%;
  display: flex;
  align-items: flex-end;
}

.bar {
  width: 100%;
  background: #42b0f5;
  border-radius: 6px 6px 2px 2px;
  min-height: 4px;
  cursor: pointer;
  transition: opacity 0.18s, height 0.4s ease;
}
.bar:hover { opacity: 0.75; }

.bar-label {
  font-size: 11.5px;
  color: #64748b;
  font-weight: 500;
  white-space: nowrap;
}

/* ── Log ───────────────────────────────────────────────── */
.log-card {
  background: #fff;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 4px 16px rgba(15,23,42,0.06);
}

.log-title {
  margin: 0 0 18px;
  font-size: 14.5px;
  font-weight: 700;
  color: #1e293b;
}

.log-empty {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.log-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.log-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.log-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #42b0f5;
  margin-top: 5px;
  flex-shrink: 0;
}

.log-text {
  margin: 0 0 3px;
  font-size: 13.5px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.45;
}

.log-time {
  font-size: 12px;
  color: #94a3b8;
}

/* ── Responsive ────────────────────────────────────────── */
@media (max-width: 960px) {
  .activity-grid { grid-template-columns: 1fr; }
}

@media (max-width: 700px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .greeting, .activity { padding-left: 16px; padding-right: 16px; }
  .stats-grid { padding: 0 16px; }
}

@media (max-width: 420px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>