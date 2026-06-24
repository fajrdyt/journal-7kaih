<template>
  <section class="page-stack">
    <div class="section-head">
      <div>
        <p class="kicker">Admin</p>
        <h2>Data Pengguna</h2>
        <p>Kelola akun siswa, guru, orang tua, dan admin.</p>
      </div>

      <div class="head-actions">
        <button class="secondary-button" @click="loadUsers">Refresh</button>
        <button class="primary-button" @click="openCreateForm">Tambah User</button>
      </div>
    </div>

    <div v-if="showForm" class="form-card">
      <div class="form-head">
        <div>
          <h3>{{ editingUser ? 'Edit User' : 'Tambah User' }}</h3>
          <p>{{ editingUser ? 'Perbarui data pengguna.' : 'Tambahkan pengguna baru ke sistem.' }}</p>
        </div>
        <button class="ghost-button" @click="closeForm">Batal</button>
      </div>

      <form class="form-grid" @submit.prevent="submitUser">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input v-model="form.full_name" type="text" required />
        </div>

        <div class="form-group">
          <label>Nama Singkat</label>
          <input v-model="form.name" type="text" required />
        </div>

        <div class="form-group">
          <label>Username</label>
          <input v-model="form.username" type="text" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="form.email" type="email" />
        </div>

        <div class="form-group">
          <label>No. HP</label>
          <input v-model="form.phone" type="text" />
        </div>

        <div class="form-group">
          <label>Role</label>
          <select v-model.number="form.role_id" required>
            <option :value="null" disabled>Pilih role</option>
            <option v-for="role in roleOptions" :key="role.id" :value="role.id">
              {{ role.label }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Class ID</label>
          <input v-model="form.class_id" type="number" placeholder="Kosongkan jika bukan siswa" />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input
            v-model="form.password"
            type="password"
            :required="!editingUser"
            placeholder="Wajib saat tambah user"
          />
        </div>

        <label class="check-row">
          <input v-model="form.is_active" type="checkbox" />
          <span>Akun aktif</span>
        </label>

        <div class="form-actions">
          <button class="primary-button" type="submit" :disabled="saving">
            {{ saving ? 'Menyimpan...' : editingUser ? 'Simpan Perubahan' : 'Tambah User' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="message" class="state-card success">{{ message }}</div>
    <div v-if="loading" class="state-card">Memuat data user...</div>
    <div v-else-if="error" class="state-card error">{{ error }}</div>

    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th class="right">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>
              <strong>{{ user.full_name || user.name }}</strong>
              <small>ID: {{ user.id }}</small>
            </td>
            <td>{{ user.username || '-' }}</td>
            <td>{{ user.email || '-' }}</td>
            <td>
              <span class="badge">{{ user.role?.name || user.role || '-' }}</span>
            </td>
            <td>
              <span class="status" :class="{ inactive: user.is_active === false || user.is_active === 0 }">
                {{ user.is_active === false || user.is_active === 0 ? 'Nonaktif' : 'Aktif' }}
              </span>
            </td>
            <td class="right">
              <div class="row-actions">
                <button class="small-button" @click="openEditForm(user)">Edit</button>
                <button class="small-button" @click="resetUserPassword(user)">Reset</button>
                <button class="small-button danger" @click="deleteUser(user)">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!users.length" class="empty">Belum ada data user.</div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { userApi } from '@/api/user'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const message = ref('')
const users = ref([])
const showForm = ref(false)
const editingUser = ref(null)

const roleOptions = [
  { id: 1, label: 'Siswa' },
  { id: 2, label: 'Guru' },
  { id: 3, label: 'Orang Tua' },
  { id: 4, label: 'Admin' },
]

const form = reactive({
  name: '',
  full_name: '',
  username: '',
  email: '',
  phone: '',
  role_id: null,
  class_id: '',
  password: '',
  is_active: true,
})

onMounted(loadUsers)

async function loadUsers() {
  try {
    loading.value = true
    error.value = ''
    message.value = ''

    const response = await userApi.getUsers({ per_page: 50 })
    users.value = normalizeList(response.data.data)
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat data user.'
  } finally {
    loading.value = false
  }
}

function normalizeList(data) {
  if (Array.isArray(data)) return data
  return data?.items || data?.data || []
}

function openCreateForm() {
  editingUser.value = null
  resetForm()
  showForm.value = true
}

function openEditForm(user) {
  editingUser.value = user

  form.name = user.name || ''
  form.full_name = user.full_name || user.name || ''
  form.username = user.username || ''
  form.email = user.email || ''
  form.phone = user.phone || ''
  form.role_id = user.role_id || user.role?.id || null
  form.class_id = user.class_id || ''
  form.password = ''
  form.is_active = user.is_active === false || user.is_active === 0 ? false : true

  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingUser.value = null
  resetForm()
}

function resetForm() {
  form.name = ''
  form.full_name = ''
  form.username = ''
  form.email = ''
  form.phone = ''
  form.role_id = null
  form.class_id = ''
  form.password = ''
  form.is_active = true
}

function buildPayload() {
  const payload = {
    name: form.name,
    full_name: form.full_name,
    username: form.username,
    email: form.email || null,
    phone: form.phone || null,
    role_id: form.role_id,
    class_id: form.class_id ? Number(form.class_id) : null,
    is_active: form.is_active,
  }

  if (form.password) {
    payload.password = form.password
  }

  return payload
}

async function submitUser() {
  try {
    saving.value = true
    error.value = ''
    message.value = ''

    if (editingUser.value) {
      await userApi.updateUser(editingUser.value.id, buildPayload())
      message.value = 'Data user berhasil diperbarui.'
    } else {
      await userApi.createUser(buildPayload())
      message.value = 'User baru berhasil ditambahkan.'
    }

    closeForm()
    await loadUsers()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menyimpan data user.'
  } finally {
    saving.value = false
  }
}

async function deleteUser(user) {
  const ok = confirm(`Hapus user "${user.full_name || user.name}"?`)
  if (!ok) return

  try {
    error.value = ''
    message.value = ''

    await userApi.deleteUser(user.id)
    message.value = 'User berhasil dihapus.'
    await loadUsers()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menghapus user.'
  }
}

async function resetUserPassword(user) {
  const password = prompt(`Password baru untuk ${user.full_name || user.name}:`)
  if (!password) return

  try {
    error.value = ''
    message.value = ''

    await userApi.resetPassword(user.id, { password })
    message.value = 'Password user berhasil direset.'
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal reset password.'
  }
}
</script>

<style scoped>
.page-stack{display:flex;flex-direction:column;gap:18px}.section-head,.form-card,.table-card,.state-card{background:white;border:1px solid #e5edf6;border-radius:24px;padding:22px;box-shadow:0 12px 28px rgba(24,33,58,.06)}.section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;background:linear-gradient(135deg,#fff,#f3f8ff)}.kicker{margin:0 0 7px;color:#1976d2;font-weight:900;text-transform:uppercase;font-size:12px;letter-spacing:.08em}h2,h3{margin:0;color:#122033}.section-head p,.form-head p{margin:5px 0 0;color:#667085}.head-actions,.form-actions,.row-actions{display:flex;gap:10px;align-items:center}.primary-button,.secondary-button,.ghost-button,.small-button{border:0;border-radius:12px;font-weight:800;cursor:pointer}.primary-button{background:#1976d2;color:white;padding:11px 16px}.secondary-button{background:#eaf3ff;color:#0f4c81;padding:11px 16px}.ghost-button{background:#f1f5f9;color:#475569;padding:10px 14px}.small-button{background:#f1f5f9;color:#334155;padding:8px 10px}.small-button.danger{background:#fff1f2;color:#be123c}.form-head{display:flex;justify-content:space-between;gap:16px;margin-bottom:18px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.form-group label{display:block;margin-bottom:7px;color:#334155;font-weight:800;font-size:13px}.form-group input,.form-group select{width:100%;border:1px solid #dbe3ef;border-radius:12px;padding:11px 12px;font:inherit}.check-row{display:flex;align-items:center;gap:9px;font-weight:800;color:#334155}.form-actions{grid-column:1/-1}.table-card{overflow:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:14px;border-bottom:1px solid #e5edf6;vertical-align:middle}th{color:#667085;font-size:13px}td small{display:block;margin-top:4px;color:#94a3b8}.right{text-align:right}.badge,.status{display:inline-flex;padding:6px 10px;border-radius:999px;font-weight:800;font-size:12px}.badge{background:#eaf3ff;color:#0f4c81}.status{background:#e8fff1;color:#14834d}.status.inactive{background:#f1f5f9;color:#64748b}.empty{padding:18px;color:#667085}.state-card.error{background:#fff1f2;border-color:#fecdd3;color:#be123c}.state-card.success{background:#f0fdf4;border-color:#bbf7d0;color:#166534}@media(max-width:760px){.section-head,.form-head{flex-direction:column}.form-grid{grid-template-columns:1fr}.head-actions{width:100%;flex-wrap:wrap}.right{text-align:left}.row-actions{justify-content:flex-start;flex-wrap:wrap}}
</style>