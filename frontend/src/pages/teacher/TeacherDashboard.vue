<template>
  <section class="teacher-page">
    <div class="welcome-card">
      <div class="welcome-copy">
        <p class="eyebrow">Dashboard Guru</p>

        <h1>Selamat Datang, {{ teacherName }}!</h1>

        <p>
          Pantau pengisian jurnal siswa dan proses validasi dari kelas
          yang Anda ampu.
        </p>
      </div>

      <div class="welcome-actions">
        <button
          type="button"
          class="secondary-button"
          :disabled="loading"
          @click="loadDashboard"
        >
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M20 12a8 8 0 1 1-2.3-5.7" />
            <path d="M20 4v6h-6" />
          </svg>

          {{ loading ? 'Memuat...' : 'Muat Ulang' }}
        </button>

        <button
          type="button"
          class="primary-button"
          @click="router.push('/teacher/monitoring')"
        >
          Buka Monitoring

          <svg viewBox="0 0 24 24" fill="none">
            <path d="M5 12h14" />
            <path d="m14 7 5 5-5 5" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="error" class="alert error-alert">
      {{ error }}
    </div>

    <div class="stats-grid">
      <article class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z" />
            <path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v17h4.5A2.5 2.5 0 0 1 20 22V5.5Z" />
          </svg>
        </div>

        <div>
          <span class="stat-label">Kelas Diampu</span>
          <strong class="stat-value">
            {{ loading ? '—' : totalClasses }}
          </strong>
          <small>Kelas aktif</small>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-icon cyan">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 20a5.5 5.5 0 0 1 11 0" />
            <circle cx="17" cy="9" r="2.5" />
            <path d="M15.5 15a4.5 4.5 0 0 1 5 5" />
          </svg>
        </div>

        <div>
          <span class="stat-label">Total Siswa</span>
          <strong class="stat-value">
            {{ loading ? '—' : totalStudents }}
          </strong>
          <small>Dari seluruh kelas</small>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16.5 8" />
          </svg>
        </div>

        <div>
          <span class="stat-label">Sudah Check-in</span>
          <strong class="stat-value">
            {{ loading ? '—' : checkedInStudents }}
          </strong>
          <small>{{ checkinRate }}% hari ini</small>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5" />
            <path d="M12 16h.01" />
          </svg>
        </div>

        <div>
          <span class="stat-label">Menunggu Validasi</span>
          <strong class="stat-value">
            {{ loading ? '—' : pendingValidations }}
          </strong>
          <small>Item kebiasaan</small>
        </div>
      </article>
    </div>

    <section class="classes-section">
      <div class="section-header">
        <div>
          <p class="section-eyebrow">Kelas Saya</p>
          <h2>Monitoring Hari Ini</h2>
          <p>{{ formattedToday }}</p>
        </div>

        <button
          type="button"
          class="text-button"
          @click="router.push('/teacher/monitoring')"
        >
          Lihat Semua
          <span>→</span>
        </button>
      </div>

      <div v-if="loading" class="state-card">
        <div class="loader"></div>
        <span>Memuat data kelas...</span>
      </div>

      <div v-else-if="classes.length === 0" class="state-card empty-state">
        <div class="empty-icon">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z" />
            <path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v17h4.5A2.5 2.5 0 0 1 20 22V5.5Z" />
          </svg>
        </div>

        <strong>Belum ada kelas yang diampu</strong>

        <p>
          Hubungi admin untuk menetapkan akun guru sebagai wali kelas.
        </p>
      </div>

      <div v-else class="class-grid">
        <article
          v-for="classItem in classes"
          :key="classItem.id"
          class="class-card"
          tabindex="0"
          @click="openClassMonitoring(classItem)"
          @keydown.enter="openClassMonitoring(classItem)"
        >
          <div class="class-card-top">
            <div class="class-identity">
              <span class="class-badge">
                {{ getClassInitial(classItem) }}
              </span>

              <div>
                <h3>{{ classItem.name }}</h3>
                <p>{{ classItem.grade_level || 'Tingkat belum tersedia' }}</p>
              </div>
            </div>

            <button
              type="button"
              class="arrow-button"
              aria-label="Buka monitoring kelas"
              @click.stop="openClassMonitoring(classItem)"
            >
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M5 12h14" />
                <path d="m14 7 5 5-5 5" />
              </svg>
            </button>
          </div>

          <div class="class-numbers">
            <div>
              <span>Total Siswa</span>
              <strong>{{ classItem.summary.total_students }}</strong>
            </div>

            <div>
              <span>Sudah Check-in</span>
              <strong>{{ classItem.summary.checked_in_count }}</strong>
            </div>

            <div>
              <span>Belum Check-in</span>
              <strong>{{ classItem.summary.not_checked_in_count }}</strong>
            </div>
          </div>

          <div class="progress-section">
            <div class="progress-header">
              <span>Progress check-in</span>
              <strong>{{ getClassProgress(classItem) }}%</strong>
            </div>

            <div class="progress-track">
              <div
                class="progress-fill"
                :style="{ width: `${getClassProgress(classItem)}%` }"
              ></div>
            </div>
          </div>

          <div class="class-footer">
            <span
              :class="[
                'validation-status',
                classItem.summary.pending_validation_items > 0
                  ? 'pending'
                  : 'complete',
              ]"
            >
              <span class="status-dot"></span>

              <template
                v-if="classItem.summary.pending_validation_items > 0"
              >
                {{ classItem.summary.pending_validation_items }}
                item menunggu validasi
              </template>

              <template v-else>
                Tidak ada validasi tertunda
              </template>
            </span>
          </div>
        </article>
      </div>
    </section>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { recapApi } from '@/api/recap'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const auth = useAuthStore()

const loading = ref(true)
const error = ref('')
const classes = ref([])

const teacherName = computed(() => {
  return (
    auth.user?.full_name ||
    auth.user?.name ||
    auth.user?.username ||
    'Guru'
  )
})

const today = getLocalDate()

const formattedToday = new Intl.DateTimeFormat('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date())

const totalClasses = computed(() => classes.value.length)

const totalStudents = computed(() => {
  return classes.value.reduce(
    (total, item) => total + item.summary.total_students,
    0,
  )
})

const checkedInStudents = computed(() => {
  return classes.value.reduce(
    (total, item) => total + item.summary.checked_in_count,
    0,
  )
})

const pendingValidations = computed(() => {
  return classes.value.reduce(
    (total, item) =>
      total + item.summary.pending_validation_items,
    0,
  )
})

const checkinRate = computed(() => {
  if (totalStudents.value === 0) {
    return 0
  }

  return Math.round(
    (checkedInStudents.value / totalStudents.value) * 100,
  )
})

onMounted(() => {
  loadDashboard()
})

async function loadDashboard() {
  try {
    loading.value = true
    error.value = ''

    const classResponse = await recapApi.getTeacherClasses()
    const classPayload = classResponse.data.data

    const teacherClasses = Array.isArray(classPayload)
      ? classPayload
      : classPayload?.items || []

    if (teacherClasses.length === 0) {
      classes.value = []
      return
    }

    const monitoringResponses = await Promise.allSettled(
      teacherClasses.map((classItem) =>
        recapApi.getClassCheckins(classItem.id, {
          date: today,
          per_page: 100,
        }),
      ),
    )

    classes.value = teacherClasses.map((classItem, index) => {
      const result = monitoringResponses[index]

      if (result.status === 'fulfilled') {
        return {
          ...classItem,
          summary: normalizeSummary(
            result.value.data.data?.summary,
          ),
        }
      }

      return {
        ...classItem,
        summary: normalizeSummary(),
      }
    })
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat dashboard guru.'
  } finally {
    loading.value = false
  }
}

function normalizeSummary(summary = {}) {
  const totalStudents = Number(summary.total_students || 0)
  const checkedInCount = Number(summary.checked_in_count || 0)

  return {
    total_students: totalStudents,
    checked_in_count: checkedInCount,
    not_checked_in_count: Number(
      summary.not_checked_in_count ??
        Math.max(totalStudents - checkedInCount, 0),
    ),
    total_done_items: Number(summary.total_done_items || 0),
    teacher_validated_items: Number(
      summary.teacher_validated_items || 0,
    ),
    pending_validation_items: Number(
      summary.pending_validation_items || 0,
    ),
  }
}

function getClassProgress(classItem) {
  const total = classItem.summary.total_students
  const checked = classItem.summary.checked_in_count

  if (!total) {
    return 0
  }

  return Math.min(
    Math.round((checked / total) * 100),
    100,
  )
}

function getClassInitial(classItem) {
  const value =
    classItem.grade_level ||
    classItem.name ||
    'K'

  return String(value)
    .trim()
    .slice(0, 2)
    .toUpperCase()
}

function openClassMonitoring(classItem) {
  router.push({
    path: '/teacher/monitoring',
    query: {
      class: classItem.id,
      date: today,
    },
  })
}

function getLocalDate() {
  const currentDate = new Date()
  const year = currentDate.getFullYear()
  const month = String(currentDate.getMonth() + 1).padStart(2, '0')
  const day = String(currentDate.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
</script>

<style scoped>
.teacher-page {
  display: flex;
  flex-direction: column;
  gap: 22px;
  padding-bottom: 28px;
}

.welcome-card {
  position: relative;
  overflow: hidden;
  min-height: 170px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 28px;
  padding: 32px;
  border-radius: 22px;
  background:
    radial-gradient(
      circle at 88% 10%,
      rgba(255, 255, 255, 0.22),
      transparent 24%
    ),
    linear-gradient(135deg, #1d9bf0 0%, #1686d1 100%);
  color: #ffffff;
  box-shadow: 0 16px 38px rgba(29, 155, 240, 0.2);
}

.welcome-card::after {
  content: "";
  position: absolute;
  right: -65px;
  bottom: -105px;
  width: 260px;
  height: 260px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
}

.welcome-copy,
.welcome-actions {
  position: relative;
  z-index: 1;
}

.eyebrow,
.section-eyebrow {
  margin: 0 0 8px;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.eyebrow {
  color: rgba(255, 255, 255, 0.68);
}

.welcome-copy h1 {
  margin: 0;
  font-size: clamp(24px, 3vw, 34px);
  line-height: 1.15;
  font-weight: 900;
  letter-spacing: -0.035em;
}

.welcome-copy > p:last-child {
  max-width: 590px;
  margin: 11px 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 14px;
  line-height: 1.7;
}

.welcome-actions {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.primary-button,
.secondary-button {
  min-height: 43px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 16px;
  border-radius: 12px;
  font: inherit;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease,
    opacity 0.18s ease;
}

.primary-button {
  border: 1px solid #ffffff;
  background: #ffffff;
  color: #1686d1;
}

.secondary-button {
  border: 1px solid rgba(255, 255, 255, 0.32);
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
}

.primary-button:hover,
.secondary-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.secondary-button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.secondary-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.primary-button svg,
.secondary-button svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.alert {
  padding: 14px 16px;
  border-radius: 14px;
  font-size: 13px;
  font-weight: 700;
}

.error-alert {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.stat-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 19px;
  border: 1px solid #e5edf5;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.045);
}

.stat-icon {
  width: 47px;
  height: 47px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 14px;
}

.stat-icon.blue {
  background: #eaf6ff;
  color: #168ad3;
}

.stat-icon.cyan {
  background: #ecfeff;
  color: #0891b2;
}

.stat-icon.green {
  background: #ecfdf5;
  color: #059669;
}

.stat-icon.orange {
  background: #fff7ed;
  color: #ea580c;
}

.stat-icon svg {
  width: 21px;
  height: 21px;
  stroke: currentColor;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.stat-card > div:last-child {
  min-width: 0;
}

.stat-label {
  display: block;
  overflow: hidden;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stat-value {
  display: block;
  margin-top: 5px;
  color: #0f172a;
  font-size: 25px;
  line-height: 1;
  font-weight: 900;
}

.stat-card small {
  display: block;
  margin-top: 6px;
  color: #94a3b8;
  font-size: 10.5px;
  font-weight: 600;
}

.classes-section {
  padding: 24px;
  border: 1px solid #e5edf5;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.045);
}

.section-header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: flex-end;
  margin-bottom: 20px;
}

.section-eyebrow {
  color: #209cee;
}

.section-header h2 {
  margin: 0;
  color: #0f172a;
  font-size: 20px;
  font-weight: 900;
  letter-spacing: -0.025em;
}

.section-header p:not(.section-eyebrow) {
  margin: 6px 0 0;
  color: #94a3b8;
  font-size: 12px;
  text-transform: capitalize;
}

.text-button {
  display: inline-flex;
  gap: 7px;
  align-items: center;
  border: 0;
  background: transparent;
  color: #168ad3;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.class-grid {
  display: grid;
  grid-template-columns: repeat(
    auto-fill,
    minmax(280px, 1fr)
  );
  gap: 14px;
}

.class-card {
  padding: 18px;
  border: 1px solid #e4edf6;
  border-radius: 18px;
  background: #fbfdff;
  cursor: pointer;
  outline: none;
  transition:
    transform 0.18s ease,
    border-color 0.18s ease,
    box-shadow 0.18s ease;
}

.class-card:hover,
.class-card:focus-visible {
  transform: translateY(-2px);
  border-color: #a9daf8;
  box-shadow: 0 12px 28px rgba(32, 156, 238, 0.1);
}

.class-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.class-identity {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.class-badge {
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 13px;
  background: #eaf6ff;
  color: #168ad3;
  font-size: 13px;
  font-weight: 900;
}

.class-identity h3 {
  margin: 0;
  color: #0f172a;
  font-size: 15px;
  font-weight: 900;
}

.class-identity p {
  margin: 4px 0 0;
  color: #94a3b8;
  font-size: 11px;
}

.arrow-button {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 0;
  border-radius: 10px;
  background: #ffffff;
  color: #168ad3;
  cursor: pointer;
}

.arrow-button svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.class-numbers {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 7px;
  margin-top: 18px;
}

.class-numbers div {
  min-width: 0;
  padding: 10px 8px;
  border-radius: 11px;
  background: #ffffff;
  text-align: center;
}

.class-numbers span {
  display: block;
  overflow: hidden;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.class-numbers strong {
  display: block;
  margin-top: 5px;
  color: #1e293b;
  font-size: 16px;
  font-weight: 900;
}

.progress-section {
  margin-top: 17px;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 7px;
  color: #64748b;
  font-size: 10.5px;
  font-weight: 700;
}

.progress-header strong {
  color: #168ad3;
}

.progress-track {
  height: 7px;
  overflow: hidden;
  border-radius: 999px;
  background: #e5edf5;
}

.progress-fill {
  height: 100%;
  border-radius: inherit;
  background: #209cee;
  transition: width 0.35s ease;
}

.class-footer {
  margin-top: 15px;
  padding-top: 14px;
  border-top: 1px solid #e8eef5;
}

.validation-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 10.5px;
  font-weight: 800;
}

.validation-status.pending {
  color: #c2410c;
}

.validation-status.complete {
  color: #047857;
}

.status-dot {
  width: 7px;
  height: 7px;
  flex-shrink: 0;
  border-radius: 50%;
  background: currentColor;
}

.state-card {
  min-height: 170px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  border: 1px dashed #cbd5e1;
  border-radius: 17px;
  background: #f8fafc;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
}

.empty-state {
  flex-direction: column;
  text-align: center;
}

.empty-state strong {
  color: #334155;
  font-size: 14px;
}

.empty-state p {
  max-width: 390px;
  margin: 0;
  color: #94a3b8;
  font-size: 12px;
  line-height: 1.6;
}

.empty-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  background: #eaf6ff;
  color: #168ad3;
}

.empty-icon svg {
  width: 22px;
  height: 22px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.loader {
  width: 22px;
  height: 22px;
  border: 3px solid #dbeafe;
  border-top-color: #209cee;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1120px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .welcome-card {
    align-items: flex-start;
  }

  .welcome-actions {
    flex-direction: column;
  }
}

@media (max-width: 760px) {
  .welcome-card {
    min-height: auto;
    flex-direction: column;
    padding: 24px;
  }

  .welcome-actions {
    width: 100%;
    flex-direction: row;
  }

  .primary-button,
  .secondary-button {
    flex: 1;
  }

  .classes-section {
    padding: 18px;
  }
}

@media (max-width: 560px) {
  .teacher-page {
    gap: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    align-items: flex-start;
  }

  .class-grid {
    grid-template-columns: 1fr;
  }

  .welcome-actions {
    flex-direction: column;
  }

  .primary-button,
  .secondary-button {
    width: 100%;
  }
}
</style>