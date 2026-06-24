<template>
  <section class="monitoring-page">
    <div class="page-banner">
      <div>
        <p class="banner-kicker">Monitoring Jurnal</p>
        <h1>Monitoring & Validasi Siswa</h1>
        <p>
          Pantau jurnal harian siswa dan lakukan validasi pada kebiasaan
          yang telah diselesaikan.
        </p>
      </div>

      <button
        type="button"
        class="refresh-button"
        :disabled="loadingMonitoring"
        @click="loadMonitoring"
      >
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M20 12a8 8 0 1 1-2.3-5.7" />
          <path d="M20 4v6h-6" />
        </svg>

        {{ loadingMonitoring ? 'Memuat...' : 'Muat Ulang' }}
      </button>
    </div>

    <div v-if="error" class="alert error-alert">
      {{ error }}
    </div>

    <section class="filter-card">
      <div class="filter-group">
        <label for="classFilter">Kelas</label>

        <select
          id="classFilter"
          v-model="selectedClassId"
          :disabled="loadingClasses || classes.length === 0"
          @change="handleFilterChange"
        >
          <option value="" disabled>Pilih kelas</option>

          <option
            v-for="classItem in classes"
            :key="classItem.id"
            :value="String(classItem.id)"
          >
            {{ classItem.grade_level }} - {{ classItem.name }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label for="dateFilter">Tanggal</label>

        <input
          id="dateFilter"
          v-model="selectedDate"
          type="date"
          @change="handleFilterChange"
        />
      </div>

      <div class="filter-group search-group">
        <label for="studentSearch">Cari Siswa</label>

        <input
          id="studentSearch"
          v-model="search"
          type="search"
          placeholder="Cari nama siswa..."
        />
      </div>

      <div class="filter-group">
        <label for="statusFilter">Status</label>

        <select id="statusFilter" v-model="statusFilter">
          <option value="all">Semua Status</option>
          <option value="checked">Sudah Check-in</option>
          <option value="not-checked">Belum Check-in</option>
          <option value="pending">Menunggu Validasi</option>
          <option value="complete">Validasi Selesai</option>
        </select>
      </div>
    </section>

    <div class="summary-grid">
      <article class="summary-card">
        <span>Total Siswa</span>
        <strong>{{ loadingMonitoring ? '—' : summary.total_students }}</strong>
        <small>{{ selectedClassLabel }}</small>
      </article>

      <article class="summary-card blue">
        <span>Sudah Check-in</span>
        <strong>{{ loadingMonitoring ? '—' : summary.checked_in_count }}</strong>
        <small>{{ checkinPercentage }}% dari kelas</small>
      </article>

      <article class="summary-card neutral">
        <span>Belum Check-in</span>
        <strong>
          {{ loadingMonitoring ? '—' : summary.not_checked_in_count }}
        </strong>
        <small>Pada tanggal terpilih</small>
      </article>

      <article class="summary-card orange">
        <span>Menunggu Validasi</span>
        <strong>
          {{ loadingMonitoring ? '—' : summary.pending_validation_items }}
        </strong>
        <small>Item kebiasaan</small>
      </article>
    </div>

    <section class="table-card">
      <div class="table-header">
        <div>
          <p class="table-kicker">Daftar Siswa</p>
          <h2>{{ selectedClassLabel }}</h2>
          <p>{{ formattedSelectedDate }}</p>
        </div>

        <span class="result-count">
          {{ filteredRows.length }} siswa
        </span>
      </div>

      <div v-if="loadingClasses || loadingMonitoring" class="table-state">
        <span class="loader"></span>
        <p>Memuat data monitoring...</p>
      </div>

      <div v-else-if="classes.length === 0" class="table-state">
        <strong>Belum ada kelas yang diampu</strong>
        <p>Hubungi admin untuk menetapkan kelas kepada akun guru.</p>
      </div>

      <div v-else-if="filteredRows.length === 0" class="table-state">
        <strong>Data siswa tidak ditemukan</strong>
        <p>Coba ubah pencarian atau filter status.</p>
      </div>

      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Siswa</th>
              <th>Status Jurnal</th>
              <th>Kebiasaan Selesai</th>
              <th>Validasi Guru</th>
              <th class="action-column">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="row in filteredRows" :key="row.student_id">
              <td>
                <div class="student-cell">
                  <span class="student-avatar">
                    {{ studentInitials(row.student_name) }}
                  </span>

                  <div>
                    <strong>{{ row.student_name }}</strong>
                    <span>ID Siswa: {{ row.student_id }}</span>
                  </div>
                </div>
              </td>

              <td>
                <ValidationStatusBadge
                  :status="rowStatus(row)"
                  :label="rowStatusLabel(row)"
                />
              </td>

              <td>
                <div class="habit-progress">
                  <strong>{{ row.total_habits_done }}</strong>
                  <span>habit selesai</span>
                </div>
              </td>

              <td>
                <div v-if="row.checked_in" class="validation-numbers">
                  <strong>{{ row.teacher_validated_items }}</strong>
                  <span>
                    tervalidasi ·
                    {{ row.teacher_pending_validation_count }} menunggu
                  </span>
                </div>

                <span v-else class="empty-value">—</span>
              </td>

              <td class="action-column">
                <button
                  type="button"
                  class="detail-button"
                  :disabled="!row.daily_checkin_id"
                  @click="openCheckinDetail(row)"
                >
                  Lihat Jurnal
                  <span>→</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <CheckinDetailPanel
      :open="detailOpen"
      :checkin="selectedCheckin"
      :loading="detailLoading"
      :validating-item-id="validatingItemId"
      :validating-all="validatingAll"
      @close="closeDetail"
      @validate-item="validateItem"
      @validate-all="validateAll"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { recapApi } from '@/api/recap'
import { validationApi } from '@/api/validation'
import CheckinDetailPanel from '@/components/cards/CheckinDetailPanel.vue'
import ValidationStatusBadge from '@/components/common/ValidationStatusBadge.vue'

const route = useRoute()
const router = useRouter()

const classes = ref([])
const rows = ref([])
const selectedClassId = ref('')
const selectedDate = ref(getLocalDate())
const search = ref('')
const statusFilter = ref('all')
const loadingClasses = ref(false)
const loadingMonitoring = ref(false)
const error = ref('')
const summary = ref(createEmptySummary())
const detailOpen = ref(false)
const detailLoading = ref(false)
const selectedCheckin = ref(null)
const selectedCheckinId = ref(null)
const validatingItemId = ref(null)
const validatingAll = ref(false)

const selectedClass = computed(() => {
  return classes.value.find((item) => {
    return String(item.id) === String(selectedClassId.value)
  })
})

const selectedClassLabel = computed(() => {
  if (!selectedClass.value) {
    return 'Kelas belum dipilih'
  }

  return [selectedClass.value.grade_level, selectedClass.value.name]
    .filter(Boolean)
    .join(' - ')
})

const formattedSelectedDate = computed(() => {
  return formatDate(selectedDate.value)
})

const checkinPercentage = computed(() => {
  if (!summary.value.total_students) return 0

  return Math.round(
    (summary.value.checked_in_count / summary.value.total_students) * 100,
  )
})

const filteredRows = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  return rows.value.filter((row) => {
    const matchesSearch =
      !keyword ||
      String(row.student_name || '')
        .toLowerCase()
        .includes(keyword)

    return matchesSearch && matchStatus(row)
  })
})

onMounted(loadClasses)

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
      rows.value = []
      summary.value = createEmptySummary()
      return
    }

    const queryClassId = String(route.query.class || '')
    const queryClassExists = classes.value.some((item) => {
      return String(item.id) === queryClassId
    })

    selectedClassId.value = queryClassExists
      ? queryClassId
      : String(classes.value[0].id)

    if (route.query.date) {
      selectedDate.value = String(route.query.date).slice(0, 10)
    }

    await syncQuery()
    await loadMonitoring()
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal mengambil daftar kelas guru.'
  } finally {
    loadingClasses.value = false
  }
}

async function loadMonitoring() {
  if (!selectedClassId.value) {
    rows.value = []
    summary.value = createEmptySummary()
    return
  }

  try {
    loadingMonitoring.value = true
    error.value = ''

    const response = await recapApi.getClassCheckins(
      selectedClassId.value,
      {
        date: selectedDate.value,
        per_page: 100,
      },
    )

    const payload = response.data.data || {}

    rows.value = Array.isArray(payload.items)
      ? payload.items.map(normalizeRow)
      : []

    summary.value = normalizeSummary(payload.summary)
  } catch (err) {
    rows.value = []
    summary.value = createEmptySummary()
    error.value =
      err.response?.data?.message ||
      'Gagal memuat monitoring check-in kelas.'
  } finally {
    loadingMonitoring.value = false
  }
}

async function handleFilterChange() {
  closeDetail()
  await syncQuery()
  await loadMonitoring()
}

async function syncQuery() {
  await router.replace({
    query: {
      class: selectedClassId.value || undefined,
      date: selectedDate.value || undefined,
    },
  })
}

async function openCheckinDetail(row) {
  if (!row.daily_checkin_id) return

  selectedCheckinId.value = row.daily_checkin_id
  selectedCheckin.value = null
  detailOpen.value = true

  await loadDetail()
}

async function loadDetail() {
  if (!selectedCheckinId.value) return

  try {
    detailLoading.value = true
    error.value = ''

    const response = await validationApi.getTeacherCheckinDetail(
      selectedCheckinId.value,
    )

    selectedCheckin.value = response.data.data || null
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat detail jurnal siswa.'

    detailOpen.value = false
  } finally {
    detailLoading.value = false
  }
}

async function validateItem(itemId) {
  try {
    validatingItemId.value = itemId
    error.value = ''

    await validationApi.validateTeacherItem(itemId)
    await Promise.all([loadDetail(), loadMonitoring()])
  } catch (err) {
    error.value =
      extractValidationError(err) ||
      'Gagal memvalidasi item kebiasaan.'
  } finally {
    validatingItemId.value = null
  }
}

async function validateAll(itemIds) {
  if (
    !selectedCheckinId.value ||
    !Array.isArray(itemIds) ||
    itemIds.length === 0
  ) {
    return
  }

  try {
    validatingAll.value = true
    error.value = ''

    await validationApi.validateTeacherCheckin(
      selectedCheckinId.value,
      itemIds,
    )

    await Promise.all([loadDetail(), loadMonitoring()])
  } catch (err) {
    error.value =
      extractValidationError(err) ||
      'Gagal memvalidasi jurnal siswa.'
  } finally {
    validatingAll.value = false
  }
}

function closeDetail() {
  detailOpen.value = false
  selectedCheckin.value = null
  selectedCheckinId.value = null
  validatingItemId.value = null
  validatingAll.value = false
}

function normalizeRow(row = {}) {
  return {
    student_id: Number(row.student_id || 0),
    student_name: row.student_name || 'Siswa',
    checked_in: Boolean(row.checked_in),
    daily_checkin_id: row.daily_checkin_id || null,
    total_habits_done: Number(row.total_habits_done || 0),
    total_items_validated: Number(row.total_items_validated || 0),
    parent_validated_items: Number(row.parent_validated_items || 0),
    teacher_validated_items: Number(row.teacher_validated_items || 0),
    teacher_pending_validation_count: Number(
      row.teacher_pending_validation_count || 0,
    ),
  }
}

function normalizeSummary(data = {}) {
  const totalStudents = Number(data.total_students || 0)
  const checkedInCount = Number(data.checked_in_count || 0)

  return {
    total_students: totalStudents,
    checked_in_count: checkedInCount,
    not_checked_in_count: Number(
      data.not_checked_in_count ??
        Math.max(totalStudents - checkedInCount, 0),
    ),
    total_done_items: Number(data.total_done_items || 0),
    teacher_validated_items: Number(
      data.teacher_validated_items || 0,
    ),
    pending_validation_items: Number(
      data.pending_validation_items || 0,
    ),
  }
}

function createEmptySummary() {
  return {
    total_students: 0,
    checked_in_count: 0,
    not_checked_in_count: 0,
    total_done_items: 0,
    teacher_validated_items: 0,
    pending_validation_items: 0,
  }
}

function matchStatus(row) {
  switch (statusFilter.value) {
    case 'checked':
      return row.checked_in
    case 'not-checked':
      return !row.checked_in
    case 'pending':
      return row.checked_in && row.teacher_pending_validation_count > 0
    case 'complete':
      return (
        row.checked_in &&
        row.total_habits_done > 0 &&
        row.teacher_pending_validation_count === 0
      )
    default:
      return true
  }
}

function rowStatus(row) {
  if (!row.checked_in) return 'not-checked-in'

  if (row.teacher_pending_validation_count > 0) {
    return row.teacher_validated_items > 0 ? 'partial' : 'pending'
  }

  if (row.total_habits_done > 0) return 'complete'
  return 'checked-in'
}

function rowStatusLabel(row) {
  if (!row.checked_in) return 'Belum Check-in'

  if (row.teacher_pending_validation_count > 0) {
    return `${row.teacher_pending_validation_count} Menunggu`
  }

  if (row.total_habits_done > 0) return 'Validasi Selesai'
  return 'Sudah Check-in'
}

function studentInitials(name) {
  const words = String(name || 'S')
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase()
}

function extractValidationError(err) {
  const errors = err.response?.data?.errors

  if (!errors) {
    return err.response?.data?.message || ''
  }

  return Object.values(errors).flat().join(' ')
}

function getLocalDate() {
  const date = new Date()
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatDate(value) {
  if (!value) return 'Tanggal belum dipilih'

  const safeValue = String(value).slice(0, 10)
  const date = new Date(`${safeValue}T00:00:00`)

  if (Number.isNaN(date.getTime())) {
    return safeValue
  }

  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}
</script>

<style scoped>
.monitoring-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding-bottom: 28px;
}

.page-banner {
  min-height: 150px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 25px;
  padding: 28px 30px;
  border-radius: 22px;
  background:
    radial-gradient(
      circle at 90% 5%,
      rgba(255, 255, 255, 0.2),
      transparent 28%
    ),
    linear-gradient(135deg, #1d9bf0, #1686d1);
  color: #ffffff;
  box-shadow: 0 15px 34px rgba(29, 155, 240, 0.18);
}

.banner-kicker,
.table-kicker {
  margin: 0 0 7px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.banner-kicker {
  color: rgba(255, 255, 255, 0.68);
}

.page-banner h1 {
  margin: 0;
  font-size: clamp(23px, 3vw, 31px);
  font-weight: 900;
  letter-spacing: -0.035em;
}

.page-banner p:last-child {
  max-width: 650px;
  margin: 10px 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 13px;
  line-height: 1.7;
}

.refresh-button {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 15px;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.32);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.14);
  color: #ffffff;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.refresh-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.refresh-button svg {
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

.filter-card {
  display: grid;
  grid-template-columns:
    minmax(170px, 1fr)
    minmax(150px, 0.8fr)
    minmax(220px, 1.3fr)
    minmax(180px, 1fr);
  gap: 13px;
  padding: 18px;
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

.filter-group input,
.filter-group select {
  width: 100%;
  height: 43px;
  border: 1px solid #dce6ef;
  border-radius: 11px;
  padding: 0 12px;
  background: #fbfdff;
  color: #1e293b;
  font: inherit;
  font-size: 12px;
  outline: none;
}

.filter-group input:focus,
.filter-group select:focus {
  border-color: #209cee;
  box-shadow: 0 0 0 4px rgba(32, 156, 238, 0.1);
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 13px;
}

.summary-card {
  padding: 18px;
  border: 1px solid #e4edf6;
  border-radius: 17px;
  background: #ffffff;
}

.summary-card span {
  display: block;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
}

.summary-card strong {
  display: block;
  margin-top: 7px;
  color: #0f172a;
  font-size: 25px;
  line-height: 1;
  font-weight: 900;
}

.summary-card small {
  display: block;
  margin-top: 7px;
  color: #94a3b8;
  font-size: 9.5px;
  font-weight: 600;
}

.summary-card.blue {
  border-color: #cfe9fa;
  background: #f7fcff;
}

.summary-card.orange {
  border-color: #fed7aa;
  background: #fffaf5;
}

.table-card {
  overflow: hidden;
  border: 1px solid #e4edf6;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 9px 28px rgba(15, 23, 42, 0.045);
}

.table-header {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
  padding: 21px 22px;
  border-bottom: 1px solid #e8eef5;
}

.table-kicker {
  color: #209cee;
}

.table-header h2 {
  margin: 0;
  color: #0f172a;
  font-size: 17px;
  font-weight: 900;
}

.table-header p:last-child {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 11px;
  text-transform: capitalize;
}

.result-count {
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
  min-width: 850px;
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

.student-cell span:last-child,
.habit-progress span,
.validation-numbers span {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 9.5px;
}

.habit-progress strong,
.validation-numbers strong {
  display: block;
  color: #1e293b;
  font-size: 14px;
  font-weight: 900;
}

.action-column {
  text-align: right;
}

.detail-button {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 11px;
  border: 1px solid #bfdbfe;
  border-radius: 9px;
  background: #eff6ff;
  color: #1686d1;
  font: inherit;
  font-size: 10.5px;
  font-weight: 800;
  cursor: pointer;
}

.detail-button:disabled {
  border-color: #e2e8f0;
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.empty-value {
  color: #cbd5e1;
}

.table-state {
  min-height: 250px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 30px;
  color: #64748b;
  text-align: center;
}

.table-state strong {
  color: #334155;
  font-size: 14px;
}

.table-state p {
  max-width: 430px;
  margin: 0;
  color: #94a3b8;
  font-size: 11.5px;
  line-height: 1.6;
}

.loader {
  width: 25px;
  height: 25px;
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
  .filter-card,
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 700px) {
  .page-banner {
    align-items: flex-start;
    flex-direction: column;
    padding: 24px;
  }

  .refresh-button {
    width: 100%;
    justify-content: center;
  }

  .filter-card,
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
