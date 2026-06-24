<template>
  <section class="page-stack">
    <div class="section-head">
      <div>
        <p class="kicker">Admin</p>
        <h2>Data Kelas</h2>
        <p>Kelola kelas dan guru yang terhubung.</p>
      </div>

      <div class="head-actions">
        <button class="secondary-button" @click="loadPageData">Refresh</button>
        <button class="primary-button" @click="openCreateForm">Tambah Kelas</button>
      </div>
    </div>

    <div v-if="showForm" class="form-card">
      <div class="form-head">
        <div>
          <h3>{{ editingClass ? 'Edit Kelas' : 'Tambah Kelas' }}</h3>
          <p>Pilih guru berdasarkan nama atau username. ID guru tetap dikirim otomatis ke backend.</p>
        </div>

        <button class="ghost-button" @click="closeForm">Batal</button>
      </div>

      <form class="form-grid" @submit.prevent="submitClass">
        <div class="form-group">
          <label>Nama Kelas</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Contoh: X-A"
            required
          />
        </div>

        <div class="form-group">
          <label>Grade Level</label>
          <input
            v-model="form.grade_level"
            type="text"
            placeholder="Contoh: X"
            required
          />
        </div>

        <div class="form-group wide">
          <label>Cari Guru</label>
          <input
            v-model="teacherSearch"
            type="text"
            placeholder="Cari nama atau username guru..."
          />
        </div>

        <div class="form-group wide">
          <label>Guru Pengampu</label>
          <select v-model.number="form.teacher_id" required>
            <option :value="null" disabled>
              Pilih guru
            </option>

            <option
              v-for="teacher in filteredTeachers"
              :key="teacher.id"
              :value="teacher.id"
            >
              {{ teacher.full_name || teacher.name }} (@{{ teacher.username || '-' }})
            </option>
          </select>

          <small v-if="teachers.length === 0" class="hint error-text">
            Belum ada user dengan role guru. Buat akun guru terlebih dahulu di menu Pengguna.
          </small>

          <small v-else class="hint">
            Guru yang dipilih akan dikirim sebagai teacher_id.
          </small>
        </div>

        <label class="check-row">
          <input v-model="form.is_active" type="checkbox" />
          <span>Kelas aktif</span>
        </label>

        <div class="form-actions">
          <button class="primary-button" type="submit" :disabled="saving">
            {{ saving ? 'Menyimpan...' : editingClass ? 'Simpan Perubahan' : 'Tambah Kelas' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="message" class="state-card success">{{ message }}</div>
    <div v-if="loading" class="state-card">Memuat kelas...</div>
    <div v-else-if="error" class="state-card error">{{ error }}</div>

    <div v-else class="cards">
      <article v-for="item in classes" :key="item.id" class="class-card">
        <div>
          <h3>{{ item.name }}</h3>
          <p>Grade: {{ item.grade_level || '-' }}</p>
          <p>
            Guru:
            {{ item.teacher?.full_name || item.teacher?.name || getTeacherName(item.teacher_id) || '-' }}
          </p>
        </div>

        <span class="status" :class="{ inactive: item.is_active === false || item.is_active === 0 }">
          {{ item.is_active === false || item.is_active === 0 ? 'Nonaktif' : 'Aktif' }}
        </span>

        <div class="card-actions">
          <button class="small-button" @click="openEditForm(item)">Edit</button>
          <button class="small-button danger" @click="deleteClass(item)">Hapus</button>
        </div>
      </article>

      <div v-if="!classes.length" class="state-card">Belum ada data kelas.</div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { userApi } from '@/api/user'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const message = ref('')
const classes = ref([])
const teachers = ref([])
const teacherSearch = ref('')
const showForm = ref(false)
const editingClass = ref(null)

const form = reactive({
  name: '',
  grade_level: '',
  teacher_id: null,
  is_active: true,
})

const filteredTeachers = computed(() => {
  const keyword = teacherSearch.value.toLowerCase().trim()

  if (!keyword) {
    return teachers.value
  }

  return teachers.value.filter((teacher) => {
    const name = `${teacher.full_name || ''} ${teacher.name || ''}`.toLowerCase()
    const username = `${teacher.username || ''}`.toLowerCase()
    const email = `${teacher.email || ''}`.toLowerCase()

    return name.includes(keyword) || username.includes(keyword) || email.includes(keyword)
  })
})

onMounted(loadPageData)

async function loadPageData() {
  await Promise.all([
    loadClasses(),
    loadTeachers(),
  ])
}

async function loadClasses() {
  try {
    loading.value = true
    error.value = ''
    message.value = ''

    const response = await userApi.getClasses({ per_page: 50 })
    classes.value = normalizeList(response.data.data)
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal memuat data kelas.')
  } finally {
    loading.value = false
  }
}

async function loadTeachers() {
  try {
    const response = await userApi.getUsers({ per_page: 100 })
    const users = normalizeList(response.data.data)

    teachers.value = users.filter((user) => {
      const roleName = user.role?.name || user.role
      const roleId = Number(user.role_id || user.role?.id)

      return roleName === 'guru' || roleId === 2
    })
  } catch (err) {
    console.warn('Gagal memuat daftar guru:', err.response?.data || err)
  }
}

function normalizeList(data) {
  if (Array.isArray(data)) return data
  return data?.items || data?.data || []
}

function openCreateForm() {
  editingClass.value = null
  resetForm()
  showForm.value = true
}

function openEditForm(item) {
  editingClass.value = item

  form.name = item.name || ''
  form.grade_level = item.grade_level || ''
  form.teacher_id = Number(item.teacher_id || item.teacher?.id || null)
  form.is_active = item.is_active === false || item.is_active === 0 ? false : true

  const teacher = teachers.value.find((teacherItem) => {
    return Number(teacherItem.id) === Number(form.teacher_id)
  })

  teacherSearch.value = teacher
    ? teacher.full_name || teacher.name || teacher.username || ''
    : ''

  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingClass.value = null
  resetForm()
}

function resetForm() {
  form.name = ''
  form.grade_level = ''
  form.teacher_id = null
  form.is_active = true
  teacherSearch.value = ''
}

function buildPayload() {
  return {
    name: form.name,
    grade_level: form.grade_level,
    teacher_id: Number(form.teacher_id),
    is_active: form.is_active,
  }
}

async function submitClass() {
  try {
    saving.value = true
    error.value = ''
    message.value = ''

    if (!form.teacher_id) {
      error.value = 'Pilih guru pengampu terlebih dahulu.'
      return
    }

    if (editingClass.value) {
      await userApi.updateClass(editingClass.value.id, buildPayload())
      message.value = 'Data kelas berhasil diperbarui.'
    } else {
      await userApi.createClass(buildPayload())
      message.value = 'Kelas baru berhasil ditambahkan.'
    }

    closeForm()
    await loadClasses()
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal menyimpan kelas.')
  } finally {
    saving.value = false
  }
}

async function deleteClass(item) {
  const ok = confirm(`Hapus kelas "${item.name}"?`)
  if (!ok) return

  try {
    error.value = ''
    message.value = ''

    await userApi.deleteClass(item.id)

    message.value = 'Kelas berhasil dihapus.'
    await loadClasses()
  } catch (err) {
    error.value = getErrorMessage(err, 'Gagal menghapus kelas.')
  }
}

function getTeacherName(teacherId) {
  const teacher = teachers.value.find((item) => Number(item.id) === Number(teacherId))
  return teacher ? teacher.full_name || teacher.name || teacher.username : ''
}

function getErrorMessage(err, fallback) {
  const errors = err.response?.data?.errors

  if (errors) {
    return Object.values(errors).flat().join(' ')
  }

  return err.response?.data?.message || fallback
}
</script>

<style scoped>
.page-stack {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.section-head,
.form-card,
.class-card,
.state-card {
  background: white;
  border: 1px solid #e5edf6;
  border-radius: 24px;
  padding: 22px;
  box-shadow: 0 12px 28px rgba(24, 33, 58, 0.06);
}

.section-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  background: linear-gradient(135deg, #ffffff, #f3f8ff);
}

.kicker {
  margin: 0 0 7px;
  color: #1976d2;
  font-weight: 900;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.08em;
}

h2,
h3 {
  margin: 0;
  color: #122033;
}

.section-head p,
.form-head p,
.class-card p {
  margin: 5px 0 0;
  color: #667085;
}

.head-actions,
.form-actions,
.card-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.primary-button,
.secondary-button,
.ghost-button,
.small-button {
  border: 0;
  border-radius: 12px;
  font-weight: 800;
  cursor: pointer;
}

.primary-button {
  background: #1976d2;
  color: white;
  padding: 11px 16px;
}

.secondary-button {
  background: #eaf3ff;
  color: #0f4c81;
  padding: 11px 16px;
}

.ghost-button {
  background: #f1f5f9;
  color: #475569;
  padding: 10px 14px;
}

.small-button {
  background: #f1f5f9;
  color: #334155;
  padding: 8px 10px;
}

.small-button.danger {
  background: #fff1f2;
  color: #be123c;
}

.form-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.form-group.wide {
  grid-column: 1 / -1;
}

.form-group label {
  display: block;
  margin-bottom: 7px;
  color: #334155;
  font-weight: 800;
  font-size: 13px;
}

.form-group input,
.form-group select {
  width: 100%;
  border: 1px solid #dbe3ef;
  border-radius: 12px;
  padding: 11px 12px;
  font: inherit;
}

.hint {
  display: block;
  margin-top: 7px;
  color: #64748b;
  font-size: 12.5px;
}

.error-text {
  color: #be123c;
}

.check-row {
  display: flex;
  align-items: center;
  gap: 9px;
  font-weight: 800;
  color: #334155;
}

.form-actions {
  grid-column: 1 / -1;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 14px;
}

.class-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status {
  width: max-content;
  background: #e8fff1;
  color: #14834d;
  padding: 7px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 12px;
}

.status.inactive {
  background: #f1f5f9;
  color: #64748b;
}

.state-card.error {
  background: #fff1f2;
  border-color: #fecdd3;
  color: #be123c;
}

.state-card.success {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}

@media (max-width: 760px) {
  .section-head,
  .form-head {
    flex-direction: column;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .head-actions {
    flex-wrap: wrap;
  }
}
</style>