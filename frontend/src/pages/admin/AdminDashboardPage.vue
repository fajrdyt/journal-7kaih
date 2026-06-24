<template>
  <div class="dashboard-page">
    <section class="welcome-card">
      <div class="welcome-content">
        <p class="welcome-label">Dashboard Admin</p>

        <h2>Halo, {{ adminName }}</h2>

        <p>
          Pantau pengguna, kelas, check-in, dan proses validasi
          dalam satu halaman.
        </p>
      </div>

      <button
        type="button"
        class="refresh-button"
        :disabled="loading"
        @click="loadSummary"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          :class="{ rotating: loading }"
        >
          <path d="M20 12a8 8 0 1 1-2.34-5.66" />
          <path d="M20 4v6h-6" />
        </svg>

        <span>
          {{ loading ? 'Memuat...' : 'Perbarui' }}
        </span>
      </button>
    </section>

    <div
      v-if="error"
      class="state-message error-message"
    >
      <div>
        <strong>Gagal memuat dashboard</strong>
        <p>{{ error }}</p>
      </div>

      <button type="button" @click="loadSummary">
        Coba lagi
      </button>
    </div>

    <template v-else>
      <section class="summary-section">
        <div class="section-heading">
          <div>
            <p>Ringkasan Data</p>
            <h3>Informasi utama sekolah</h3>
          </div>

          <span v-if="activityDate">
            {{ activityDate }}
          </span>
        </div>

        <div class="summary-grid">
          <article
            v-for="card in summaryCards"
            :key="card.key"
            class="summary-card"
          >
            <div
              class="summary-icon"
              :class="card.theme"
            >
              <svg
                v-if="card.icon === 'student'"
                viewBox="0 0 24 24"
                fill="none"
              >
                <circle cx="9" cy="8" r="3" />
                <path d="M3 20a6 6 0 0 1 12 0" />
                <path d="M16 5.5a3 3 0 0 1 0 5.5" />
                <path d="M17 14a5 5 0 0 1 4 5" />
              </svg>

              <svg
                v-else-if="card.icon === 'teacher'"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path d="M3 6.5 12 2l9 4.5-9 4.5-9-4.5Z" />
                <path d="M7 9v5.5c0 1.7 2.2 3.5 5 3.5s5-1.8 5-3.5V9" />
                <path d="M21 7v6" />
              </svg>

              <svg
                v-else-if="card.icon === 'parent'"
                viewBox="0 0 24 24"
                fill="none"
              >
                <circle cx="8" cy="8" r="3" />
                <circle cx="17" cy="9" r="2.5" />
                <path d="M2.5 20a5.5 5.5 0 0 1 11 0" />
                <path d="M14 20a4.5 4.5 0 0 1 8 0" />
              </svg>

              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
              >
                <path
                  d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z"
                />
                <path
                  d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v17h4.5A2.5 2.5 0 0 1 20 22V5.5Z"
                />
              </svg>
            </div>

            <div class="summary-copy">
              <span>{{ card.label }}</span>

              <strong v-if="!loading">
                {{ formatNumber(card.value) }}
              </strong>

              <span
                v-else
                class="skeleton-value"
              ></span>

              <small>{{ card.description }}</small>
            </div>
          </article>
        </div>
      </section>

      <section class="activity-layout">
        <div class="activity-panel">
          <div class="section-heading">
            <div>
              <p>Aktivitas Hari Ini</p>
              <h3>Progress check-in siswa</h3>
            </div>
          </div>

          <div
            v-if="loading"
            class="activity-loading"
          >
            <span></span>
            <span></span>
          </div>

          <div
            v-else
            class="progress-list"
          >
            <article class="progress-card">
              <div class="progress-head">
                <div>
                  <span>Siswa sudah check-in</span>
                  <strong>
                    {{
                      formatNumber(
                        todayActivity.students_checked_in_today,
                      )
                    }}
                    dari
                    {{
                      formatNumber(
                        users.total_students,
                      )
                    }}
                  </strong>
                </div>

                <div class="progress-percentage">
                  {{ checkinPercentage }}%
                </div>
              </div>

              <div class="progress-track">
                <span
                  :style="{
                    width: `${checkinPercentage}%`,
                  }"
                ></span>
              </div>

              <p>
                {{
                  formatNumber(
                    todayActivity.students_not_checked_in_today,
                  )
                }}
                siswa belum melakukan check-in hari ini.
              </p>
            </article>

            <article class="progress-card">
              <div class="progress-head">
                <div>
                  <span>Item kebiasaan selesai</span>
                  <strong>
                    {{
                      formatNumber(
                        todayActivity.today_done_items,
                      )
                    }}
                    dari
                    {{
                      formatNumber(
                        todayActivity.today_total_items,
                      )
                    }}
                  </strong>
                </div>

                <div class="progress-percentage green">
                  {{ itemPercentage }}%
                </div>
              </div>

              <div class="progress-track green-track">
                <span
                  :style="{
                    width: `${itemPercentage}%`,
                  }"
                ></span>
              </div>

              <p>
                Total penyelesaian item kebiasaan dari check-in
                hari ini.
              </p>
            </article>
          </div>
        </div>

        <div class="validation-panel">
          <div class="section-heading">
            <div>
              <p>Validasi</p>
              <h3>Status pemeriksaan</h3>
            </div>
          </div>

          <div class="validation-grid">
            <article class="validation-card">
              <span>Validasi Orang Tua</span>
              <strong>
                {{
                  loading
                    ? '—'
                    : formatNumber(
                        todayActivity.today_parent_validations,
                      )
                }}
              </strong>
            </article>

            <article class="validation-card">
              <span>Validasi Guru</span>
              <strong>
                {{
                  loading
                    ? '—'
                    : formatNumber(
                        todayActivity.today_teacher_validations,
                      )
                }}
              </strong>
            </article>

            <article class="validation-card highlighted">
              <span>Total Validasi</span>
              <strong>
                {{
                  loading
                    ? '—'
                    : formatNumber(
                        todayActivity.today_total_validations,
                      )
                }}
              </strong>
            </article>

            <article class="validation-card warning">
              <span>Menunggu Validasi</span>
              <strong>
                {{
                  loading
                    ? '—'
                    : formatNumber(
                        todayActivity.pending_validation_items,
                      )
                }}
              </strong>
            </article>
          </div>
        </div>
      </section>

      <section class="recent-section">
        <div class="section-heading recent-heading">
          <div>
            <p>Aktivitas Terbaru</p>
            <h3>Check-in siswa terbaru</h3>
          </div>

          <RouterLink
            to="/admin/users"
            class="section-link"
          >
            Lihat pengguna
          </RouterLink>
        </div>

        <div
          v-if="loading"
          class="recent-loading"
        >
          <div
            v-for="item in 3"
            :key="item"
            class="recent-skeleton"
          ></div>
        </div>

        <div
          v-else-if="!recentCheckins.length"
          class="empty-state"
        >
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 6h16v14H4z" />
              <path d="M8 3v6M16 3v6M4 10h16" />
            </svg>
          </div>

          <strong>Belum ada check-in terbaru</strong>

          <p>
            Aktivitas check-in siswa akan muncul di sini.
          </p>
        </div>

        <div
          v-else
          class="recent-list"
        >
          <article
            v-for="checkin in recentCheckins"
            :key="checkin.id"
            class="recent-item"
          >
            <div class="student-avatar">
              {{ getInitials(checkin.student_name) }}
            </div>

            <div class="recent-copy">
              <strong>
                {{ checkin.student_name || 'Siswa' }}
              </strong>

              <span>
                {{
                  checkin.class?.name
                    ? `Kelas ${checkin.class.name}`
                    : 'Kelas belum tersedia'
                }}
              </span>
            </div>

            <time>
              {{ formatDateJakarta(checkin.checkin_date) }}
            </time>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

import { userApi } from '@/api/user'
import { useAuthStore } from '@/stores/authStore'
import {
  formatDateJakarta,
  formatNumber,
} from '@/utils/formatter'

const authStore = useAuthStore()

const loading = ref(true)
const error = ref('')
const recentCheckins = ref([])

const users = reactive({
  total_students: 0,
  total_teachers: 0,
  total_parents: 0,
  total_admins: 0,
  active_students: 0,
})

const classes = reactive({
  total_classes: 0,
  active_classes: 0,
})

const todayActivity = reactive({
  date: '',
  today_checkins: 0,
  students_checked_in_today: 0,
  students_not_checked_in_today: 0,
  today_total_items: 0,
  today_done_items: 0,
  today_parent_validations: 0,
  today_teacher_validations: 0,
  today_total_validations: 0,
  pending_validation_items: 0,
})

const adminName = computed(() => {
  return (
    authStore.user?.display_name ??
    authStore.user?.full_name ??
    authStore.user?.name ??
    authStore.user?.username ??
    'Admin'
  )
})

const activityDate = computed(() => {
  if (!todayActivity.date) {
    return ''
  }

  return formatDateJakarta(todayActivity.date)
})

const summaryCards = computed(() => [
  {
    key: 'students',
    label: 'Total Siswa',
    value: users.total_students,
    description: `${formatNumber(
      users.active_students,
    )} siswa aktif`,
    icon: 'student',
    theme: 'blue',
  },
  {
    key: 'teachers',
    label: 'Total Guru',
    value: users.total_teachers,
    description: 'Akun guru terdaftar',
    icon: 'teacher',
    theme: 'purple',
  },
  {
    key: 'parents',
    label: 'Total Orang Tua',
    value: users.total_parents,
    description: 'Akun orang tua terdaftar',
    icon: 'parent',
    theme: 'orange',
  },
  {
    key: 'classes',
    label: 'Total Kelas',
    value: classes.total_classes,
    description: `${formatNumber(
      classes.active_classes,
    )} kelas aktif`,
    icon: 'class',
    theme: 'green',
  },
])

const checkinPercentage = computed(() => {
  return calculatePercentage(
    todayActivity.students_checked_in_today,
    users.total_students,
  )
})

const itemPercentage = computed(() => {
  return calculatePercentage(
    todayActivity.today_done_items,
    todayActivity.today_total_items,
  )
})

onMounted(loadSummary)

async function loadSummary() {
  try {
    loading.value = true
    error.value = ''

    const response = await userApi.getDashboardSummary()
    const data = response.data?.data ?? {}

    Object.assign(users, data.users ?? {})
    Object.assign(classes, data.classes ?? {})
    Object.assign(
      todayActivity,
      data.today_activity ?? {},
    )

    recentCheckins.value = Array.isArray(
      data.recent_checkins,
    )
      ? data.recent_checkins
      : []
  } catch (err) {
    error.value =
      err.response?.data?.message ??
      'Terjadi kesalahan saat mengambil ringkasan admin.'
  } finally {
    loading.value = false
  }
}

function calculatePercentage(value, total) {
  const currentValue = Number(value)
  const currentTotal = Number(total)

  if (
    !Number.isFinite(currentValue) ||
    !Number.isFinite(currentTotal) ||
    currentTotal <= 0
  ) {
    return 0
  }

  return Math.min(
    100,
    Math.max(
      0,
      Math.round(
        (currentValue / currentTotal) * 100,
      ),
    ),
  )
}

function getInitials(name) {
  const words = String(name ?? '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!words.length) {
    return 'S'
  }

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words.at(-1)[0]}`.toUpperCase()
}
</script>

<style scoped>
.dashboard-page {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 24px;
}

.welcome-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  overflow: hidden;
  padding: 30px 32px;
  border-radius: 24px;
  background:
    radial-gradient(
      circle at 88% 20%,
      rgba(255, 255, 255, 0.24),
      transparent 30%
    ),
    linear-gradient(135deg, #1597e5, #58bdf8);
  color: #ffffff;
  box-shadow: 0 18px 40px rgba(32, 156, 238, 0.2);
}

.welcome-content {
  min-width: 0;
}

.welcome-label {
  margin: 0 0 8px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.82;
}

.welcome-content h2 {
  margin: 0;
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 900;
  letter-spacing: -0.035em;
}

.welcome-content p:last-child {
  max-width: 580px;
  margin: 9px 0 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 14px;
  line-height: 1.7;
}

.refresh-button {
  display: inline-flex;
  min-height: 42px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 16px;
  border: 1px solid rgba(255, 255, 255, 0.34);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.17);
  color: #ffffff;
  font: inherit;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  backdrop-filter: blur(10px);
  transition:
    background 0.18s ease,
    transform 0.18s ease;
}

.refresh-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, 0.25);
}

.refresh-button:disabled {
  cursor: not-allowed;
  opacity: 0.75;
}

.refresh-button svg {
  width: 17px;
  height: 17px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.rotating {
  animation: rotate 0.8s linear infinite;
}

.summary-section,
.activity-panel,
.validation-panel,
.recent-section {
  min-width: 0;
}

.section-heading {
  display: flex;
  min-width: 0;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

.section-heading p {
  margin: 0 0 4px;
  color: #168ad3;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.section-heading h3 {
  margin: 0;
  color: #172033;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: -0.025em;
}

.section-heading > span {
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.summary-card {
  display: flex;
  min-width: 0;
  align-items: flex-start;
  gap: 15px;
  padding: 20px;
  border: 1px solid #e7edf5;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.045);
}

.summary-icon {
  display: grid;
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 15px;
}

.summary-icon svg {
  width: 22px;
  height: 22px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.summary-icon.blue {
  background: #eaf6ff;
  color: #168ad3;
}

.summary-icon.purple {
  background: #f2edff;
  color: #7554d8;
}

.summary-icon.orange {
  background: #fff4e8;
  color: #e4822c;
}

.summary-icon.green {
  background: #e9f9ef;
  color: #259c5f;
}

.summary-copy {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.summary-copy > span:first-child {
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.summary-copy strong {
  margin-top: 6px;
  color: #0f172a;
  font-size: 27px;
  font-weight: 900;
  line-height: 1;
  letter-spacing: -0.04em;
}

.summary-copy small {
  margin-top: 8px;
  overflow: hidden;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.skeleton-value {
  width: 72px;
  height: 28px;
  margin-top: 6px;
  border-radius: 8px;
  background: #edf2f7;
  animation: pulse 1.2s ease-in-out infinite;
}

.activity-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.75fr);
  gap: 18px;
}

.activity-panel,
.validation-panel,
.recent-section {
  padding: 22px;
  border: 1px solid #e7edf5;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.045);
}

.progress-list {
  display: grid;
  gap: 14px;
}

.progress-card {
  padding: 18px;
  border: 1px solid #e8eef5;
  border-radius: 17px;
  background: #fbfdff;
}

.progress-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
}

.progress-head span {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.progress-head strong {
  display: block;
  margin-top: 5px;
  color: #172033;
  font-size: 18px;
  font-weight: 900;
}

.progress-percentage {
  flex-shrink: 0;
  color: #168ad3;
  font-size: 18px;
  font-weight: 900;
}

.progress-percentage.green {
  color: #259c5f;
}

.progress-track {
  height: 9px;
  margin-top: 15px;
  overflow: hidden;
  border-radius: 999px;
  background: #e6f2fb;
}

.progress-track span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #168ad3, #58bdf8);
  transition: width 0.4s ease;
}

.green-track {
  background: #e6f6ec;
}

.green-track span {
  background: linear-gradient(90deg, #259c5f, #5ecb8c);
}

.progress-card p {
  margin: 11px 0 0;
  color: #94a3b8;
  font-size: 11.5px;
  line-height: 1.55;
}

.validation-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.validation-card {
  display: flex;
  min-height: 104px;
  flex-direction: column;
  justify-content: space-between;
  padding: 16px;
  border: 1px solid #e7edf5;
  border-radius: 16px;
  background: #fbfdff;
}

.validation-card span {
  color: #64748b;
  font-size: 11.5px;
  font-weight: 700;
  line-height: 1.4;
}

.validation-card strong {
  color: #172033;
  font-size: 25px;
  font-weight: 900;
}

.validation-card.highlighted {
  border-color: #bae6fd;
  background: #f0f9ff;
}

.validation-card.highlighted strong {
  color: #168ad3;
}

.validation-card.warning {
  border-color: #fde7bb;
  background: #fffaf0;
}

.validation-card.warning strong {
  color: #d97706;
}

.recent-heading {
  align-items: center;
}

.section-link {
  color: #168ad3;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
}

.section-link:hover {
  text-decoration: underline;
}

.recent-list {
  display: grid;
}

.recent-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 13px;
  padding: 14px 4px;
  border-bottom: 1px solid #edf2f7;
}

.recent-item:last-child {
  border-bottom: 0;
}

.student-avatar {
  display: grid;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 13px;
  background: #eaf6ff;
  color: #168ad3;
  font-size: 11px;
  font-weight: 900;
}

.recent-copy {
  min-width: 0;
}

.recent-copy strong,
.recent-copy span {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.recent-copy strong {
  color: #1e293b;
  font-size: 13px;
  font-weight: 800;
}

.recent-copy span {
  margin-top: 4px;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
}

.recent-item time {
  color: #64748b;
  font-size: 11.5px;
  font-weight: 700;
  white-space: nowrap;
}

.state-message {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 17px 18px;
  border-radius: 16px;
}

.error-message {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.state-message strong {
  display: block;
  font-size: 13px;
}

.state-message p {
  margin: 4px 0 0;
  font-size: 12px;
}

.state-message button {
  min-height: 38px;
  flex-shrink: 0;
  padding: 0 14px;
  border: 0;
  border-radius: 10px;
  background: #be123c;
  color: #ffffff;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.empty-state {
  display: grid;
  min-height: 220px;
  place-items: center;
  align-content: center;
  text-align: center;
}

.empty-icon {
  display: grid;
  width: 50px;
  height: 50px;
  place-items: center;
  border-radius: 16px;
  background: #f1f5f9;
  color: #94a3b8;
}

.empty-icon svg {
  width: 23px;
  height: 23px;
  stroke: currentColor;
  stroke-width: 1.7;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.empty-state strong {
  margin-top: 12px;
  color: #334155;
  font-size: 13px;
}

.empty-state p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 11.5px;
}

.activity-loading,
.recent-loading {
  display: grid;
  gap: 14px;
}

.activity-loading span,
.recent-skeleton {
  height: 120px;
  border-radius: 17px;
  background: #edf2f7;
  animation: pulse 1.2s ease-in-out infinite;
}

.recent-skeleton {
  height: 66px;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 0.55;
  }

  50% {
    opacity: 1;
  }
}

@keyframes rotate {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1180px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .activity-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .dashboard-page {
    gap: 18px;
  }

  .welcome-card {
    align-items: flex-start;
    padding: 24px 20px;
  }

  .welcome-content p:last-child {
    font-size: 13px;
  }

  .refresh-button span {
    display: none;
  }

  .refresh-button {
    width: 42px;
    padding: 0;
  }

  .summary-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .summary-card {
    align-items: center;
    padding: 17px;
  }

  .activity-panel,
  .validation-panel,
  .recent-section {
    padding: 17px;
    border-radius: 18px;
  }

  .validation-grid {
    grid-template-columns: 1fr 1fr;
  }

  .recent-item {
    grid-template-columns: auto minmax(0, 1fr);
  }

  .recent-item time {
    grid-column: 2;
  }
}

@media (max-width: 430px) {
  .welcome-card {
    gap: 12px;
    padding: 21px 17px;
    border-radius: 19px;
  }

  .welcome-content h2 {
    font-size: 21px;
  }

  .welcome-content p:last-child {
    display: none;
  }

  .section-heading {
    align-items: flex-start;
  }

  .section-heading h3 {
    font-size: 16px;
  }

  .section-heading > span {
    max-width: 110px;
    text-align: right;
  }

  .validation-grid {
    grid-template-columns: 1fr;
  }

  .progress-head strong {
    font-size: 16px;
  }

  .state-message {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>