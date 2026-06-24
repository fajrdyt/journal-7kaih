<template>
  <div class="user-page">
    <section class="page-header">
      <div>
        <p class="page-kicker">Administrasi Pengguna</p>
        <h2>Manajemen Pengguna</h2>
        <p>
          Kelola akun siswa, guru, orang tua, dan admin yang
          terdaftar dalam sistem.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="secondary-button"
          :disabled="loading"
          @click="refreshData"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            :class="{ rotating: loading }"
          >
            <path d="M20 12a8 8 0 1 1-2.34-5.66" />
            <path d="M20 4v6h-6" />
          </svg>

          <span>Perbarui</span>
        </button>

        <button
          type="button"
          class="primary-button"
          :disabled="loading || !roles.length"
          @click="openCreateModal"
        >
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" />
          </svg>

          <span>Tambah Pengguna</span>
        </button>
      </div>
    </section>

    <div
      v-if="message"
      class="alert success-alert"
    >
      <div>
        <strong>Berhasil</strong>
        <p>{{ message }}</p>
      </div>

      <button
        type="button"
        aria-label="Tutup pesan"
        @click="message = ''"
      >
        ×
      </button>
    </div>

    <div
      v-if="error"
      class="alert error-alert"
    >
      <div>
        <strong>Terjadi kesalahan</strong>
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
            placeholder="Cari nama, username, NISN, email, atau kelas..."
            aria-label="Cari pengguna"
          />
        </div>

        <div class="filter-group">
          <select
            v-model="roleFilter"
            aria-label="Filter role"
          >
            <option value="all">Semua Role</option>

            <option
              v-for="role in rolesForTemplate"
              :key="role.id"
              :value="normalizeRoleName(role.name)"
            >
              {{ formatRoleLabel(role.name) }}
            </option>
          </select>

          <select
            v-model="statusFilter"
            aria-label="Filter status"
          >
            <option value="all">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="panel-summary">
        <div>
          <h3>Daftar Pengguna</h3>

          <p>
            Menampilkan
            <strong>{{ rangeStart }}–{{ rangeEnd }}</strong>
            dari
            <strong>{{ filteredUsers.length }}</strong>
            pengguna
          </p>
        </div>

        <span class="total-badge">
          {{ formatNumber(users.length) }} total akun
        </span>
      </div>

      <div
        v-if="loading"
        class="loading-state"
      >
        <div
          v-for="item in 5"
          :key="item"
          class="loading-row"
        ></div>
      </div>

      <div
        v-else-if="!filteredUsers.length"
        class="empty-state"
      >
        <div class="empty-icon">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20a6 6 0 0 1 12 0" />
            <path d="M16 5.5a3 3 0 0 1 0 5.5" />
            <path d="M17 14a5 5 0 0 1 4 5" />
          </svg>
        </div>

        <strong>Pengguna tidak ditemukan</strong>

        <p>
          Ubah kata pencarian atau filter yang digunakan.
        </p>
      </div>

      <template v-else>
        <div class="desktop-table">
          <table>
            <thead>
              <tr>
                <th>Pengguna</th>
                <th>Kontak</th>
                <th>Role</th>
                <th>Kelas</th>
                <th>Status</th>
                <th class="action-column">Aksi</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="user in paginatedUsers"
                :key="user.id"
              >
                <td>
                  <div class="user-cell">
                    <div class="user-avatar">
                      {{ getInitials(user.full_name) }}
                    </div>

                    <div>
                      <strong>
                        {{ user.full_name || 'Tanpa nama' }}
                      </strong>

                      <span>
                        @{{ user.username || '-' }}
                      </span>

                      <small
                        v-if="normalizeRoleName(user.role) === 'siswa'"
                      >
                        NISN: {{ user.nisn || 'Belum diisi' }}
                      </small>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="contact-cell">
                    <span>{{ user.email || '-' }}</span>
                    <small>{{ user.phone || 'Nomor HP belum diisi' }}</small>
                  </div>
                </td>

                <td>
                  <span
                    class="role-badge"
                    :class="roleClass(user.role)"
                  >
                    {{ formatRoleLabel(user.role) }}
                  </span>
                </td>

                <td>
                  <span v-if="user.class">
                    {{ user.class.name }}
                  </span>

                  <span
                    v-else
                    class="muted-text"
                  >
                    —
                  </span>
                </td>

                <td>
                  <span
                    class="status-badge"
                    :class="{
                      inactive: !normalizeBoolean(user.is_active),
                    }"
                  >
                    {{ formatStatus(user.is_active) }}
                  </span>
                </td>

                <td class="action-column">
                  <div class="row-actions">
                    <button
                      type="button"
                      class="icon-action"
                      title="Edit pengguna"
                      aria-label="Edit pengguna"
                      @click="openEditModal(user)"
                    >
                      <svg viewBox="0 0 24 24" fill="none">
                        <path d="m4 16 10.5-10.5a2.1 2.1 0 0 1 3 3L7 19H4v-3Z" />
                        <path d="m13 7 3 3" />
                      </svg>
                    </button>

                    <button
                      type="button"
                      class="icon-action"
                      title="Reset password"
                      aria-label="Reset password"
                      @click="openResetModal(user)"
                    >
                      <svg viewBox="0 0 24 24" fill="none">
                        <rect x="5" y="10" width="14" height="10" rx="2" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                        <path d="M12 14v2" />
                      </svg>
                    </button>

                    <button
                      type="button"
                      class="icon-action danger"
                      title="Hapus pengguna"
                      aria-label="Hapus pengguna"
                      :disabled="isCurrentUser(user)"
                      @click="openDeleteModal(user)"
                    >
                      <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 7h16" />
                        <path d="M9 7V4h6v3" />
                        <path d="m7 7 1 13h8l1-13" />
                        <path d="M10 11v5M14 11v5" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mobile-list">
          <article
            v-for="user in paginatedUsers"
            :key="user.id"
            class="mobile-user-card"
          >
            <div class="mobile-user-head">
              <div class="user-cell">
                <div class="user-avatar">
                  {{ getInitials(user.full_name) }}
                </div>

                <div>
                  <strong>
                    {{ user.full_name || 'Tanpa nama' }}
                  </strong>

                  <span>
                    @{{ user.username || '-' }}
                  </span>

                  <small
                    v-if="normalizeRoleName(user.role) === 'siswa'"
                  >
                    NISN: {{ user.nisn || 'Belum diisi' }}
                  </small>
                </div>
              </div>

              <span
                class="status-badge"
                :class="{
                  inactive: !normalizeBoolean(user.is_active),
                }"
              >
                {{ formatStatus(user.is_active) }}
              </span>
            </div>

            <div class="mobile-user-info">
              <div>
                <span>Role</span>

                <strong>
                  {{ formatRoleLabel(user.role) }}
                </strong>
              </div>

              <div>
                <span>Kelas</span>

                <strong>
                  {{ user.class?.name || '—' }}
                </strong>
              </div>

              <div>
                <span>Email</span>

                <strong>
                  {{ user.email || '—' }}
                </strong>
              </div>

              <div>
                <span>No. HP</span>

                <strong>
                  {{ user.phone || '—' }}
                </strong>
              </div>

              <div
                v-if="normalizeRoleName(user.role) === 'siswa'"
              >
                <span>NISN</span>

                <strong>
                  {{ user.nisn || 'Belum diisi' }}
                </strong>
              </div>
            </div>

            <div class="mobile-actions">
              <button
                type="button"
                @click="openEditModal(user)"
              >
                Edit
              </button>

              <button
                type="button"
                @click="openResetModal(user)"
              >
                Reset Password
              </button>

              <button
                type="button"
                class="danger"
                :disabled="isCurrentUser(user)"
                @click="openDeleteModal(user)"
              >
                Hapus
              </button>
            </div>
          </article>
        </div>

        <div
          v-if="pageCount > 1"
          class="pagination"
        >
          <button
            type="button"
            :disabled="currentPage === 1"
            @click="currentPage -= 1"
          >
            <svg viewBox="0 0 24 24" fill="none">
              <path d="m15 18-6-6 6-6" />
            </svg>

            <span>Sebelumnya</span>
          </button>

          <p>
            Halaman
            <strong>{{ currentPage }}</strong>
            dari
            <strong>{{ pageCount }}</strong>
          </p>

          <button
            type="button"
            :disabled="currentPage === pageCount"
            @click="currentPage += 1"
          >
            <span>Berikutnya</span>

            <svg viewBox="0 0 24 24" fill="none">
              <path d="m9 18 6-6-6-6" />
            </svg>
          </button>
        </div>
      </template>
    </section>

    <Teleport to="body">
      <div
        v-if="showUserModal"
        class="modal-layer"
        @mousedown.self="closeUserModal"
      >
        <section
          class="modal-card form-modal"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="
            editingUser
              ? 'edit-user-title'
              : 'create-user-title'
          "
        >
          <div class="modal-header">
            <div>
              <p>{{ editingUser ? 'Edit Akun' : 'Akun Baru' }}</p>

              <h3
                :id="
                  editingUser
                    ? 'edit-user-title'
                    : 'create-user-title'
                "
              >
                {{
                  editingUser
                    ? 'Perbarui Pengguna'
                    : 'Tambah Pengguna'
                }}
              </h3>
            </div>

            <button
              type="button"
              class="close-button"
              aria-label="Tutup modal"
              @click="closeUserModal"
            >
              ×
            </button>
          </div>

          <div
            v-if="formError"
            class="modal-error"
          >
            {{ formError }}
          </div>

          <form
            class="user-form"
            @submit.prevent="submitUser"
          >
            <div class="form-group wide">
              <label for="full-name">Nama Lengkap</label>

              <input
                id="full-name"
                v-model.trim="form.full_name"
                type="text"
                autocomplete="name"
                placeholder="Masukkan nama lengkap"
                required
              />

              <small v-if="fieldError('full_name')">
                {{ fieldError('full_name') }}
              </small>
            </div>

            <div class="form-group">
              <label for="username">Username</label>

              <input
                id="username"
                v-model.trim="form.username"
                type="text"
                autocomplete="username"
                placeholder="Masukkan username"
                required
              />

              <small v-if="fieldError('username')">
                {{ fieldError('username') }}
              </small>
            </div>

            <div class="form-group">
              <label for="role">Role</label>

              <select
                id="role"
                v-model.number="form.role_id"
                required
              >
                <option
                  :value="null"
                  disabled
                >
                  Pilih role
                </option>

                <option
                  v-for="role in rolesForTemplate"
                  :key="role.id"
                  :value="role.id"
                >
                  {{ formatRoleLabel(role.name) }}
                </option>
              </select>

              <small v-if="fieldError('role_id')">
                {{ fieldError('role_id') }}
              </small>
            </div>

            <div
              v-if="isStudentRole"
              class="form-group"
            >
              <label for="nisn">NISN</label>

              <input
                id="nisn"
                :value="form.nisn"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                maxlength="10"
                pattern="[0-9]{10}"
                placeholder="10 digit NISN"
                @input="handleNisnInput"
              />

              <small v-if="fieldError('nisn')">
                {{ fieldError('nisn') }}
              </small>

              <p v-else>
                Kosongkan jika belum tersedia. Jika diisi, wajib
                10 digit dan unik.
              </p>
            </div>

            <div class="form-group">
              <label for="email">Email</label>

              <input
                id="email"
                v-model.trim="form.email"
                type="email"
                autocomplete="email"
                placeholder="nama@example.com"
              />

              <small v-if="fieldError('email')">
                {{ fieldError('email') }}
              </small>
            </div>

            <div class="form-group">
              <label for="phone">Nomor HP</label>

              <input
                id="phone"
                v-model.trim="form.phone"
                type="tel"
                autocomplete="tel"
                placeholder="08xxxxxxxxxx"
              />

              <small v-if="fieldError('phone')">
                {{ fieldError('phone') }}
              </small>
            </div>

            <div
              v-if="isStudentRole"
              class="form-group wide"
            >
              <label for="class">Kelas</label>

              <select
                id="class"
                v-model.number="form.class_id"
                required
              >
                <option
                  :value="null"
                  disabled
                >
                  Pilih kelas
                </option>

                <option
                  v-for="classItem in classes"
                  :key="classItem.id"
                  :value="classItem.id"
                >
                  {{ classItem.name }}
                  <template v-if="classItem.grade_level">
                    · Tingkat {{ classItem.grade_level }}
                  </template>
                  <template v-if="!normalizeBoolean(classItem.is_active)">
                    · Nonaktif
                  </template>
                </option>
              </select>

              <small v-if="fieldError('class_id')">
                {{ fieldError('class_id') }}
              </small>
            </div>

            <div class="form-group wide">
              <label for="password">
                {{
                  editingUser
                    ? 'Password Baru'
                    : 'Password'
                }}
              </label>

              <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                minlength="8"
                :required="!editingUser"
                :placeholder="
                  editingUser
                    ? 'Kosongkan jika tidak diubah'
                    : 'Minimal 8 karakter'
                "
              />

              <small v-if="fieldError('password')">
                {{ fieldError('password') }}
              </small>

              <p v-else-if="editingUser">
                Kosongkan apabila password tidak ingin diubah.
              </p>
            </div>

            <label class="active-checkbox">
              <input
                v-model="form.is_active"
                type="checkbox"
              />

              <span>
                <strong>Akun aktif</strong>
                <small>
                  Pengguna dapat masuk dan menggunakan sistem.
                </small>
              </span>
            </label>

            <div class="modal-actions">
              <button
                type="button"
                class="cancel-button"
                :disabled="saving"
                @click="closeUserModal"
              >
                Batal
              </button>

              <button
                type="submit"
                class="save-button"
                :disabled="saving"
              >
                {{
                  saving
                    ? 'Menyimpan...'
                    : editingUser
                      ? 'Simpan Perubahan'
                      : 'Tambah Pengguna'
                }}
              </button>
            </div>
          </form>
        </section>
      </div>

      <div
        v-if="showResetModal"
        class="modal-layer"
        @mousedown.self="closeResetModal"
      >
        <section
          class="modal-card compact-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="reset-password-title"
        >
          <div class="modal-header">
            <div>
              <p>Keamanan Akun</p>
              <h3 id="reset-password-title">
                Reset Password
              </h3>
            </div>

            <button
              type="button"
              class="close-button"
              aria-label="Tutup modal"
              @click="closeResetModal"
            >
              ×
            </button>
          </div>

          <p class="modal-description">
            Buat password baru untuk
            <strong>
              {{ selectedUser?.full_name || 'pengguna' }}
            </strong>.
          </p>

          <div
            v-if="formError"
            class="modal-error"
          >
            {{ formError }}
          </div>

          <form @submit.prevent="resetUserPassword">
            <div class="form-group">
              <label for="reset-password">
                Password Baru
              </label>

              <input
                id="reset-password"
                v-model="resetPassword"
                type="password"
                autocomplete="new-password"
                minlength="8"
                placeholder="Minimal 8 karakter"
                required
              />
            </div>

            <div class="modal-actions">
              <button
                type="button"
                class="cancel-button"
                :disabled="saving"
                @click="closeResetModal"
              >
                Batal
              </button>

              <button
                type="submit"
                class="save-button"
                :disabled="saving"
              >
                {{ saving ? 'Menyimpan...' : 'Reset Password' }}
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
        <section
          class="modal-card compact-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="delete-user-title"
        >
          <div class="danger-icon">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 9v4M12 17h.01" />
              <path d="M10.3 3.8 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z" />
            </svg>
          </div>

          <h3 id="delete-user-title">
            Hapus Pengguna?
          </h3>

          <p class="delete-description">
            Akun
            <strong>
              {{ selectedUser?.full_name || 'pengguna ini' }}
            </strong>
            akan dihapus dari sistem. Tindakan ini tidak dapat
            dibatalkan.
          </p>

          <div
            v-if="formError"
            class="modal-error"
          >
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
              class="delete-button"
              :disabled="saving"
              @click="deleteUser"
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
import { useAuthStore } from '@/stores/authStore'
import {
  formatNumber,
  formatRoleLabel,
  formatStatus,
  normalizeBoolean,
} from '@/utils/formatter'

const authStore = useAuthStore()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const message = ref('')
const formError = ref('')
const fieldErrors = ref({})

const users = ref([])
const roles = ref([])
const classes = ref([])

const search = ref('')
const roleFilter = ref('all')
const statusFilter = ref('all')
const currentPage = ref(1)
const pageSize = 8

const showUserModal = ref(false)
const showResetModal = ref(false)
const showDeleteModal = ref(false)
const editingUser = ref(null)
const selectedUser = ref(null)
const resetPassword = ref('')

const form = reactive({
  full_name: '',
  username: '',
  nisn: '',
  email: '',
  phone: '',
  role_id: null,
  class_id: null,
  password: '',
  is_active: true,
})

const normalizedRoles = computed(() => {
  const order = {
    siswa: 1,
    guru: 2,
    orang_tua: 3,
    admin: 4,
  }

  return [...roles.value].sort((first, second) => {
    return (
      (order[normalizeRoleName(first.name)] ?? 99) -
      (order[normalizeRoleName(second.name)] ?? 99)
    )
  })
})

const rolesForTemplate = computed(() => normalizedRoles.value)

const selectedRoleName = computed(() => {
  const role = roles.value.find((item) => {
    return Number(item.id) === Number(form.role_id)
  })

  return normalizeRoleName(role?.name)
})

const isStudentRole = computed(() => {
  return selectedRoleName.value === 'siswa'
})

const filteredUsers = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  return users.value.filter((user) => {
    const roleName = normalizeRoleName(user.role)
    const isActive = normalizeBoolean(user.is_active)

    const matchesSearch =
      !keyword ||
      [
        user.full_name,
        user.username,
        user.nisn,
        user.email,
        user.phone,
        user.class?.name,
        formatRoleLabel(user.role),
      ].some((value) => {
        return String(value ?? '')
          .toLowerCase()
          .includes(keyword)
      })

    const matchesRole =
      roleFilter.value === 'all' ||
      roleName === roleFilter.value

    const matchesStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'active' && isActive) ||
      (statusFilter.value === 'inactive' && !isActive)

    return matchesSearch && matchesRole && matchesStatus
  })
})

const pageCount = computed(() => {
  return Math.max(
    1,
    Math.ceil(filteredUsers.value.length / pageSize),
  )
})

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize

  return filteredUsers.value.slice(start, start + pageSize)
})

const rangeStart = computed(() => {
  if (!filteredUsers.value.length) {
    return 0
  }

  return (currentPage.value - 1) * pageSize + 1
})

const rangeEnd = computed(() => {
  return Math.min(
    currentPage.value * pageSize,
    filteredUsers.value.length,
  )
})

const anyModalOpen = computed(() => {
  return (
    showUserModal.value ||
    showResetModal.value ||
    showDeleteModal.value
  )
})

onMounted(loadPageData)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})

watch(
  [search, roleFilter, statusFilter],
  () => {
    currentPage.value = 1
  },
)

watch(pageCount, (totalPages) => {
  if (currentPage.value > totalPages) {
    currentPage.value = totalPages
  }
})

watch(isStudentRole, (studentRole) => {
  if (!studentRole) {
    form.class_id = null
  }
})

watch(anyModalOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

async function loadPageData() {
  try {
    loading.value = true
    error.value = ''

    const [loadedUsers, roleResponse, loadedClasses] =
      await Promise.all([
        fetchAllPages(userApi.getUsers),
        userApi.getRoles(),
        fetchAllPages(userApi.getClasses),
      ])

    users.value = loadedUsers
    roles.value = Array.isArray(roleResponse.data?.data)
      ? roleResponse.data.data
      : []
    classes.value = loadedClasses
  } catch (err) {
    error.value = getErrorMessage(
      err,
      'Gagal memuat data pengguna.',
    )
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  try {
    loading.value = true
    users.value = await fetchAllPages(userApi.getUsers)
  } catch (err) {
    error.value = getErrorMessage(
      err,
      'Gagal memuat data pengguna.',
    )
  } finally {
    loading.value = false
  }
}

async function refreshData() {
  message.value = ''
  await loadPageData()
}

async function fetchAllPages(fetcher) {
  const firstResponse = await fetcher({
    page: 1,
    per_page: 100,
  })

  const firstPayload = firstResponse.data?.data
  const items = normalizeItems(firstPayload)
  const totalPages = Number(
    firstPayload?.pagination?.total_pages ?? 1,
  )

  if (totalPages <= 1) {
    return items
  }

  const requests = []

  for (let page = 2; page <= totalPages; page += 1) {
    requests.push(
      fetcher({
        page,
        per_page: 100,
      }),
    )
  }

  const responses = await Promise.all(requests)

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
    teacher: 'guru',
    parent: 'orang_tua',
    orangtua: 'orang_tua',
  }

  return aliases[value] ?? value
}

function roleClass(role) {
  const classesByRole = {
    siswa: 'student',
    guru: 'teacher',
    orang_tua: 'parent',
    admin: 'admin',
  }

  return classesByRole[normalizeRoleName(role)] ?? ''
}

function openCreateModal() {
  editingUser.value = null
  clearFormState()
  resetForm()
  showUserModal.value = true
}

function openEditModal(user) {
  editingUser.value = user
  clearFormState()

  form.full_name = user.full_name ?? ''
  form.username = user.username ?? ''
  form.nisn = user.nisn ?? ''
  form.email = user.email ?? ''
  form.phone = user.phone ?? ''
  form.role_id = user.role?.id ?? null
  form.class_id = user.class?.id ?? null
  form.password = ''
  form.is_active = normalizeBoolean(user.is_active)

  showUserModal.value = true
}

function closeUserModal() {
  if (saving.value) {
    return
  }

  showUserModal.value = false
  editingUser.value = null
  clearFormState()
  resetForm()
}

function openResetModal(user) {
  selectedUser.value = user
  resetPassword.value = ''
  clearFormState()
  showResetModal.value = true
}

function closeResetModal() {
  if (saving.value) {
    return
  }

  showResetModal.value = false
  selectedUser.value = null
  resetPassword.value = ''
  clearFormState()
}

function openDeleteModal(user) {
  if (isCurrentUser(user)) {
    return
  }

  selectedUser.value = user
  clearFormState()
  showDeleteModal.value = true
}

function closeDeleteModal() {
  if (saving.value) {
    return
  }

  showDeleteModal.value = false
  selectedUser.value = null
  clearFormState()
}

function resetForm() {
  form.full_name = ''
  form.username = ''
  form.nisn = ''
  form.email = ''
  form.phone = ''
  form.role_id = null
  form.class_id = null
  form.password = ''
  form.is_active = true
}

function clearFormState() {
  formError.value = ''
  fieldErrors.value = {}
}

function handleNisnInput(event) {
  form.nisn = String(event.target.value ?? '')
    .replace(/\D/g, '')
    .slice(0, 10)

  event.target.value = form.nisn

  if (fieldErrors.value?.nisn) {
    const nextErrors = {
      ...fieldErrors.value,
    }

    delete nextErrors.nisn
    fieldErrors.value = nextErrors
  }
}

function buildPayload() {
  const payload = {
    full_name: form.full_name.trim(),
    username: form.username.trim(),
    nisn:
      isStudentRole.value && form.nisn
        ? form.nisn
        : null,
    email: form.email.trim() || null,
    phone: form.phone.trim() || null,
    role_id: Number(form.role_id),
    class_id:
      isStudentRole.value && form.class_id
        ? Number(form.class_id)
        : null,
    is_active: Boolean(form.is_active),
  }

  if (form.password) {
    payload.password = form.password
  }

  return payload
}

async function submitUser() {
  try {
    saving.value = true
    clearFormState()
    error.value = ''
    message.value = ''

    if (
      isStudentRole.value &&
      form.nisn &&
      form.nisn.length !== 10
    ) {
      fieldErrors.value = {
        ...fieldErrors.value,
        nisn: ['NISN harus terdiri dari tepat 10 digit.'],
      }
      formError.value =
        'Periksa kembali NISN siswa.'
      return
    }

    const payload = buildPayload()

    if (editingUser.value) {
      await userApi.updateUser(editingUser.value.id, payload)
      message.value = 'Data pengguna berhasil diperbarui.'
    } else {
      await userApi.createUser(payload)
      message.value = 'Pengguna baru berhasil ditambahkan.'
    }

    showUserModal.value = false
    editingUser.value = null
    resetForm()
    await loadUsers()
  } catch (err) {
    setFormError(err, 'Gagal menyimpan data pengguna.')
  } finally {
    saving.value = false
  }
}

async function resetUserPassword() {
  if (!selectedUser.value) {
    return
  }

  try {
    saving.value = true
    clearFormState()
    message.value = ''

    await userApi.resetPassword(selectedUser.value.id, {
      password: resetPassword.value,
    })

    message.value = `Password ${selectedUser.value.full_name} berhasil direset.`
    showResetModal.value = false
    selectedUser.value = null
    resetPassword.value = ''
  } catch (err) {
    setFormError(err, 'Gagal mereset password pengguna.')
  } finally {
    saving.value = false
  }
}

async function deleteUser() {
  if (!selectedUser.value) {
    return
  }

  try {
    saving.value = true
    clearFormState()
    message.value = ''

    const deletedName = selectedUser.value.full_name

    await userApi.deleteUser(selectedUser.value.id)

    message.value = `Pengguna ${deletedName} berhasil dihapus.`
    showDeleteModal.value = false
    selectedUser.value = null
    await loadUsers()
  } catch (err) {
    setFormError(err, 'Gagal menghapus pengguna.')
  } finally {
    saving.value = false
  }
}

function setFormError(err, fallback) {
  const errors = err.response?.data?.errors

  if (errors && typeof errors === 'object') {
    fieldErrors.value = errors
    formError.value = Object.values(errors)
      .flat()
      .join(' ')

    return
  }

  formError.value =
    err.response?.data?.message ??
    err.message ??
    fallback
}

function fieldError(field) {
  const value = fieldErrors.value?.[field]

  if (Array.isArray(value)) {
    return value[0]
  }

  return value ?? ''
}

function getErrorMessage(err, fallback) {
  const errors = err.response?.data?.errors

  if (errors) {
    return Object.values(errors).flat().join(' ')
  }

  return (
    err.response?.data?.message ??
    err.message ??
    fallback
  )
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

function isCurrentUser(user) {
  return Number(user.id) === Number(authStore.user?.id)
}
</script>

<style scoped>
.user-page {
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
  max-width: 620px;
  margin: 7px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.65;
}

.header-actions {
  display: flex;
  flex-shrink: 0;
  gap: 10px;
}

.primary-button,
.secondary-button {
  display: inline-flex;
  min-height: 42px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 15px;
  border: 0;
  border-radius: 12px;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease,
    box-shadow 0.18s ease;
}

.primary-button {
  background: #168ad3;
  color: #ffffff;
  box-shadow: 0 9px 20px rgba(22, 138, 211, 0.2);
}

.secondary-button {
  background: #edf7fe;
  color: #168ad3;
}

.primary-button:hover:not(:disabled),
.secondary-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.primary-button:disabled,
.secondary-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.primary-button svg,
.secondary-button svg {
  width: 17px;
  height: 17px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
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

.success-alert {
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #166534;
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
  width: min(100%, 430px);
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
.filter-group select {
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

.filter-group {
  display: flex;
  gap: 9px;
}

.filter-group select {
  min-width: 140px;
  padding: 0 34px 0 12px;
}

.search-field input:focus,
.filter-group select:focus {
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

.total-badge {
  padding: 7px 10px;
  border-radius: 999px;
  background: #edf7fe;
  color: #168ad3;
  font-size: 11px;
  font-weight: 800;
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
  padding: 14px 18px;
  border-top: 1px solid #edf2f7;
  text-align: left;
  vertical-align: middle;
}

th {
  background: #fbfdff;
  color: #64748b;
  font-size: 10.5px;
  font-weight: 900;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  white-space: nowrap;
}

td {
  color: #334155;
  font-size: 12px;
}

.user-cell {
  display: flex;
  min-width: 180px;
  align-items: center;
  gap: 11px;
}

.user-avatar {
  display: grid;
  width: 39px;
  height: 39px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 12px;
  background: #eaf6ff;
  color: #168ad3;
  font-size: 11px;
  font-weight: 900;
}

.user-cell strong,
.user-cell span,
.user-cell small {
  display: block;
  max-width: 210px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-cell strong {
  color: #1e293b;
  font-size: 12.5px;
  font-weight: 800;
}

.user-cell span {
  margin-top: 3px;
  color: #94a3b8;
  font-size: 10.5px;
}

.user-cell small {
  margin-top: 3px;
  color: #64748b;
  font-size: 9.5px;
  font-weight: 700;
}

.contact-cell span,
.contact-cell small {
  display: block;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.contact-cell small {
  margin-top: 4px;
  color: #94a3b8;
  font-size: 10.5px;
}

.role-badge,
.status-badge {
  display: inline-flex;
  min-height: 26px;
  align-items: center;
  padding: 0 9px;
  border-radius: 999px;
  font-size: 10.5px;
  font-weight: 800;
  white-space: nowrap;
}

.role-badge.student {
  background: #eaf6ff;
  color: #168ad3;
}

.role-badge.teacher {
  background: #f2edff;
  color: #7554d8;
}

.role-badge.parent {
  background: #fff4e8;
  color: #d97706;
}

.role-badge.admin {
  background: #f1f5f9;
  color: #475569;
}

.status-badge {
  background: #e9f9ef;
  color: #168c50;
}

.status-badge.inactive {
  background: #f1f5f9;
  color: #64748b;
}

.muted-text {
  color: #94a3b8;
}

.action-column {
  text-align: right;
}

.row-actions {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}

.icon-action {
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border: 0;
  border-radius: 9px;
  background: #f1f6fb;
  color: #475569;
  cursor: pointer;
  transition:
    background 0.18s ease,
    color 0.18s ease;
}

.icon-action:hover:not(:disabled) {
  background: #eaf6ff;
  color: #168ad3;
}

.icon-action.danger:hover:not(:disabled) {
  background: #fff1f2;
  color: #dc2626;
}

.icon-action:disabled {
  cursor: not-allowed;
  opacity: 0.35;
}

.icon-action svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.mobile-list {
  display: none;
}

.mobile-user-card {
  padding: 16px;
  border-top: 1px solid #edf2f7;
}

.mobile-user-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.mobile-user-info {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 16px;
  padding: 14px;
  border-radius: 14px;
  background: #f8fafc;
}

.mobile-user-info span,
.mobile-user-info strong {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mobile-user-info span {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.mobile-user-info strong {
  margin-top: 4px;
  color: #334155;
  font-size: 11.5px;
  font-weight: 800;
}

.mobile-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}

.mobile-actions button {
  min-height: 36px;
  padding: 0 11px;
  border: 0;
  border-radius: 10px;
  background: #edf7fe;
  color: #168ad3;
  font: inherit;
  font-size: 10.5px;
  font-weight: 800;
  cursor: pointer;
}

.mobile-actions button.danger {
  background: #fff1f2;
  color: #dc2626;
}

.mobile-actions button:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 16px 20px;
  border-top: 1px solid #edf2f7;
}

.pagination button {
  display: inline-flex;
  min-height: 36px;
  align-items: center;
  gap: 6px;
  padding: 0 11px;
  border: 1px solid #dfe7f0;
  border-radius: 10px;
  background: #ffffff;
  color: #475569;
  font: inherit;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.pagination button:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.pagination svg {
  width: 15px;
  height: 15px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.pagination p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.empty-state,
.loading-state {
  min-height: 320px;
}

.empty-state {
  display: grid;
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

.loading-state {
  display: grid;
  align-content: start;
  gap: 12px;
  padding: 18px 20px;
  border-top: 1px solid #edf2f7;
}

.loading-row {
  height: 56px;
  border-radius: 13px;
  background: #edf2f7;
  animation: pulse 1.2s ease-in-out infinite;
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
  width: min(100%, 660px);
  max-height: calc(100dvh - 48px);
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
}

.form-modal {
  padding: 23px;
}

.compact-modal {
  width: min(100%, 430px);
  padding: 24px;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.modal-header p {
  margin: 0 0 4px;
  color: #168ad3;
  font-size: 9.5px;
  font-weight: 900;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.modal-header h3 {
  margin: 0;
  color: #172033;
  font-size: 19px;
  font-weight: 900;
}

.close-button {
  display: grid;
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  place-items: center;
  border: 0;
  border-radius: 10px;
  background: #f1f5f9;
  color: #64748b;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}

.user-form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 15px;
}

.form-group {
  display: grid;
  gap: 7px;
}

.form-group.wide {
  grid-column: 1 / -1;
}

.form-group label {
  color: #334155;
  font-size: 11.5px;
  font-weight: 800;
}

.form-group input,
.form-group select {
  width: 100%;
  height: 43px;
  border: 1px solid #dce5ef;
  border-radius: 11px;
  outline: none;
  background: #ffffff;
  color: #1e293b;
  font: inherit;
  font-size: 12px;
  padding: 0 12px;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #7dd3fc;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
}

.form-group > small {
  color: #dc2626;
  font-size: 10.5px;
}

.form-group > p {
  margin: 0;
  color: #94a3b8;
  font-size: 10.5px;
}

.active-checkbox {
  grid-column: 1 / -1;
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
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 5px;
}

.cancel-button,
.save-button,
.delete-button {
  min-height: 40px;
  padding: 0 15px;
  border: 0;
  border-radius: 11px;
  font: inherit;
  font-size: 11.5px;
  font-weight: 800;
  cursor: pointer;
}

.cancel-button {
  background: #f1f5f9;
  color: #475569;
}

.save-button {
  background: #168ad3;
  color: #ffffff;
}

.delete-button {
  background: #dc2626;
  color: #ffffff;
}

.cancel-button:disabled,
.save-button:disabled,
.delete-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
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

.modal-description,
.delete-description {
  margin: 0 0 18px;
  color: #64748b;
  font-size: 12px;
  line-height: 1.65;
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
}

.danger-icon svg {
  width: 25px;
  height: 25px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.compact-modal > h3 {
  margin: 0 0 7px;
  color: #172033;
  font-size: 19px;
  font-weight: 900;
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

@media (max-width: 900px) {
  .page-header {
    align-items: flex-start;
  }

  .toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .search-field {
    width: 100%;
  }

  .filter-group select {
    flex: 1;
  }
}

@media (max-width: 760px) {
  .desktop-table {
    display: none;
  }

  .mobile-list {
    display: block;
  }

  .page-header {
    flex-direction: column;
    padding: 20px;
  }

  .header-actions {
    width: 100%;
  }

  .primary-button,
  .secondary-button {
    flex: 1;
  }

  .user-form {
    grid-template-columns: 1fr;
  }

  .form-group.wide,
  .active-checkbox,
  .modal-actions {
    grid-column: auto;
  }
}

@media (max-width: 540px) {
  .page-header h2 {
    font-size: 20px;
  }

  .filter-group {
    flex-direction: column;
  }

  .filter-group select {
    width: 100%;
  }

  .panel-summary {
    align-items: flex-start;
    flex-direction: column;
  }

  .mobile-user-info {
    grid-template-columns: 1fr;
  }

  .pagination button span {
    display: none;
  }

  .modal-layer {
    align-items: end;
    padding: 0;
  }

  .modal-card {
    width: 100%;
    max-height: 92dvh;
    border-radius: 22px 22px 0 0;
  }

  .form-modal,
  .compact-modal {
    padding: 20px 16px;
  }

  .modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .cancel-button,
  .save-button,
  .delete-button {
    width: 100%;
  }
}
</style>