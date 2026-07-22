<template>
  <section class="recap-page">
    <div class="page-banner">
      <div>
        <p class="banner-kicker">Laporan Kelas</p>
        <h1>Rekap Bulanan Kelas</h1>
        <p>
          Lihat perkembangan check-in, penyelesaian kebiasaan, dan
          validasi siswa berdasarkan bulan.
        </p>
      </div>

      <div v-if="recap?.class" class="banner-class">
        <span>Kelas aktif</span>
        <strong>{{ formatClassName(recap.class) }}</strong>
      </div>
    </div>

    <div v-if="error" class="alert error-alert">
      {{ error }}
    </div>

    <section class="filter-card">
      <div class="filter-group class-filter">
        <label for="classSelect">Kelas</label>

        <select
          id="classSelect"
          v-model="selectedClassId"
          :disabled="loadingClasses || classes.length === 0"
        >
          <option value="" disabled>Pilih kelas</option>

          <option
            v-for="classItem in classes"
            :key="classItem.id"
            :value="String(classItem.id)"
          >
            {{ formatClassName(classItem) }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label for="monthSelect">Bulan</label>

        <select id="monthSelect" v-model="selectedMonth">
          <option
            v-for="monthItem in monthOptions"
            :key="monthItem.value"
            :value="monthItem.value"
          >
            {{ monthItem.label }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label for="yearSelect">Tahun</label>

        <select id="yearSelect" v-model="selectedYear">
          <option
            v-for="year in yearOptions"
            :key="year"
            :value="String(year)"
          >
            {{ year }}
          </option>
        </select>
      </div>

      <div class="filter-actions">
        <button
          type="button"
          class="show-button"
          :disabled="
            loadingRecap ||
            !selectedClassId ||
            classes.length === 0
          "
          @click="loadRecap"
        >
          <span v-if="loadingRecap" class="small-loader"></span>

          {{ loadingRecap ? 'Memuat...' : 'Tampilkan Rekap' }}
        </button>

        <button
          type="button"
          class="export-button"
          :disabled="loadingRecap || exporting || !recap || students.length === 0"
          @click="exportExcel"
        >
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 3v12" />
            <path d="m7 10 5 5 5-5" />
            <path d="M5 21h14" />
          </svg>

          {{ exporting ? 'Menyiapkan...' : 'Export Excel' }}
        </button>
      </div>
    </section>

    <div
      v-if="loadingClasses || loadingRecap"
      class="state-card"
    >
      <span class="loader"></span>
      <p>Memuat rekap kelas...</p>
    </div>

    <div
      v-else-if="classes.length === 0"
      class="state-card"
    >
      <div class="state-icon">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z" />
          <path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v17h4.5A2.5 2.5 0 0 1 20 22V5.5Z" />
        </svg>
      </div>

      <strong>Belum ada kelas yang diampu</strong>
      <p>Hubungi admin untuk menetapkan kelas kepada akun guru.</p>
    </div>

    <div
      v-else-if="!recap"
      class="state-card"
    >
      <strong>Rekap belum ditampilkan</strong>
      <p>Pilih kelas dan periode, kemudian tekan Tampilkan Rekap.</p>
    </div>

    <template v-else>
      <section class="period-card">
        <div>
          <p class="section-kicker">Periode Rekap</p>
          <h2>{{ selectedPeriodLabel }}</h2>
          <p>{{ formattedDateRange }}</p>
        </div>

        <div class="teacher-info">
          <span>Wali kelas</span>
          <strong>
            {{ recap.class?.teacher?.full_name || 'Belum tersedia' }}
          </strong>
        </div>
      </section>

      <section class="summary-grid">
        <article class="summary-card">
          <div class="summary-icon blue">
            <svg viewBox="0 0 24 24" fill="none">
              <circle cx="9" cy="8" r="3" />
              <path d="M3.5 20a5.5 5.5 0 0 1 11 0" />
              <circle cx="17" cy="9" r="2.5" />
              <path d="M15.5 15a4.5 4.5 0 0 1 5 5" />
            </svg>
          </div>

          <div>
            <span>Total Siswa</span>
            <strong>{{ summary.total_students }}</strong>
            <small>Dalam kelas</small>
          </div>
        </article>

        <article class="summary-card">
          <div class="summary-icon cyan">
            <svg viewBox="0 0 24 24" fill="none">
              <rect x="5" y="3" width="14" height="18" rx="2" />
              <path d="m8 12 2.2 2.2L16 8.5" />
            </svg>
          </div>

          <div>
            <span>Total Check-in</span>
            <strong>{{ summary.total_checkins }}</strong>
            <small>Selama {{ selectedPeriodLabel }}</small>
          </div>
        </article>

        <article class="summary-card">
          <div class="summary-icon green">
            <svg viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" />
              <path d="m8 12 2.5 2.5L16.5 8" />
            </svg>
          </div>

          <div>
            <span>Kebiasaan Selesai</span>
            <strong>{{ summary.total_done_items }}</strong>
            <small>Total item selesai</small>
          </div>
        </article>

        <article class="summary-card">
          <div class="summary-icon navy">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M9 12.5 11 14.5 15.5 9.5" />
              <path d="M12 3 4.5 6v5.5c0 4.7 3.2 8.1 7.5 9.5 4.3-1.4 7.5-4.8 7.5-9.5V6L12 3Z" />
            </svg>
          </div>

          <div>
            <span>Validasi Guru</span>
            <strong>{{ summary.teacher_validated_items }}</strong>
            <small>Item tervalidasi</small>
          </div>
        </article>

        <article class="summary-card">
          <div class="summary-icon orange">
            <svg viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 7v5" />
              <path d="M12 16h.01" />
            </svg>
          </div>

          <div>
            <span>Menunggu Validasi</span>
            <strong>{{ summary.pending_validation_items }}</strong>
            <small>Item belum divalidasi</small>
          </div>
        </article>
      </section>

      <section class="student-card">
        <div class="section-header">
          <div>
            <p class="section-kicker">Detail Siswa</p>
            <h2>Rekap Siswa {{ formatClassName(recap.class) }}</h2>
            <p>
              Ringkasan aktivitas masing-masing siswa pada
              {{ selectedPeriodLabel }}.
            </p>
          </div>

          <span class="student-count">
            {{ students.length }} siswa
          </span>
        </div>

        <div v-if="students.length === 0" class="empty-table">
          <strong>Belum ada data siswa</strong>
          <p>
            Tidak ada siswa atau check-in pada periode yang dipilih.
          </p>
        </div>

        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Siswa</th>
                <th>Total Check-in</th>
                <th>Kebiasaan Selesai</th>
                <th>Pencapaian</th>
                <th>Validasi Guru</th>
                <th>Menunggu</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="studentItem in students"
                :key="studentItem.student.id"
              >
                <td>
                  <div class="student-cell">
                    <span class="student-avatar">
                      {{ getInitials(studentItem.student.full_name) }}
                    </span>

                    <div>
                      <strong>
                        {{ studentItem.student.full_name }}
                      </strong>
                      <span>
                        {{ studentItem.student.username }}
                      </span>
                    </div>
                  </div>
                </td>

                <td>
                  <strong class="cell-value">
                    {{ studentItem.summary.total_checkins }}
                  </strong>
                  <span class="cell-caption">hari</span>
                </td>

                <td>
                  <strong class="cell-value">
                    {{ studentItem.summary.total_done_items }}
                  </strong>
                  <span class="cell-caption">item</span>
                </td>

                <td>
                  <div class="completion-cell">
                    <div class="completion-header">
                      <strong>
                        {{ formatPercentage(
                          studentItem.summary.completion_percentage
                        ) }}%
                      </strong>
                    </div>

                    <div class="progress-track">
                      <div
                        class="progress-fill"
                        :style="{
                          width: `${clampPercentage(
                            studentItem.summary.completion_percentage
                          )}%`,
                        }"
                      ></div>
                    </div>
                  </div>
                </td>

                <td>
                  <strong class="cell-value">
                    {{ studentItem.summary.teacher_validated_items }}
                  </strong>
                  <span class="cell-caption">item</span>
                </td>

                <td>
                  <span
                    :class="[
                      'pending-badge',
                      {
                        complete:
                          studentItem.summary
                            .pending_validation_items === 0,
                      },
                    ]"
                  >
                    {{
                      studentItem.summary.pending_validation_items === 0
                        ? 'Selesai'
                        : `${studentItem.summary.pending_validation_items} item`
                    }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref,
} from 'vue'
import { recapApi } from '@/api/recap'

const currentDate = new Date()
const currentYear = currentDate.getFullYear()
const currentMonth = String(
  currentDate.getMonth() + 1,
).padStart(2, '0')

const monthOptions = [
  { value: '01', label: 'Januari' },
  { value: '02', label: 'Februari' },
  { value: '03', label: 'Maret' },
  { value: '04', label: 'April' },
  { value: '05', label: 'Mei' },
  { value: '06', label: 'Juni' },
  { value: '07', label: 'Juli' },
  { value: '08', label: 'Agustus' },
  { value: '09', label: 'September' },
  { value: '10', label: 'Oktober' },
  { value: '11', label: 'November' },
  { value: '12', label: 'Desember' },
]

const loadingClasses = ref(true)
const loadingRecap = ref(false)
const error = ref('')

const classes = ref([])
const selectedClassId = ref('')
const selectedMonth = ref(currentMonth)
const selectedYear = ref(String(currentYear))
const recap = ref(null)
const exporting = ref(false)

const yearOptions = computed(() => {
  const years = []

  for (let year = currentYear + 1; year >= currentYear - 5; year--) {
    years.push(year)
  }

  return years
})

const selectedPeriod = computed(() => {
  return `${selectedYear.value}-${selectedMonth.value}`
})

const selectedPeriodLabel = computed(() => {
  const selected = monthOptions.find(
    (item) => item.value === selectedMonth.value,
  )

  return `${selected?.label || ''} ${selectedYear.value}`
})

const summary = computed(() => {
  const data = recap.value?.summary || {}

  return {
    total_students: Number(data.total_students || 0),
    total_checkins: Number(data.total_checkins || 0),
    total_done_items: Number(data.total_done_items || 0),
    total_validated_items: Number(
      data.total_validated_items || 0,
    ),
    parent_validated_items: Number(
      data.parent_validated_items || 0,
    ),
    teacher_validated_items: Number(
      data.teacher_validated_items || 0,
    ),
    pending_validation_items: Number(
      data.pending_validation_items || 0,
    ),
  }
})

const students = computed(() => {
  const items = Array.isArray(recap.value?.students)
    ? recap.value.students
    : []

  return items
    .filter((item) => item?.student)
    .map((item) => {
      const studentSummary = item.summary || {}
      const totalItems = Number(studentSummary.total_items || 0)
      const totalDoneItems = Number(
        studentSummary.total_done_items || 0,
      )

      const completionPercentage = Number(
        studentSummary.completion_percentage ??
          (
            totalItems > 0
              ? (totalDoneItems / totalItems) * 100
              : 0
          ),
      )

      return {
        student: item.student,
        summary: {
          total_checkins: Number(
            studentSummary.total_checkins || 0,
          ),
          total_items: totalItems,
          total_done_items: totalDoneItems,
          teacher_validated_items: Number(
            studentSummary.teacher_validated_items || 0,
          ),
          pending_validation_items: Number(
            studentSummary.pending_validation_items || 0,
          ),
          completion_percentage: completionPercentage,
        },
      }
    })
})

const formattedDateRange = computed(() => {
  const startDate = recap.value?.period?.start_date
  const endDate = recap.value?.period?.end_date

  if (!startDate || !endDate) {
    return 'Periode tanggal tidak tersedia'
  }

  return `${formatDate(startDate)} sampai ${formatDate(endDate)}`
})

onMounted(() => {
  loadClasses()
})

async function loadClasses() {
  try {
    loadingClasses.value = true
    error.value = ''

    const response = await recapApi.getTeacherClasses()
    const payload = response.data.data

    classes.value = Array.isArray(payload)
      ? payload
      : payload?.items || []

    if (classes.value.length === 0) {
      selectedClassId.value = ''
      recap.value = null
      return
    }

    selectedClassId.value = String(classes.value[0].id)

    await loadRecap()
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat daftar kelas guru.'
  } finally {
    loadingClasses.value = false
  }
}

async function loadRecap() {
  if (!selectedClassId.value) {
    recap.value = null
    return
  }

  try {
    loadingRecap.value = true
    error.value = ''

    const response = await recapApi.getMonthlyRecap(
      selectedClassId.value,
      {
        month: selectedPeriod.value,
      },
    )

    recap.value = response.data.data || null
  } catch (err) {
    recap.value = null

    error.value =
      err.response?.data?.message ||
      'Gagal memuat rekap bulanan kelas.'
  } finally {
    loadingRecap.value = false
  }
}

function exportExcel() {
  if (!recap.value || students.value.length === 0) return

  try {
    exporting.value = true

    const className = formatClassName(recap.value.class)
    const teacherName = recap.value.class?.teacher?.full_name || 'Belum tersedia'

    const studentRows = students.value
      .map((studentItem, index) => {
        return `
          <tr>
            <td>${index + 1}</td>
            <td>${escapeHtml(studentItem.student.full_name || '-')}</td>
            <td>${escapeHtml(studentItem.student.username || '-')}</td>
            <td>${studentItem.summary.total_checkins}</td>
            <td>${studentItem.summary.total_done_items}</td>
            <td>${formatPercentage(studentItem.summary.completion_percentage)}%</td>
            <td>${studentItem.summary.teacher_validated_items}</td>
            <td>${studentItem.summary.pending_validation_items}</td>
          </tr>
        `
      })
      .join('')

    const workbook = `
      <!DOCTYPE html>
      <html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
          <meta charset="UTF-8" />
          <style>
            body { font-family: Arial, sans-serif; }
            h1 { font-size: 20px; }
            .meta td { padding: 3px 8px; }
            table.data { border-collapse: collapse; margin-top: 16px; }
            table.data th, table.data td {
              border: 1px solid #9ca3af;
              padding: 7px 10px;
            }
            table.data th {
              background: #dbeafe;
              font-weight: bold;
            }
          </style>
        </head>
        <body>
          <h1>Rekap Bulanan Kelas</h1>
          <table class="meta">
            <tr><td><strong>Kelas</strong></td><td>${escapeHtml(className)}</td></tr>
            <tr><td><strong>Periode</strong></td><td>${escapeHtml(selectedPeriodLabel.value)}</td></tr>
            <tr><td><strong>Rentang Tanggal</strong></td><td>${escapeHtml(formattedDateRange.value)}</td></tr>
            <tr><td><strong>Wali Kelas</strong></td><td>${escapeHtml(teacherName)}</td></tr>
            <tr><td><strong>Total Siswa</strong></td><td>${summary.value.total_students}</td></tr>
            <tr><td><strong>Total Check-in</strong></td><td>${summary.value.total_checkins}</td></tr>
            <tr><td><strong>Kebiasaan Selesai</strong></td><td>${summary.value.total_done_items}</td></tr>
            <tr><td><strong>Validasi Guru</strong></td><td>${summary.value.teacher_validated_items}</td></tr>
            <tr><td><strong>Menunggu Validasi</strong></td><td>${summary.value.pending_validation_items}</td></tr>
          </table>

          <table class="data">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Username</th>
                <th>Total Check-in</th>
                <th>Kebiasaan Selesai</th>
                <th>Pencapaian</th>
                <th>Validasi Guru</th>
                <th>Menunggu Validasi</th>
              </tr>
            </thead>
            <tbody>${studentRows}</tbody>
          </table>
        </body>
      </html>
    `

    const blob = new Blob(['\ufeff', workbook], {
      type: 'application/vnd.ms-excel;charset=utf-8;',
    })

    const url = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    const safeClass = slugify(className)
    const safePeriod = slugify(selectedPeriodLabel.value)

    anchor.href = url
    anchor.download = `rekap-kelas-${safeClass}-${safePeriod}.xls`
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function slugify(value) {
  return String(value || 'rekap')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function formatClassName(classItem) {
  if (!classItem) {
    return 'Kelas'
  }

  const name = String(classItem.name || '').trim()
  const grade = String(classItem.grade_level || '').trim()

  if (!grade) {
    return name || 'Kelas'
  }

  if (name.toUpperCase().startsWith(grade.toUpperCase())) {
    return name
  }

  return [grade, name].filter(Boolean).join(' - ')
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  const safeValue = String(value).slice(0, 10)
  const date = new Date(`${safeValue}T00:00:00`)

  if (Number.isNaN(date.getTime())) {
    return safeValue
  }

  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

function getInitials(name) {
  const words = String(name || 'S')
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words[words.length - 1][0]}`
    .toUpperCase()
}

function clampPercentage(value) {
  return Math.max(
    0,
    Math.min(Number(value || 0), 100),
  )
}

function formatPercentage(value) {
  return Math.round(Number(value || 0))
}
</script>

<style scoped>
.recap-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 30px;
}

.page-banner {
  min-height: 165px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 26px;
  padding: 30px 32px;
  border-radius: 22px;
  background:
    radial-gradient(
      circle at 91% 8%,
      rgba(255, 255, 255, 0.2),
      transparent 28%
    ),
    linear-gradient(
      135deg,
      var(--color-primary-deep) 0%,
      var(--color-primary) 55%,
      var(--color-primary-light) 100%
    );
  color: #ffffff;
  box-shadow: 0 16px 36px rgba(29, 155, 240, 0.18);
}

.banner-kicker,
.section-kicker {
  margin: 0 0 7px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.banner-kicker {
  color: rgba(255, 255, 255, 0.7);
}

.page-banner h1 {
  margin: 0;
  font-size: clamp(24px, 3vw, 32px);
  font-weight: 900;
  letter-spacing: -0.035em;
}

.page-banner > div:first-child > p:last-child {
  max-width: 650px;
  margin: 10px 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 13px;
  line-height: 1.7;
}

.banner-class {
  min-width: 150px;
  padding: 16px 18px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 15px;
  background: rgba(255, 255, 255, 0.13);
}

.banner-class span,
.teacher-info span {
  display: block;
  font-size: 10px;
  font-weight: 700;
}

.banner-class strong,
.teacher-info strong {
  display: block;
  margin-top: 5px;
  font-size: 15px;
  font-weight: 900;
}

.filter-card {
  display: grid;
  grid-template-columns:
    minmax(190px, 1.4fr)
    minmax(150px, 1fr)
    minmax(120px, 0.8fr)
    auto;
  gap: 13px;
  align-items: end;
  padding: 19px;
  border: 1px solid #e4edf6;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.filter-group label {
  color: #475569;
  font-size: 11px;
  font-weight: 800;
}

.filter-group select {
  width: 100%;
  height: 43px;
  padding: 0 12px;
  border: 1px solid #dce6ef;
  border-radius: 11px;
  background: #fbfdff;
  color: #1e293b;
  font: inherit;
  font-size: 12px;
  outline: none;
}

.filter-group select:focus {
  border-color: #209cee;
  box-shadow: 0 0 0 4px rgba(32, 156, 238, 0.1);
}

.show-button {
  min-height: 43px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 17px;
  border: 0;
  border-radius: 11px;
  background: #209cee;
  color: #ffffff;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.show-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filter-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.export-button {
  min-height: 43px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 17px;
  border: 1px solid #bfdbfe;
  border-radius: 11px;
  background: #eff6ff;
  color: #1686d1;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.export-button svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.export-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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

.period-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 20px 22px;
  border: 1px solid #dbeafe;
  border-radius: 18px;
  background: #f7fcff;
}

.section-kicker {
  color: #209cee;
}

.period-card h2,
.section-header h2 {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
  font-weight: 900;
}

.period-card p:last-child,
.section-header p:last-child {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 11.5px;
}

.teacher-info {
  min-width: 190px;
  padding: 12px 15px;
  border-radius: 12px;
  background: #ffffff;
  color: #334155;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 13px;
}

.summary-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 17px;
  border: 1px solid #e4edf6;
  border-radius: 17px;
  background: #ffffff;
}

.summary-icon {
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 13px;
}

.summary-icon.blue {
  background: #eaf6ff;
  color: #1686d1;
}

.summary-icon.cyan {
  background: #ecfeff;
  color: #0891b2;
}

.summary-icon.green {
  background: #ecfdf5;
  color: #059669;
}

.summary-icon.navy {
  background: #eef2ff;
  color: #3156a3;
}

.summary-icon.orange {
  background: #fff7ed;
  color: #ea580c;
}

.summary-icon svg {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.summary-card > div:last-child {
  min-width: 0;
}

.summary-card span {
  display: block;
  color: #64748b;
  font-size: 10.5px;
  font-weight: 700;
}

.summary-card strong {
  display: block;
  margin-top: 5px;
  color: #0f172a;
  font-size: 22px;
  line-height: 1;
  font-weight: 900;
}

.summary-card small {
  display: block;
  margin-top: 6px;
  overflow: hidden;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.student-card {
  overflow: hidden;
  border: 1px solid #e4edf6;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 9px 28px rgba(15, 23, 42, 0.04);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 21px 22px;
  border-bottom: 1px solid #e8eef5;
}

.student-count {
  padding: 7px 11px;
  border-radius: 999px;
  background: #eaf6ff;
  color: #1686d1;
  font-size: 10.5px;
  font-weight: 800;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
}

th {
  padding: 13px 16px;
  border-bottom: 1px solid #e8eef5;
  background: #f8fafc;
  color: #64748b;
  font-size: 9.5px;
  font-weight: 900;
  text-align: left;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

td {
  padding: 15px 16px;
  border-bottom: 1px solid #edf2f7;
  color: #334155;
  font-size: 12px;
}

tbody tr:last-child td {
  border-bottom: 0;
}

tbody tr:hover {
  background: #fbfdff;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: 11px;
}

.student-avatar {
  width: 37px;
  height: 37px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 12px;
  background: #eaf6ff;
  color: #1686d1;
  font-size: 10px;
  font-weight: 900;
}

.student-cell strong {
  display: block;
  color: #1e293b;
  font-size: 12.5px;
  font-weight: 800;
}

.student-cell span:last-child {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 9.5px;
}

.cell-value {
  display: block;
  color: #1e293b;
  font-size: 14px;
  font-weight: 900;
}

.cell-caption {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 9.5px;
}

.completion-cell {
  min-width: 120px;
}

.completion-header {
  margin-bottom: 7px;
}

.completion-header strong {
  color: #1686d1;
  font-size: 11px;
  font-weight: 900;
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
}

.pending-badge {
  display: inline-flex;
  padding: 6px 9px;
  border: 1px solid #fed7aa;
  border-radius: 999px;
  background: #fff7ed;
  color: #c2410c;
  font-size: 9.5px;
  font-weight: 800;
}

.pending-badge.complete {
  border-color: #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}

.state-card,
.empty-table {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 30px;
  border: 1px dashed #cbd5e1;
  border-radius: 18px;
  background: #f8fafc;
  color: #64748b;
  text-align: center;
}

.state-card strong,
.empty-table strong {
  color: #334155;
  font-size: 14px;
}

.state-card p,
.empty-table p {
  max-width: 430px;
  margin: 0;
  color: #94a3b8;
  font-size: 11.5px;
  line-height: 1.6;
}

.state-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  background: #eaf6ff;
  color: #1686d1;
}

.state-icon svg {
  width: 22px;
  height: 22px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.loader,
.small-loader {
  display: inline-block;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.loader {
  width: 25px;
  height: 25px;
  border: 3px solid #dbeafe;
  border-top-color: #209cee;
}

.small-loader {
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #ffffff;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1180px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 850px) {
  .filter-card {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .filter-actions {
    width: 100%;
  }

  .show-button,
  .export-button {
    flex: 1;
  }
}

@media (max-width: 620px) {
  .page-banner,
  .period-card,
  .section-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .banner-class,
  .teacher-info {
    width: 100%;
  }

  .filter-card,
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .filter-actions {
    flex-direction: column;
  }

  .show-button,
  .export-button {
    width: 100%;
  }
}
</style>