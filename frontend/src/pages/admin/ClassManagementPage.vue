<template>
  <div class="page-stack">
    <section class="page-header">
      <div>
        <p class="kicker">Administrasi Kelas</p>
        <h2>Manajemen Kelas</h2>
        <p>Kelola nama kelas, tingkat, guru pengampu, dan status kelas.</p>
      </div>

      <div class="header-actions">
      <button
        type="button"
        class="secondary-button"
        :disabled="loading || exporting"
        @click="loadPageData"
      >
        {{ loading ? 'Memuat...' : 'Perbarui' }}
      </button>

      <button
        type="button"
        class="secondary-button"
        :disabled="exporting"
        @click="exportClasses"
      >
        {{
          exporting
            ? 'Mengunduh...'
            : 'Export Excel'
        }}
      </button>

      <button
        type="button"
        class="primary-button"
        :disabled="loading || !teachers.length"
        @click="openCreateModal"
      >
        Tambah Kelas
      </button>
    </div>
    </section>

    <div v-if="message" class="alert success">
      <div>
        <strong>Berhasil</strong>
        <p>{{ message }}</p>
      </div>
      <button type="button" aria-label="Tutup pesan" @click="message = ''">×</button>
    </div>

    <div v-if="error" class="alert error">
      <div>
        <strong>Terjadi kesalahan</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" aria-label="Tutup pesan" @click="error = ''">×</button>
    </div>

    <div v-if="!loading && !teachers.length" class="alert warning">
      <div>
        <strong>Guru belum tersedia</strong>
        <p>Buat akun dengan role guru sebelum menambahkan kelas.</p>
      </div>
      <RouterLink to="/admin/users">Buka Pengguna</RouterLink>
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
            placeholder="Cari kelas, tingkat, atau guru..."
          />
        </div>

        <select v-model="statusFilter" class="status-filter">
          <option value="all">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>
      </div>

      <div class="panel-summary">
        <div>
          <h3>Daftar Kelas</h3>
          <p>
            Menampilkan <strong>{{ rangeStart }}–{{ rangeEnd }}</strong>
            dari <strong>{{ filteredClasses.length }}</strong> kelas
          </p>
        </div>

        <div class="summary-badges">
          <span>{{ formatNumber(classes.length) }} total</span>
          <span class="active">{{ formatNumber(activeClassCount) }} aktif</span>
        </div>
      </div>

      <div v-if="loading" class="loading-grid">
        <div v-for="item in 4" :key="item" class="loading-card"></div>
      </div>

      <div v-else-if="!filteredClasses.length" class="empty-state">
        <strong>Kelas tidak ditemukan</strong>
        <p>Ubah kata pencarian atau filter status yang digunakan.</p>
      </div>

      <template v-else>
        <div class="class-grid">
          <article
            v-for="classItem in paginatedClasses"
            :key="classItem.id"
            class="class-card"
          >
            <div class="card-head">
              <div class="class-icon">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z" />
                  <path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v17h4.5A2.5 2.5 0 0 1 20 22V5.5Z" />
                </svg>
              </div>

              <span
                class="status-badge"
                :class="{ inactive: !normalizeBoolean(classItem.is_active) }"
              >
                {{ formatStatus(classItem.is_active) }}
              </span>
            </div>

            <div class="class-copy">
              <span>Tingkat {{ classItem.grade_level || '—' }}</span>
              <h4>{{ classItem.name || 'Tanpa nama' }}</h4>
            </div>

            <div class="teacher-info">
              <div class="teacher-avatar">
                {{ getInitials(classItem.teacher?.full_name) }}
              </div>
              <div>
                <span>Guru Pengampu</span>
                <strong>{{ classItem.teacher?.full_name || 'Belum ditentukan' }}</strong>
                <small v-if="classItem.teacher?.username">
                  @{{ classItem.teacher.username }}
                </small>
              </div>
            </div>

            <div class="card-actions">
              <button type="button" class="edit-button" @click="openEditModal(classItem)">
                Edit
              </button>
              <button type="button" class="delete-button" @click="openDeleteModal(classItem)">
                Hapus
              </button>
            </div>
          </article>
        </div>

        <div v-if="pageCount > 1" class="pagination">
          <button
            type="button"
            :disabled="currentPage === 1"
            @click="currentPage -= 1"
          >
            Sebelumnya
          </button>
          <span>Halaman {{ currentPage }} dari {{ pageCount }}</span>
          <button
            type="button"
            :disabled="currentPage === pageCount"
            @click="currentPage += 1"
          >
            Berikutnya
          </button>
        </div>
      </template>
    </section>

    <Teleport to="body">
      <div
        v-if="showClassModal"
        class="modal-layer"
        @mousedown.self="closeClassModal"
      >
        <section class="modal-card" role="dialog" aria-modal="true">
          <div class="modal-header">
            <div>
              <p>{{ editingClass ? 'Edit Data' : 'Kelas Baru' }}</p>
              <h3>{{ editingClass ? 'Perbarui Kelas' : 'Tambah Kelas' }}</h3>
            </div>
            <button type="button" class="close-button" @click="closeClassModal">×</button>
          </div>

          <div v-if="formError" class="modal-error">
            {{ formError }}
          </div>

          <form class="class-form" @submit.prevent="submitClass">
            <div class="form-group">
              <label for="class-name">Nama Kelas</label>
              <input
                id="class-name"
                v-model.trim="form.name"
                type="text"
                placeholder="Contoh: X-A"
                required
              />
              <small v-if="fieldError('name')">{{ fieldError('name') }}</small>
            </div>

            <div class="form-group">
              <label for="grade-level">Tingkat Kelas</label>
              <input
                id="grade-level"
                v-model.trim="form.grade_level"
                type="text"
                placeholder="Contoh: X"
                required
              />
              <small v-if="fieldError('grade_level')">
                {{ fieldError('grade_level') }}
              </small>
            </div>

            <div class="form-group wide">
              <label for="teacher-selector">Guru Pengampu</label>
              <SearchableUserSelect
                v-model="form.teacher_id"
                :options="teachers"
                input-id="teacher-selector"
                context="teacher"
                placeholder="Cari nama, username, atau email guru..."
                empty-text="Guru tidak ditemukan"
                :disabled="saving || !teachers.length"
                :error="fieldError('teacher_id')"
              />
              <p v-if="!teachers.length">Belum ada akun dengan role guru.</p>
              <p v-else>
                Ketik nama, username, atau email lalu pilih guru dari rekomendasi.
              </p>
            </div>

            <label class="active-checkbox wide">
              <input v-model="form.is_active" type="checkbox" />
              <span>
                <strong>Kelas aktif</strong>
                <small>Kelas dapat digunakan untuk penempatan siswa.</small>
              </span>
            </label>

            <div class="modal-actions wide">
              <button
                type="button"
                class="cancel-button"
                :disabled="saving"
                @click="closeClassModal"
              >
                Batal
              </button>
              <button
                type="submit"
                class="save-button"
                :disabled="saving || !form.teacher_id"
              >
                {{
                  saving
                    ? 'Menyimpan...'
                    : editingClass
                      ? 'Simpan Perubahan'
                      : 'Tambah Kelas'
                }}
              </button>
            </div>
          </form>
        </section>
      </div>

      <div
        v-if="showDeleteModal"
        class="modal-layer"
        @mousedown.self="closeDeleteModal"
      >
        <section class="modal-card compact" role="dialog" aria-modal="true">
          <div class="danger-icon">!</div>
          <h3>Hapus Kelas?</h3>
          <p class="delete-copy">
            Kelas <strong>{{ selectedClass?.name || 'ini' }}</strong> akan dihapus.
            Pastikan tidak ada siswa yang masih menggunakan kelas tersebut.
          </p>

          <div v-if="formError" class="modal-error">
            {{ formError }}
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="cancel-button"
              :disabled="saving"
              @click="closeDeleteModal"
            >
              Batal
            </button>
            <button
              type="button"
              class="confirm-delete-button"
              :disabled="saving"
              @click="deleteClass"
            >
              {{ saving ? 'Menghapus...' : 'Ya, Hapus' }}
            </button>
          </div>
        </section>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'

import { userApi } from '@/api/user'
import SearchableUserSelect from '@/components/forms/SearchableUserSelect.vue'
import {
  formatNumber,
  formatStatus,
  normalizeBoolean,
} from '@/utils/formatter'

const loading = ref(true)
const saving = ref(false)
const exporting = ref(false)
const error = ref('')
const message = ref('')
const formError = ref('')
const fieldErrors = ref({})
const classes = ref([])
const teachers = ref([])
const search = ref('')
const statusFilter = ref('all')
const currentPage = ref(1)
const pageSize = 6
const showClassModal = ref(false)
const showDeleteModal = ref(false)
const editingClass = ref(null)
const selectedClass = ref(null)

const form = reactive({
  name: '',
  grade_level: '',
  teacher_id: null,
  is_active: true,
})

const filteredClasses = computed(() => {
  const keyword = normalizeText(search.value)

  return classes.value.filter((classItem) => {
    const isActive = normalizeBoolean(classItem.is_active)
    const matchesSearch =
      !keyword ||
      [
        classItem.name,
        classItem.grade_level,
        classItem.teacher?.full_name,
        classItem.teacher?.username,
        classItem.teacher?.email,
      ].some((value) => normalizeText(value).includes(keyword))

    const matchesStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'active' && isActive) ||
      (statusFilter.value === 'inactive' && !isActive)

    return matchesSearch && matchesStatus
  })
})

const activeClassCount = computed(() => {
  return classes.value.filter((item) => normalizeBoolean(item.is_active)).length
})

const pageCount = computed(() => {
  return Math.max(1, Math.ceil(filteredClasses.value.length / pageSize))
})

const paginatedClasses = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredClasses.value.slice(start, start + pageSize)
})

const rangeStart = computed(() => {
  return filteredClasses.value.length
    ? (currentPage.value - 1) * pageSize + 1
    : 0
})

const rangeEnd = computed(() => {
  return Math.min(currentPage.value * pageSize, filteredClasses.value.length)
})

const anyModalOpen = computed(() => {
  return showClassModal.value || showDeleteModal.value
})

onMounted(loadPageData)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})

watch([search, statusFilter], () => {
  currentPage.value = 1
})

watch(pageCount, (totalPages) => {
  if (currentPage.value > totalPages) {
    currentPage.value = totalPages
  }
})

watch(anyModalOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

async function loadPageData() {
  try {
    loading.value = true
    error.value = ''

    const [loadedClasses, loadedUsers] = await Promise.all([
      fetchAllPages(userApi.getClasses),
      fetchAllPages(userApi.getUsers),
    ])

    classes.value = loadedClasses
    teachers.value = loadedUsers.filter((user) => {
      return normalizeRoleName(user.role) === 'guru'
    })
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal memuat data kelas.')
  } finally {
    loading.value = false
  }
}

async function loadClasses() {
  try {
    loading.value = true
    classes.value = await fetchAllPages(userApi.getClasses)
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal memuat data kelas.')
  } finally {
    loading.value = false
  }
}

async function fetchAllPages(fetcher) {
  const firstResponse = await fetcher({ page: 1, per_page: 100 })
  const firstPayload = firstResponse.data?.data
  const items = normalizeItems(firstPayload)
  const totalPages = Number(firstPayload?.pagination?.total_pages ?? 1)

  if (totalPages <= 1) {
    return items
  }

  const responses = await Promise.all(
    Array.from({ length: totalPages - 1 }, (_, index) => {
      return fetcher({ page: index + 2, per_page: 100 })
    }),
  )

  responses.forEach((response) => {
    items.push(...normalizeItems(response.data?.data))
  })

  return items
}

function normalizeItems(payload) {
  if (Array.isArray(payload)) {
    return [...payload]
  }

  if (Array.isArray(payload?.items)) {
    return [...payload.items]
  }

  if (Array.isArray(payload?.data)) {
    return [...payload.data]
  }

  return []
}

function normalizeRoleName(role) {
  const rawRole =
    typeof role === 'object' && role !== null
      ? role.name ?? role.code ?? ''
      : role

  const value = String(rawRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  return value === 'teacher' ? 'guru' : value
}

function normalizeText(value) {
  return String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
}

function openCreateModal() {
  editingClass.value = null
  resetForm()
  clearFormState()
  showClassModal.value = true
}

function openEditModal(classItem) {
  editingClass.value = classItem
  clearFormState()
  form.name = classItem.name ?? ''
  form.grade_level = classItem.grade_level ?? ''
  form.teacher_id =
    Number(classItem.teacher_id ?? classItem.teacher?.id ?? 0) || null
  form.is_active = normalizeBoolean(classItem.is_active)
  showClassModal.value = true
}

function closeClassModal() {
  if (saving.value) {
    return
  }

  showClassModal.value = false
  editingClass.value = null
  resetForm()
  clearFormState()
}

function openDeleteModal(classItem) {
  selectedClass.value = classItem
  clearFormState()
  showDeleteModal.value = true
}

function closeDeleteModal() {
  if (saving.value) {
    return
  }

  showDeleteModal.value = false
  selectedClass.value = null
  clearFormState()
}

function resetForm() {
  form.name = ''
  form.grade_level = ''
  form.teacher_id = null
  form.is_active = true
}

function clearFormState() {
  formError.value = ''
  fieldErrors.value = {}
}

async function exportClasses() {
  try {
    exporting.value = true
    error.value = ''
    message.value = ''

    const params =
      new URLSearchParams()

    if (
      search.value?.trim()
    ) {
      params.set(
        'search',
        search.value.trim(),
      )
    }

    if (
      statusFilter.value ===
      'active'
    ) {
      params.set(
        'is_active',
        '1',
      )
    }

    if (
      statusFilter.value ===
      'inactive'
    ) {
      params.set(
        'is_active',
        '0',
      )
    }

    const token =
      localStorage.getItem(
        'token',
      )

    const response =
      await fetch(
        `${
          import.meta.env
            .VITE_API_BASE_URL
        }/admin/exports/classes?${
          params.toString()
        }`,
        {
          method: 'GET',

          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ngrok-skip-browser-warning': 'true',
          },
        },
      )

    if (!response.ok) {
      throw new Error(
        'Gagal mengunduh file export.',
      )
    }

    const blob =
      await response.blob()

    const disposition =
      response.headers.get(
        'content-disposition',
      )

    const filename =
      disposition
        ?.match(
          /filename="?([^"]+)"?/,
        )?.[1] ??
      'export.xlsx'

    const url =
      window.URL.createObjectURL(
        blob,
      )

    const link =
      document.createElement(
        'a',
      )

    link.href = url
    link.download =
      filename

    document.body.appendChild(
      link,
    )

    link.click()

    link.remove()

    window.URL.revokeObjectURL(
      url,
    )

    message.value =
      'File export berhasil diunduh.'
  } catch (err) {
    error.value =
      err?.message ??
      'Gagal mengunduh file export.'
  } finally {
    exporting.value = false
  }
}

async function submitClass() {
  if (!form.teacher_id) {
    formError.value = 'Pilih guru pengampu terlebih dahulu.'
    return
  }

  try {
    saving.value = true
    clearFormState()
    message.value = ''

    const payload = {
      name: form.name.trim(),
      grade_level: form.grade_level.trim(),
      teacher_id: Number(form.teacher_id),
      is_active: Boolean(form.is_active),
    }

    if (editingClass.value) {
      await userApi.updateClass(editingClass.value.id, payload)
      message.value = 'Data kelas berhasil diperbarui.'
    } else {
      await userApi.createClass(payload)
      message.value = 'Kelas baru berhasil ditambahkan.'
    }

    showClassModal.value = false
    editingClass.value = null
    resetForm()
    await loadClasses()
  } catch (err) {
    setFormError(err, 'Gagal menyimpan data kelas.')
  } finally {
    saving.value = false
  }
}

async function deleteClass() {
  if (!selectedClass.value) {
    return
  }

  try {
    saving.value = true
    clearFormState()
    const deletedName = selectedClass.value.name
    await userApi.deleteClass(selectedClass.value.id)
    message.value = `Kelas ${deletedName} berhasil dihapus.`
    showDeleteModal.value = false
    selectedClass.value = null
    await loadClasses()
  } catch (err) {
    setFormError(err, 'Gagal menghapus kelas.')
  } finally {
    saving.value = false
  }
}

function setFormError(err, fallback) {
  const errors = err.response?.data?.errors

  if (errors && typeof errors === 'object') {
    fieldErrors.value = errors
    formError.value = Object.values(errors).flat().join(' ')
    return
  }

  formError.value = err.response?.data?.message ?? err.message ?? fallback
}

function fieldError(field) {
  const value = fieldErrors.value?.[field]
  return Array.isArray(value) ? value[0] : value ?? ''
}

function getErrorMessage(err, fallback) {
  const errors = err.response?.data?.errors

  if (errors) {
    return Object.values(errors).flat().join(' ')
  }

  return err.response?.data?.message ?? err.message ?? fallback
}

function getInitials(name) {
  const words = String(name ?? '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!words.length) {
    return 'G'
  }

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words.at(-1)[0]}`.toUpperCase()
}
</script>

<style scoped>
.page-stack {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 18px;
}

.page-header,
.data-panel {
  border: 1px solid #e6edf5;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.045);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 24px;
  background:
    radial-gradient(circle at 90% 15%, rgba(32, 156, 238, 0.1), transparent 30%),
    #ffffff;
}

.kicker,
.modal-header p {
  margin: 0 0 6px;
  color: #168ad3;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.page-header h2,
.modal-header h3,
.compact h3 {
  margin: 0;
  color: #172033;
  font-weight: 900;
  letter-spacing: -0.03em;
}

.page-header h2 {
  font-size: 23px;
}

.page-header p:last-child {
  max-width: 620px;
  margin: 7px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.65;
}

.header-actions,
.modal-actions,
.card-actions,
.summary-badges {
  display: flex;
  gap: 9px;
}

.primary-button,
.secondary-button,
.edit-button,
.delete-button,
.cancel-button,
.save-button,
.confirm-delete-button,
.pagination button {
  border: 0;
  border-radius: 11px;
  font: inherit;
  font-size: 11.5px;
  font-weight: 800;
  cursor: pointer;
}

.primary-button,
.secondary-button {
  min-height: 42px;
  padding: 0 15px;
}

.primary-button,
.save-button {
  background: #168ad3;
  color: #ffffff;
}

.secondary-button,
.edit-button {
  background: #edf7fe;
  color: #168ad3;
}

.delete-button,
.confirm-delete-button {
  background: #fff1f2;
  color: #dc2626;
}

.primary-button:disabled,
.secondary-button:disabled,
.cancel-button:disabled,
.save-button:disabled,
.confirm-delete-button:disabled,
.pagination button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.alert {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 15px 17px;
  border-radius: 15px;
}

.alert strong,
.alert p {
  display: block;
  margin: 0;
  font-size: 12px;
}

.alert p {
  margin-top: 4px;
  line-height: 1.5;
}

.alert button {
  border: 0;
  background: transparent;
  color: inherit;
  font-size: 21px;
  cursor: pointer;
}

.alert a {
  color: inherit;
  font-size: 11.5px;
  font-weight: 900;
}

.alert.success {
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #166534;
}

.alert.error {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.alert.warning {
  border: 1px solid #fde7bb;
  background: #fffaf0;
  color: #b45309;
}

.data-panel {
  min-width: 0;
  overflow: hidden;
}

.toolbar,
.panel-summary,
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 18px 20px;
}

.toolbar {
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
.status-filter,
.form-group input {
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
.status-filter:focus,
.form-group input:focus {
  border-color: #7dd3fc;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
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

.summary-badges span {
  padding: 7px 10px;
  border-radius: 999px;
  background: #edf7fe;
  color: #168ad3;
  font-size: 11px;
  font-weight: 800;
}

.summary-badges .active {
  background: #e9f9ef;
  color: #168c50;
}

.class-grid,
.loading-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 15px;
  padding: 0 20px 20px;
}

.class-card {
  min-width: 0;
  padding: 19px;
  border: 1px solid #e6edf5;
  border-radius: 18px;
  background: #ffffff;
}

.card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.class-icon {
  display: grid;
  width: 43px;
  height: 43px;
  place-items: center;
  border-radius: 14px;
  background: #eaf6ff;
  color: #168ad3;
}

.class-icon svg {
  width: 21px;
  height: 21px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
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

.class-copy {
  margin-top: 16px;
}

.class-copy span {
  color: #94a3b8;
  font-size: 10.5px;
  font-weight: 800;
  text-transform: uppercase;
}

.class-copy h4 {
  margin: 5px 0 0;
  color: #172033;
  font-size: 22px;
  font-weight: 900;
}

.teacher-info {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 11px;
  margin-top: 17px;
  padding: 13px;
  border-radius: 14px;
  background: #f8fafc;
}

.teacher-avatar {
  display: grid;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 12px;
  background: #f2edff;
  color: #7554d8;
  font-size: 10.5px;
  font-weight: 900;
}

.teacher-info div:last-child {
  min-width: 0;
}

.teacher-info span,
.teacher-info strong,
.teacher-info small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.teacher-info span,
.teacher-info small {
  color: #94a3b8;
  font-size: 10px;
}

.teacher-info strong {
  margin-top: 3px;
  color: #334155;
  font-size: 11.5px;
  font-weight: 800;
}

.card-actions {
  margin-top: 16px;
}

.edit-button,
.delete-button {
  min-height: 37px;
  flex: 1;
}

.loading-card {
  height: 245px;
  border-radius: 18px;
  background: #edf2f7;
  animation: pulse 1.2s ease-in-out infinite;
}

.empty-state {
  min-height: 330px;
  display: grid;
  place-items: center;
  align-content: center;
  text-align: center;
}

.empty-state strong {
  color: #334155;
  font-size: 13px;
}

.empty-state p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 11.5px;
}

.pagination {
  border-top: 1px solid #edf2f7;
}

.pagination button {
  min-height: 36px;
  padding: 0 12px;
  border: 1px solid #dfe7f0;
  background: #ffffff;
  color: #475569;
}

.pagination span {
  color: #64748b;
  font-size: 11px;
}

.modal-layer {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: grid;
  place-items: center;
  overflow-y: auto;
  padding: 24px;
  background: rgba(15, 23, 42, 0.48);
  backdrop-filter: blur(3px);
}

.modal-card {
  width: min(100%, 620px);
  max-height: calc(100dvh - 48px);
  overflow: visible;
  padding: 23px;
  border: 1px solid #e2e8f0;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
}

.modal-card.compact {
  width: min(100%, 430px);
  overflow-y: auto;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.modal-header h3,
.compact h3 {
  font-size: 19px;
}

.close-button {
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border: 0;
  border-radius: 10px;
  background: #f1f5f9;
  color: #64748b;
  font-size: 22px;
  cursor: pointer;
}

.class-form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 15px;
}

.wide {
  grid-column: 1 / -1;
}

.form-group {
  display: grid;
  gap: 7px;
}

.form-group label {
  color: #334155;
  font-size: 11.5px;
  font-weight: 800;
}

.form-group input {
  width: 100%;
  padding: 0 12px;
}

.form-group small {
  color: #dc2626;
  font-size: 10.5px;
}

.form-group p {
  margin: 0;
  color: #94a3b8;
  font-size: 10.5px;
}

.active-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 13px;
  border: 1px solid #e5edf6;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
}

.active-checkbox input {
  margin-top: 2px;
}

.active-checkbox strong,
.active-checkbox small {
  display: block;
}

.active-checkbox strong {
  color: #334155;
  font-size: 11.5px;
}

.active-checkbox small {
  margin-top: 3px;
  color: #94a3b8;
  font-size: 10.5px;
}

.modal-actions {
  justify-content: flex-end;
  margin-top: 5px;
}

.cancel-button,
.save-button,
.confirm-delete-button {
  min-height: 40px;
  padding: 0 15px;
}

.cancel-button {
  background: #f1f5f9;
  color: #475569;
}

.confirm-delete-button {
  background: #dc2626;
  color: #ffffff;
}

.modal-error {
  margin-bottom: 16px;
  padding: 12px 13px;
  border: 1px solid #fecdd3;
  border-radius: 11px;
  background: #fff1f2;
  color: #be123c;
  font-size: 11px;
  line-height: 1.5;
}

.danger-icon {
  display: grid;
  width: 52px;
  height: 52px;
  place-items: center;
  margin-bottom: 16px;
  border-radius: 16px;
  background: #fff1f2;
  color: #dc2626;
  font-size: 24px;
  font-weight: 900;
}

.delete-copy {
  margin: 8px 0 18px;
  color: #64748b;
  font-size: 12px;
  line-height: 1.65;
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

@media (max-width: 860px) {
  .class-grid,
  .loading-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .page-header,
  .toolbar,
  .panel-summary {
    align-items: stretch;
    flex-direction: column;
  }

  .header-actions,
  .search-field,
  .status-filter {
    width: 100%;
  }

  .primary-button,
  .secondary-button {
    flex: 1;
  }

  .class-form {
    grid-template-columns: 1fr;
  }

  .wide {
    grid-column: auto;
  }
}

@media (max-width: 540px) {
  .modal-layer {
    align-items: end;
    padding: 0;
  }

  .modal-card {
    width: 100%;
    max-height: 92dvh;
    overflow-y: auto;
    padding: 20px 16px;
    border-radius: 22px 22px 0 0;
  }

  .modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
}
</style>
