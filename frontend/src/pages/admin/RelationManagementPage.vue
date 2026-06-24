<template>
  <div class="page-stack">
    <section class="page-header">
      <div>
        <p class="kicker">Administrasi Relasi</p>
        <h2>Relasi Orang Tua dan Siswa</h2>
        <p>
          Hubungkan akun siswa dengan akun orang tua agar proses pemantauan
          dan validasi dapat dilakukan.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="secondary-button"
          :disabled="loading"
          @click="loadPageData"
        >
          {{ loading ? 'Memuat...' : 'Perbarui' }}
        </button>

        <button
          type="button"
          class="primary-button"
          :disabled="loading || !students.length || !parents.length"
          @click="openCreateModal"
        >
          Tambah Relasi
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

    <div
      v-if="!loading && (!students.length || !parents.length)"
      class="alert warning"
    >
      <div>
        <strong>Data pengguna belum lengkap</strong>
        <p>Tambahkan akun siswa dan orang tua sebelum membuat relasi baru.</p>
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
            placeholder="Cari siswa, orang tua, username, atau kelas..."
          />
        </div>

        <div class="filters">
          <select v-model="typeFilter">
            <option value="all">Semua Hubungan</option>
            <option value="ayah">Ayah</option>
            <option value="ibu">Ibu</option>
            <option value="wali">Wali</option>
          </select>

          <select v-model="statusFilter">
            <option value="all">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="panel-summary">
        <div>
          <h3>Daftar Relasi</h3>
          <p>
            Menampilkan <strong>{{ rangeStart }}–{{ rangeEnd }}</strong>
            dari <strong>{{ filteredRelations.length }}</strong> relasi
          </p>
        </div>

        <div class="summary-badges">
          <span>{{ formatNumber(relations.length) }} total</span>
          <span class="active">{{ formatNumber(activeRelationCount) }} aktif</span>
        </div>
      </div>

      <div v-if="loading" class="loading-list">
        <div v-for="item in 5" :key="item" class="loading-item"></div>
      </div>

      <div v-else-if="!filteredRelations.length" class="empty-state">
        <strong>Relasi tidak ditemukan</strong>
        <p>Tambahkan relasi baru atau ubah filter pencarian.</p>
      </div>

      <template v-else>
        <div class="desktop-table">
          <table>
            <thead>
              <tr>
                <th>Siswa</th>
                <th>Orang Tua</th>
                <th>Hubungan</th>
                <th>Status</th>
                <th class="actions-column">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="relation in paginatedRelations"
                :key="relation.id"
              >
                <td>
                  <div class="person-cell">
                    <div class="avatar student">
                      {{ getInitials(relation.student?.full_name) }}
                    </div>
                    <div>
                      <strong>
                        {{ relation.student?.full_name || 'Siswa tidak ditemukan' }}
                      </strong>
                      <span>@{{ relation.student?.username || '-' }}</span>
                      <small>
                        {{
                          relation.student?.class?.name
                            ? `Kelas ${relation.student.class.name}`
                            : 'Kelas belum tersedia'
                        }}
                      </small>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="person-cell">
                    <div class="avatar parent">
                      {{ getInitials(relation.parent?.full_name) }}
                    </div>
                    <div>
                      <strong>
                        {{ relation.parent?.full_name || 'Orang tua tidak ditemukan' }}
                      </strong>
                      <span>@{{ relation.parent?.username || '-' }}</span>
                      <small>{{ relation.parent?.email || 'Email belum diisi' }}</small>
                    </div>
                  </div>
                </td>

                <td>
                  <span
                    class="relation-badge"
                    :class="normalizeRelationType(relation.relation_type)"
                  >
                    {{ formatRelationType(relation.relation_type) }}
                  </span>
                </td>

                <td>
                  <span
                    class="status-badge"
                    :class="{ inactive: !normalizeBoolean(relation.is_active) }"
                  >
                    {{ formatStatus(relation.is_active) }}
                  </span>
                </td>

                <td class="actions-column">
                  <div class="row-actions">
                    <button
                      type="button"
                      class="edit-button"
                      @click="openEditModal(relation)"
                    >
                      Edit
                    </button>
                    <button
                      type="button"
                      class="delete-button"
                      @click="openDeleteModal(relation)"
                    >
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mobile-list">
          <article
            v-for="relation in paginatedRelations"
            :key="relation.id"
            class="relation-card"
          >
            <div class="card-head">
              <span
                class="relation-badge"
                :class="normalizeRelationType(relation.relation_type)"
              >
                {{ formatRelationType(relation.relation_type) }}
              </span>
              <span
                class="status-badge"
                :class="{ inactive: !normalizeBoolean(relation.is_active) }"
              >
                {{ formatStatus(relation.is_active) }}
              </span>
            </div>

            <div class="mobile-person">
              <div class="avatar student">
                {{ getInitials(relation.student?.full_name) }}
              </div>
              <div>
                <span>Siswa</span>
                <strong>{{ relation.student?.full_name || 'Tidak ditemukan' }}</strong>
                <small>
                  @{{ relation.student?.username || '-' }}
                  <template v-if="relation.student?.class?.name">
                    · Kelas {{ relation.student.class.name }}
                  </template>
                </small>
              </div>
            </div>

            <div class="connection-line">
              <span></span>
              <strong>terhubung dengan</strong>
              <span></span>
            </div>

            <div class="mobile-person">
              <div class="avatar parent">
                {{ getInitials(relation.parent?.full_name) }}
              </div>
              <div>
                <span>Orang Tua</span>
                <strong>{{ relation.parent?.full_name || 'Tidak ditemukan' }}</strong>
                <small>@{{ relation.parent?.username || '-' }}</small>
              </div>
            </div>

            <div class="mobile-actions">
              <button type="button" class="edit-button" @click="openEditModal(relation)">
                Edit Relasi
              </button>
              <button type="button" class="delete-button" @click="openDeleteModal(relation)">
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
        v-if="showRelationModal"
        class="modal-layer"
        @mousedown.self="closeRelationModal"
      >
        <section class="modal-card" role="dialog" aria-modal="true">
          <div class="modal-header">
            <div>
              <p>{{ editingRelation ? 'Edit Hubungan' : 'Hubungan Baru' }}</p>
              <h3>{{ editingRelation ? 'Perbarui Relasi' : 'Tambah Relasi' }}</h3>
            </div>
            <button type="button" class="close-button" @click="closeRelationModal">×</button>
          </div>

          <div v-if="formError" class="modal-error">
            {{ formError }}
          </div>

          <form class="relation-form" @submit.prevent="submitRelation">
            <div class="form-group wide">
              <label for="student-selector">Siswa</label>
              <SearchableUserSelect
                v-model="form.student_id"
                :options="students"
                input-id="student-selector"
                context="student"
                placeholder="Cari nama, username, atau kelas siswa..."
                empty-text="Siswa tidak ditemukan"
                :disabled="saving || !students.length"
                :error="fieldError('student_id')"
              />
              <p>
                Ketik nama, username, atau kelas lalu pilih siswa dari rekomendasi.
              </p>
            </div>

            <div class="form-group wide">
              <label for="parent-selector">Orang Tua</label>
              <SearchableUserSelect
                v-model="form.parent_id"
                :options="parents"
                input-id="parent-selector"
                context="parent"
                placeholder="Cari nama, username, email, atau nomor HP..."
                empty-text="Orang tua tidak ditemukan"
                :disabled="saving || !parents.length"
                :error="fieldError('parent_id')"
              />
              <p>
                Ketik nama, username, email, atau nomor HP lalu pilih orang tua.
              </p>
            </div>

            <div class="form-group wide">
              <label for="relation-type">Jenis Hubungan</label>
              <select
                id="relation-type"
                v-model="form.relation_type"
                required
              >
                <option value="ayah">Ayah</option>
                <option value="ibu">Ibu</option>
                <option value="wali">Wali</option>
              </select>
              <small v-if="fieldError('relation_type')">
                {{ fieldError('relation_type') }}
              </small>
            </div>

            <label class="active-checkbox wide">
              <input v-model="form.is_active" type="checkbox" />
              <span>
                <strong>Relasi aktif</strong>
                <small>
                  Orang tua dapat mengakses data dan melakukan validasi untuk siswa ini.
                </small>
              </span>
            </label>

            <div class="modal-actions wide">
              <button
                type="button"
                class="cancel-button"
                :disabled="saving"
                @click="closeRelationModal"
              >
                Batal
              </button>
              <button
                type="submit"
                class="save-button"
                :disabled="saving || !form.student_id || !form.parent_id"
              >
                {{
                  saving
                    ? 'Menyimpan...'
                    : editingRelation
                      ? 'Simpan Perubahan'
                      : 'Tambah Relasi'
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
          <h3>Hapus Relasi?</h3>
          <p class="delete-copy">
            Hubungan antara
            <strong>{{ selectedRelation?.student?.full_name || 'siswa' }}</strong>
            dan
            <strong>{{ selectedRelation?.parent?.full_name || 'orang tua' }}</strong>
            akan dihapus.
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
              @click="deleteRelation"
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
const error = ref('')
const message = ref('')
const formError = ref('')
const fieldErrors = ref({})
const relations = ref([])
const students = ref([])
const parents = ref([])
const search = ref('')
const typeFilter = ref('all')
const statusFilter = ref('all')
const currentPage = ref(1)
const pageSize = 8
const showRelationModal = ref(false)
const showDeleteModal = ref(false)
const editingRelation = ref(null)
const selectedRelation = ref(null)

const form = reactive({
  student_id: null,
  parent_id: null,
  relation_type: 'ayah',
  is_active: true,
})

const filteredRelations = computed(() => {
  const keyword = normalizeText(search.value)

  return relations.value.filter((relation) => {
    const isActive = normalizeBoolean(relation.is_active)
    const relationType = normalizeRelationType(relation.relation_type)

    const matchesSearch =
      !keyword ||
      [
        relation.student?.full_name,
        relation.student?.username,
        relation.student?.email,
        relation.student?.class?.name,
        relation.student?.class?.grade_level,
        relation.parent?.full_name,
        relation.parent?.username,
        relation.parent?.email,
        relation.parent?.phone,
        formatRelationType(relation.relation_type),
      ].some((value) => normalizeText(value).includes(keyword))

    const matchesType =
      typeFilter.value === 'all' || relationType === typeFilter.value

    const matchesStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'active' && isActive) ||
      (statusFilter.value === 'inactive' && !isActive)

    return matchesSearch && matchesType && matchesStatus
  })
})

const activeRelationCount = computed(() => {
  return relations.value.filter((item) => normalizeBoolean(item.is_active)).length
})

const pageCount = computed(() => {
  return Math.max(1, Math.ceil(filteredRelations.value.length / pageSize))
})

const paginatedRelations = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredRelations.value.slice(start, start + pageSize)
})

const rangeStart = computed(() => {
  return filteredRelations.value.length
    ? (currentPage.value - 1) * pageSize + 1
    : 0
})

const rangeEnd = computed(() => {
  return Math.min(
    currentPage.value * pageSize,
    filteredRelations.value.length,
  )
})

const anyModalOpen = computed(() => {
  return showRelationModal.value || showDeleteModal.value
})

onMounted(loadPageData)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})

watch([search, typeFilter, statusFilter], () => {
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

    const [loadedRelations, loadedUsers] = await Promise.all([
      fetchAllPages(userApi.getRelations),
      fetchAllPages(userApi.getUsers),
    ])

    relations.value = loadedRelations
    students.value = loadedUsers.filter((user) => {
      return normalizeRoleName(user.role) === 'siswa'
    })
    parents.value = loadedUsers.filter((user) => {
      return normalizeRoleName(user.role) === 'orang_tua'
    })
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal memuat data relasi.')
  } finally {
    loading.value = false
  }
}

async function loadRelations() {
  try {
    loading.value = true
    relations.value = await fetchAllPages(userApi.getRelations)
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal memuat data relasi.')
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

  const aliases = {
    student: 'siswa',
    parent: 'orang_tua',
    orangtua: 'orang_tua',
  }

  return aliases[value] ?? value
}

function normalizeText(value) {
  return String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
}

function normalizeRelationType(value) {
  return String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')
}

function formatRelationType(value) {
  const labels = {
    ayah: 'Ayah',
    ibu: 'Ibu',
    wali: 'Wali',
  }

  const normalized = normalizeRelationType(value)
  return labels[normalized] ?? value ?? '-'
}

function openCreateModal() {
  editingRelation.value = null
  resetForm()
  clearFormState()
  showRelationModal.value = true
}

function openEditModal(relation) {
  editingRelation.value = relation
  clearFormState()
  form.student_id =
    Number(relation.student_id ?? relation.student?.id ?? 0) || null
  form.parent_id =
    Number(relation.parent_id ?? relation.parent?.id ?? 0) || null
  form.relation_type = normalizeRelationType(relation.relation_type) || 'ayah'
  form.is_active = normalizeBoolean(relation.is_active)
  showRelationModal.value = true
}

function closeRelationModal() {
  if (saving.value) {
    return
  }

  showRelationModal.value = false
  editingRelation.value = null
  resetForm()
  clearFormState()
}

function openDeleteModal(relation) {
  selectedRelation.value = relation
  clearFormState()
  showDeleteModal.value = true
}

function closeDeleteModal() {
  if (saving.value) {
    return
  }

  showDeleteModal.value = false
  selectedRelation.value = null
  clearFormState()
}

function resetForm() {
  form.student_id = null
  form.parent_id = null
  form.relation_type = 'ayah'
  form.is_active = true
}

function clearFormState() {
  formError.value = ''
  fieldErrors.value = {}
}

async function submitRelation() {
  if (!form.student_id || !form.parent_id) {
    formError.value = 'Pilih siswa dan orang tua terlebih dahulu.'
    return
  }

  try {
    saving.value = true
    clearFormState()
    message.value = ''

    const payload = {
      student_id: Number(form.student_id),
      parent_id: Number(form.parent_id),
      relation_type: form.relation_type,
      is_active: Boolean(form.is_active),
    }

    if (editingRelation.value) {
      await userApi.updateRelation(editingRelation.value.id, payload)
      message.value = 'Data relasi berhasil diperbarui.'
    } else {
      await userApi.createRelation(payload)
      message.value = 'Relasi baru berhasil ditambahkan.'
    }

    showRelationModal.value = false
    editingRelation.value = null
    resetForm()
    await loadRelations()
  } catch (err) {
    setFormError(err, 'Gagal menyimpan data relasi.')
  } finally {
    saving.value = false
  }
}

async function deleteRelation() {
  if (!selectedRelation.value) {
    return
  }

  try {
    saving.value = true
    clearFormState()
    await userApi.deleteRelation(selectedRelation.value.id)
    message.value = 'Relasi berhasil dihapus.'
    showDeleteModal.value = false
    selectedRelation.value = null
    await loadRelations()
  } catch (err) {
    setFormError(err, 'Gagal menghapus relasi.')
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
    return 'U'
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
  max-width: 650px;
  margin: 7px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.65;
}

.header-actions,
.modal-actions,
.row-actions,
.mobile-actions,
.summary-badges,
.filters {
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
  width: min(100%, 460px);
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
.filters select,
.form-group select {
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

.filters select {
  min-width: 145px;
  padding: 0 34px 0 12px;
}

.search-field input:focus,
.filters select:focus,
.form-group select:focus {
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

.desktop-table {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 15px 18px;
  border-top: 1px solid #edf2f7;
  text-align: left;
  vertical-align: middle;
}

th {
  background: #fbfdff;
  color: #64748b;
  font-size: 10.5px;
  font-weight: 900;
  text-transform: uppercase;
  white-space: nowrap;
}

td {
  color: #334155;
  font-size: 12px;
}

.person-cell,
.mobile-person {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 11px;
}

.person-cell {
  min-width: 210px;
}

.avatar {
  display: grid;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 13px;
  font-size: 10.5px;
  font-weight: 900;
}

.avatar.student {
  background: #eaf6ff;
  color: #168ad3;
}

.avatar.parent {
  background: #fff4e8;
  color: #d97706;
}

.person-cell div:last-child,
.mobile-person div:last-child {
  min-width: 0;
}

.person-cell strong,
.person-cell span,
.person-cell small,
.mobile-person strong,
.mobile-person span,
.mobile-person small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.person-cell strong,
.mobile-person strong {
  color: #1e293b;
  font-size: 12px;
  font-weight: 800;
}

.person-cell span,
.mobile-person span,
.person-cell small,
.mobile-person small {
  margin-top: 3px;
  color: #94a3b8;
  font-size: 10px;
}

.relation-badge,
.status-badge {
  display: inline-flex;
  min-height: 27px;
  align-items: center;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 10.5px;
  font-weight: 800;
  white-space: nowrap;
}

.relation-badge.ayah {
  background: #eaf6ff;
  color: #168ad3;
}

.relation-badge.ibu {
  background: #fceef8;
  color: #c2418c;
}

.relation-badge.wali {
  background: #f2edff;
  color: #7554d8;
}

.status-badge {
  background: #e9f9ef;
  color: #168c50;
}

.status-badge.inactive {
  background: #f1f5f9;
  color: #64748b;
}

.actions-column {
  text-align: right;
}

.row-actions {
  justify-content: flex-end;
}

.edit-button,
.delete-button {
  min-height: 35px;
  padding: 0 11px;
}

.mobile-list {
  display: none;
}

.relation-card {
  padding: 17px;
  border-top: 1px solid #edf2f7;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.mobile-person {
  margin-top: 16px;
}

.connection-line {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  color: #94a3b8;
}

.connection-line span {
  height: 1px;
  flex: 1;
  background: #e2e8f0;
}

.connection-line strong {
  font-size: 9px;
  text-transform: uppercase;
}

.mobile-actions {
  margin-top: 16px;
}

.mobile-actions button {
  flex: 1;
}

.loading-list {
  display: grid;
  gap: 12px;
  min-height: 360px;
  align-content: start;
  padding: 18px 20px;
  border-top: 1px solid #edf2f7;
}

.loading-item {
  height: 66px;
  border-radius: 13px;
  background: #edf2f7;
  animation: pulse 1.2s ease-in-out infinite;
}

.empty-state {
  min-height: 340px;
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
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow-y: auto;
  padding: 24px;
  background: rgba(15, 23, 42, 0.48);
}

.modal-card {
  width: min(100%, 640px);
  margin: auto 0;
  padding: 23px;
  border: 1px solid #e2e8f0;
  border-radius: 22px;
  background: #ffffff;
  box-sizing: border-box;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
}

.modal-card.compact {
  width: min(100%, 440px);
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

.relation-form {
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

.form-group select {
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
  line-height: 1.5;
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

@media (max-width: 930px) {
  .toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .search-field {
    width: 100%;
  }

  .filters select {
    flex: 1;
  }
}

@media (max-width: 760px) {
  .page-header,
  .panel-summary {
    align-items: stretch;
    flex-direction: column;
  }

  .header-actions {
    width: 100%;
  }

  .primary-button,
  .secondary-button {
    flex: 1;
  }

  .desktop-table {
    display: none;
  }

  .mobile-list {
    display: block;
  }

  .relation-form {
    grid-template-columns: 1fr;
  }

  .wide {
    grid-column: auto;
  }
}

@media (max-width: 540px) {
  .filters {
    flex-direction: column;
  }

  .filters select {
    width: 100%;
  }

  .modal-layer {
    align-items: flex-end;
    padding: 0;
  }

  .modal-card {
    width: 100%;
    max-height: 92dvh;
    overflow-y: auto;
    margin: 0;
    padding: 20px 16px;
    border-radius: 22px 22px 0 0;
  }

  .modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
}
</style>
