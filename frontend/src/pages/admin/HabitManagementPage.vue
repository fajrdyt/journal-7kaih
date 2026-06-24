<template>
  <div class="habit-page">
    <section class="page-header">
      <div>
        <p class="page-kicker">Master Kebiasaan</p>

        <h2>Daftar Kebiasaan</h2>

        <p>
          Tujuh Kebiasaan Anak Indonesia Hebat merupakan data
          tetap yang digunakan dalam seluruh proses check-in.
        </p>
      </div>

      <button
        type="button"
        class="refresh-button"
        :disabled="loading"
        @click="loadHabits"
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

    <section class="information-card">
      <div class="information-icon">
        <svg viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="9" />
          <path d="M12 11v5" />
          <path d="M12 8h.01" />
        </svg>
      </div>

      <div>
        <strong>Data kebiasaan bersifat tetap</strong>

        <p>
          Admin hanya dapat melihat daftar kebiasaan. Penambahan,
          perubahan, dan penghapusan kebiasaan tidak tersedia
          karena sistem menggunakan tujuh kebiasaan yang sudah
          ditetapkan.
        </p>
      </div>
    </section>

    <div
      v-if="error"
      class="alert error-alert"
    >
      <div>
        <strong>Gagal memuat kebiasaan</strong>
        <p>{{ error }}</p>
      </div>

      <button
        type="button"
        aria-label="Tutup pesan"
        @click="error = ''"
      >
        ×
      </button>
    </div>

    <section class="data-panel">
      <div class="toolbar">
        <div class="search-field">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>

          <input
            v-model="search"
            type="search"
            placeholder="Cari nama atau kode kebiasaan..."
            aria-label="Cari kebiasaan"
          />
        </div>

        <select
          v-model="statusFilter"
          class="status-filter"
          aria-label="Filter status kebiasaan"
        >
          <option value="all">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>
      </div>

      <div class="panel-summary">
        <div>
          <h3>Tujuh Kebiasaan Utama</h3>

          <p>
            Menampilkan
            <strong>{{ filteredHabits.length }}</strong>
            dari
            <strong>{{ habits.length }}</strong>
            kebiasaan
          </p>
        </div>

        <div class="summary-badges">
          <span>
            {{ formatNumber(habits.length) }} kebiasaan
          </span>

          <span class="active-summary">
            {{ formatNumber(activeHabitCount) }} aktif
          </span>
        </div>
      </div>

      <div
        v-if="loading"
        class="loading-grid"
      >
        <div
          v-for="item in 7"
          :key="item"
          class="loading-card"
        ></div>
      </div>

      <div
        v-else-if="!filteredHabits.length"
        class="empty-state"
      >
        <div class="empty-icon">
          <svg viewBox="0 0 24 24" fill="none">
            <rect
              x="4"
              y="4"
              width="16"
              height="16"
              rx="3"
            />
            <path d="m8 12 2.5 2.5L16 9" />
          </svg>
        </div>

        <strong>Kebiasaan tidak ditemukan</strong>

        <p>
          Ubah kata pencarian atau filter status yang digunakan.
        </p>
      </div>

      <div
        v-else
        class="habit-grid"
      >
        <article
          v-for="habit in filteredHabits"
          :key="habit.id"
          class="habit-card"
        >
          <div class="habit-card-head">
            <div class="order-badge">
              {{ habit.sort_order || '-' }}
            </div>

            <span
              class="status-badge"
              :class="{
                inactive: !normalizeBoolean(
                  habit.is_active,
                ),
              }"
            >
              {{ formatStatus(habit.is_active) }}
            </span>
          </div>

          <div class="habit-icon">
            <svg
              v-if="habitIcon(habit) === 'sun'"
              viewBox="0 0 24 24"
              fill="none"
            >
              <circle cx="12" cy="12" r="4" />
              <path d="M12 2v2M12 20v2" />
              <path d="m4.93 4.93 1.42 1.42" />
              <path d="m17.65 17.65 1.42 1.42" />
              <path d="M2 12h2M20 12h2" />
              <path d="m4.93 19.07 1.42-1.42" />
              <path d="m17.65 6.35 1.42-1.42" />
            </svg>

            <svg
              v-else-if="habitIcon(habit) === 'pray'"
              viewBox="0 0 24 24"
              fill="none"
            >
              <path d="M12 3v7" />
              <path d="m8 6 4 4 4-4" />
              <path d="M7 21v-4a5 5 0 0 1 10 0v4" />
              <path d="M4 21h16" />
            </svg>

            <svg
              v-else-if="habitIcon(habit) === 'exercise'"
              viewBox="0 0 24 24"
              fill="none"
            >
              <path d="M6 7v10M18 7v10" />
              <path d="M3 9v6M21 9v6" />
              <path d="M6 12h12" />
            </svg>

            <svg
              v-else-if="habitIcon(habit) === 'food'"
              viewBox="0 0 24 24"
              fill="none"
            >
              <path d="M7 3v8" />
              <path d="M4 3v5a3 3 0 0 0 6 0V3" />
              <path d="M7 11v10" />
              <path d="M16 3v18" />
              <path d="M16 3c3 2 4 5 4 8h-4" />
            </svg>

            <svg
              v-else-if="habitIcon(habit) === 'learn'"
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

            <svg
              v-else-if="habitIcon(habit) === 'community'"
              viewBox="0 0 24 24"
              fill="none"
            >
              <circle cx="9" cy="8" r="3" />
              <path d="M3 20a6 6 0 0 1 12 0" />
              <circle cx="17" cy="9" r="2.5" />
              <path d="M15 15a5 5 0 0 1 6 5" />
            </svg>

            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
            >
              <path d="M6 4h12v16H6z" />
              <path d="M9 8h6M9 12h6" />
              <path d="M9 16h3" />
            </svg>
          </div>

          <div class="habit-content">
            <span class="habit-code">
              {{ habit.code || '-' }}
            </span>

            <h4>
              {{ habit.name || 'Tanpa nama' }}
            </h4>

            <p>
              {{ habitDescription(habit) }}
            </p>
          </div>

          <div class="habit-footer">
            <span>ID Kebiasaan</span>
            <strong>#{{ habit.id }}</strong>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref,
} from 'vue'

import { habitApi } from '@/api/habit'
import {
  formatNumber,
  formatStatus,
  normalizeBoolean,
} from '@/utils/formatter'

const habits = ref([])
const loading = ref(true)
const error = ref('')

const search = ref('')
const statusFilter = ref('all')

const filteredHabits = computed(() => {
  const keyword = search.value
    .trim()
    .toLowerCase()

  return [...habits.value]
    .filter((habit) => {
      const isActive = normalizeBoolean(
        habit.is_active,
      )

      const matchesSearch =
        !keyword ||
        [
          habit.name,
          habit.code,
          habit.sort_order,
          habit.id,
        ].some((value) => {
          return String(value ?? '')
            .toLowerCase()
            .includes(keyword)
        })

      const matchesStatus =
        statusFilter.value === 'all' ||
        (
          statusFilter.value === 'active' &&
          isActive
        ) ||
        (
          statusFilter.value === 'inactive' &&
          !isActive
        )

      return matchesSearch && matchesStatus
    })
    .sort((first, second) => {
      return (
        Number(first.sort_order ?? 0) -
        Number(second.sort_order ?? 0)
      )
    })
})

const activeHabitCount = computed(() => {
  return habits.value.filter((habit) => {
    return normalizeBoolean(habit.is_active)
  }).length
})

onMounted(loadHabits)

async function loadHabits() {
  try {
    loading.value = true
    error.value = ''

    const response = await habitApi.getHabits()
    const payload = response.data?.data ?? response.data

    habits.value = normalizeItems(payload)
  } catch (err) {
    error.value =
      err.response?.data?.message ??
      err.message ??
      'Terjadi kesalahan saat mengambil daftar kebiasaan.'
  } finally {
    loading.value = false
  }
}

function normalizeItems(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (Array.isArray(payload?.items)) {
    return payload.items
  }

  if (Array.isArray(payload?.data)) {
    return payload.data
  }

  return []
}

function normalizeHabitCode(habit) {
  return String(
    habit.code ?? habit.name ?? '',
  )
    .trim()
    .toUpperCase()
    .replace(/[\s-]+/g, '_')
}

function habitIcon(habit) {
  const code = normalizeHabitCode(habit)

  const icons = {
    BANGUN_PAGI: 'sun',
    BERIBADAH: 'pray',
    BEROLAHRAGA: 'exercise',
    MAKAN_SEHAT_DAN_BERGIZI: 'food',
    GEMAR_BELAJAR: 'learn',
    BERMASYARAKAT: 'community',
    TIDUR_CEPAT: 'sleep',
  }

  return icons[code] ?? 'default'
}

function habitDescription(habit) {
  const code = normalizeHabitCode(habit)

  const descriptions = {
    BANGUN_PAGI:
      'Membiasakan diri memulai hari lebih awal dan teratur.',
    BERIBADAH:
      'Menjalankan ibadah sesuai agama dan keyakinan masing-masing.',
    BEROLAHRAGA:
      'Menjaga kesehatan tubuh melalui aktivitas fisik yang teratur.',
    MAKAN_SEHAT_DAN_BERGIZI:
      'Mengonsumsi makanan sehat, seimbang, dan bergizi.',
    GEMAR_BELAJAR:
      'Membangun kebiasaan belajar dan meningkatkan pengetahuan.',
    BERMASYARAKAT:
      'Berinteraksi, bekerja sama, dan peduli terhadap lingkungan.',
    TIDUR_CEPAT:
      'Menjaga pola istirahat yang cukup dan teratur.',
  }

  return (
    descriptions[code] ??
    'Kebiasaan utama dalam program 7KAIH.'
  )
}
</script>

<style scoped>
.habit-page {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 18px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 24px;
  border: 1px solid #e6edf5;
  border-radius: 22px;
  background:
    radial-gradient(
      circle at 90% 15%,
      rgba(32, 156, 238, 0.1),
      transparent 30%
    ),
    #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.045);
}

.page-kicker {
  margin: 0 0 6px;
  color: #168ad3;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.page-header h2 {
  margin: 0;
  color: #172033;
  font-size: 23px;
  font-weight: 900;
  letter-spacing: -0.035em;
}

.page-header p:last-child {
  max-width: 650px;
  margin: 7px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.65;
}

.refresh-button {
  display: inline-flex;
  min-height: 42px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 15px;
  border: 0;
  border-radius: 12px;
  background: #edf7fe;
  color: #168ad3;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease;
}

.refresh-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #e0f2fe;
}

.refresh-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.refresh-button svg {
  width: 17px;
  height: 17px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.information-card {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  padding: 16px 18px;
  border: 1px solid #bae6fd;
  border-radius: 16px;
  background: #f0f9ff;
  color: #075985;
}

.information-icon {
  display: grid;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 12px;
  background: #e0f2fe;
}

.information-icon svg {
  width: 19px;
  height: 19px;
  stroke: currentColor;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.information-card strong {
  display: block;
  font-size: 12.5px;
}

.information-card p {
  margin: 5px 0 0;
  font-size: 11.5px;
  line-height: 1.6;
}

.alert {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 15px 17px;
  border-radius: 15px;
}

.alert strong {
  display: block;
  font-size: 12px;
}

.alert p {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 1.5;
}

.alert button {
  border: 0;
  background: transparent;
  color: inherit;
  font-size: 21px;
  line-height: 1;
  cursor: pointer;
}

.error-alert {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.data-panel {
  min-width: 0;
  overflow: hidden;
  border: 1px solid #e6edf5;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.045);
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1px solid #edf2f7;
}

.search-field {
  position: relative;
  width: min(100%, 470px);
}

.search-field svg {
  position: absolute;
  top: 50%;
  left: 13px;
  width: 18px;
  height: 18px;
  transform: translateY(-50%);
  stroke: #94a3b8;
  stroke-width: 1.8;
  stroke-linecap: round;
}

.search-field input,
.status-filter {
  height: 42px;
  border: 1px solid #dfe7f0;
  border-radius: 11px;
  outline: none;
  background: #ffffff;
  color: #1e293b;
  font: inherit;
  font-size: 12px;
}

.search-field input {
  width: 100%;
  padding: 0 14px 0 42px;
}

.status-filter {
  min-width: 155px;
  padding: 0 34px 0 12px;
}

.search-field input:focus,
.status-filter:focus {
  border-color: #7dd3fc;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
}

.panel-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 17px 20px;
}

.panel-summary h3 {
  margin: 0;
  color: #1e293b;
  font-size: 15px;
  font-weight: 900;
}

.panel-summary p {
  margin: 4px 0 0;
  color: #94a3b8;
  font-size: 11.5px;
}

.summary-badges {
  display: flex;
  gap: 8px;
}

.summary-badges span {
  padding: 7px 10px;
  border-radius: 999px;
  background: #edf7fe;
  color: #168ad3;
  font-size: 11px;
  font-weight: 800;
}

.summary-badges .active-summary {
  background: #e9f9ef;
  color: #168c50;
}

.habit-grid,
.loading-grid {
  display: grid;
  grid-template-columns: repeat(
    3,
    minmax(0, 1fr)
  );
  gap: 15px;
  padding: 0 20px 20px;
}

.habit-card {
  display: flex;
  min-width: 0;
  min-height: 280px;
  flex-direction: column;
  padding: 19px;
  border: 1px solid #e6edf5;
  border-radius: 18px;
  background: #ffffff;
  transition:
    border-color 0.18s ease,
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

.habit-card:hover {
  transform: translateY(-2px);
  border-color: #bae6fd;
  box-shadow: 0 13px 30px rgba(15, 23, 42, 0.07);
}

.habit-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.order-badge {
  display: grid;
  width: 31px;
  height: 31px;
  place-items: center;
  border-radius: 10px;
  background: #172033;
  color: #ffffff;
  font-size: 11px;
  font-weight: 900;
}

.status-badge {
  display: inline-flex;
  min-height: 26px;
  align-items: center;
  padding: 0 9px;
  border-radius: 999px;
  background: #e9f9ef;
  color: #168c50;
  font-size: 10.5px;
  font-weight: 800;
}

.status-badge.inactive {
  background: #f1f5f9;
  color: #64748b;
}

.habit-icon {
  display: grid;
  width: 50px;
  height: 50px;
  margin-top: 20px;
  place-items: center;
  border-radius: 16px;
  background: #eaf6ff;
  color: #168ad3;
}

.habit-icon svg {
  width: 24px;
  height: 24px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.habit-content {
  margin-top: 16px;
}

.habit-code {
  display: block;
  overflow: hidden;
  color: #94a3b8;
  font-size: 9.5px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-overflow: ellipsis;
  text-transform: uppercase;
  white-space: nowrap;
}

.habit-content h4 {
  margin: 6px 0 0;
  color: #172033;
  font-size: 17px;
  font-weight: 900;
  letter-spacing: -0.025em;
}

.habit-content p {
  margin: 8px 0 0;
  color: #64748b;
  font-size: 11.5px;
  line-height: 1.65;
}

.habit-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: auto;
  padding-top: 17px;
  border-top: 1px solid #edf2f7;
}

.habit-footer span {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.habit-footer strong {
  color: #475569;
  font-size: 10.5px;
  font-weight: 900;
}

.loading-card {
  height: 280px;
  border-radius: 18px;
  background: #edf2f7;
  animation: pulse 1.2s ease-in-out infinite;
}

.empty-state {
  display: grid;
  min-height: 340px;
  place-items: center;
  align-content: center;
  padding: 32px;
  text-align: center;
}

.empty-icon {
  display: grid;
  width: 54px;
  height: 54px;
  place-items: center;
  border-radius: 17px;
  background: #f1f5f9;
  color: #94a3b8;
}

.empty-icon svg {
  width: 25px;
  height: 25px;
  stroke: currentColor;
  stroke-width: 1.7;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.empty-state strong {
  margin-top: 13px;
  color: #334155;
  font-size: 13px;
}

.empty-state p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 11.5px;
}

.rotating {
  animation: rotate 0.8s linear infinite;
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

@media (max-width: 1050px) {
  .habit-grid,
  .loading-grid {
    grid-template-columns: repeat(
      2,
      minmax(0, 1fr)
    );
  }
}

@media (max-width: 680px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
    padding: 20px;
  }

  .refresh-button {
    width: 100%;
  }

  .toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .search-field,
  .status-filter {
    width: 100%;
  }

  .panel-summary {
    align-items: flex-start;
    flex-direction: column;
  }

  .habit-grid,
  .loading-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .page-header h2 {
    font-size: 20px;
  }

  .information-card {
    padding: 14px;
  }

  .panel-summary,
  .toolbar {
    padding-right: 14px;
    padding-left: 14px;
  }

  .habit-grid,
  .loading-grid {
    padding-right: 14px;
    padding-left: 14px;
  }

  .habit-card {
    min-height: 260px;
  }
}
</style>