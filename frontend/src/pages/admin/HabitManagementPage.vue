<template>
  <div class="page">
    <div class="page-header">
      <div>
        <p class="header-kicker">Master Data</p>
        <h1>Manajemen Kebiasaan</h1>
        <p>Kelola daftar kebiasaan yang digunakan pada jurnal harian siswa.</p>
      </div>

      <button class="primary-btn" @click="openCreateForm">
        Tambah Kebiasaan
      </button>
    </div>

    <div v-if="message" class="alert success">
      {{ message }}
    </div>

    <div v-if="error" class="alert error">
      {{ error }}
    </div>

    <section v-if="showForm" class="card form-card">
      <div class="form-head">
        <div>
          <h2>{{ editingHabit ? 'Edit Kebiasaan' : 'Tambah Kebiasaan' }}</h2>
          <p>Isi kode dan nama kebiasaan.</p>
        </div>

        <button class="secondary-btn" @click="closeForm">
          Batal
        </button>
      </div>

      <form class="form-grid" @submit.prevent="submitHabit">
        <div class="form-group">
          <label>Kode</label>
          <input v-model="form.code" type="text" placeholder="Contoh: H01" required />
        </div>

        <div class="form-group">
          <label>Nama Kebiasaan</label>
          <input v-model="form.name" type="text" placeholder="Contoh: Bangun Pagi" required />
        </div>

        <label class="checkbox-row">
          <input v-model="form.is_active" type="checkbox" />
          <span>Kebiasaan aktif</span>
        </label>

        <div class="form-actions">
          <button class="primary-btn" type="submit" :disabled="saving">
            {{ saving ? 'Menyimpan...' : editingHabit ? 'Simpan Perubahan' : 'Tambah Kebiasaan' }}
          </button>
        </div>
      </form>
    </section>

    <section class="card">
      <div class="toolbar">
        <div>
          <h2>Daftar Kebiasaan</h2>
          <p>Total {{ habits.length }} kebiasaan</p>
        </div>

        <input
          v-model="search"
          type="text"
          placeholder="Cari kebiasaan..."
        />
      </div>

      <div v-if="loading" class="empty-state">
        Memuat data kebiasaan...
      </div>

      <div v-else-if="filteredHabits.length === 0" class="empty-state">
        Belum ada data kebiasaan.
      </div>

      <div v-else class="habit-grid">
        <div v-for="habit in filteredHabits" :key="habit.id" class="habit-card">
          <div class="habit-main">
            <div class="habit-code">
              {{ habit.code || 'H' + habit.id }}
            </div>

            <div>
              <h3>{{ habit.name }}</h3>
              <span :class="['status-pill', habit.is_active ? 'active' : 'inactive']">
                {{ habit.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
          </div>

          <div class="habit-actions">
            <button class="small-btn" @click="openEditForm(habit)">
              Edit
            </button>

            <button class="small-btn danger" @click="deleteHabit(habit)">
              Hapus
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { habitApi } from '@/api/habit'

const habits = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const editingHabit = ref(null)
const search = ref('')
const error = ref('')
const message = ref('')

const form = reactive({
  code: '',
  name: '',
  is_active: true,
})

const filteredHabits = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  if (!keyword) return habits.value

  return habits.value.filter((habit) => {
    return (
      String(habit.code || '').toLowerCase().includes(keyword) ||
      String(habit.name || '').toLowerCase().includes(keyword)
    )
  })
})

onMounted(() => {
  loadHabits()
})

async function loadHabits() {
  try {
    loading.value = true
    error.value = ''

    const response = await habitApi.getHabits()
    const payload = response.data.data

    habits.value = payload?.items || payload || []
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat data kebiasaan.'
  } finally {
    loading.value = false
  }
}

function openCreateForm() {
  editingHabit.value = null
  resetForm()
  showForm.value = true
  error.value = ''
  message.value = ''
}

function openEditForm(habit) {
  editingHabit.value = habit
  form.code = habit.code || ''
  form.name = habit.name || ''
  form.is_active = Boolean(habit.is_active)
  showForm.value = true
  error.value = ''
  message.value = ''
}

function closeForm() {
  showForm.value = false
  editingHabit.value = null
  resetForm()
}

function resetForm() {
  form.code = ''
  form.name = ''
  form.is_active = true
}

function buildPayload() {
  return {
    code: form.code.trim(),
    name: form.name.trim(),
    is_active: Boolean(form.is_active),
  }
}

async function submitHabit() {
  try {
    saving.value = true
    error.value = ''
    message.value = ''

    const payload = buildPayload()

    if (editingHabit.value) {
      await habitApi.updateHabit(editingHabit.value.id, payload)
      message.value = 'Data kebiasaan berhasil diperbarui.'
    } else {
      await habitApi.createHabit(payload)
      message.value = 'Kebiasaan baru berhasil ditambahkan.'
    }

    closeForm()
    await loadHabits()
  } catch (err) {
    const errors = err.response?.data?.errors

    if (err.response?.status === 404) {
      error.value = 'Endpoint CRUD kebiasaan belum tersedia di backend.'
    } else if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value =
        err.response?.data?.message ||
        'Gagal menyimpan data kebiasaan.'
    }
  } finally {
    saving.value = false
  }
}

async function deleteHabit(habit) {
  const confirmed = confirm(`Hapus kebiasaan "${habit.name}"?`)

  if (!confirmed) return

  try {
    error.value = ''
    message.value = ''

    await habitApi.deleteHabit(habit.id)

    message.value = 'Kebiasaan berhasil dihapus.'
    await loadHabits()
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = 'Endpoint hapus kebiasaan belum tersedia di backend.'
    } else {
      error.value =
        err.response?.data?.message ||
        'Gagal menghapus kebiasaan.'
    }
  }
}
</script>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-height: 100%;
  padding: 0 24px 40px;
}

.page-header {
  background: #42b0f5;
  border-radius: 0 0 20px 20px;
  padding: 28px 32px;
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
  color: #fff;
  margin: 0 -24px;
}

.header-kicker {
  margin: 0 0 6px;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.82;
}

.page-header h1 {
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.page-header p {
  margin: 6px 0 0;
  font-size: 14px;
  opacity: 0.9;
}

.card {
  background: #fff;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}

.form-card {
  border: 1px solid #dbeafe;
}

.form-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 20px;
}

.form-head h2,
.toolbar h2 {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
  font-weight: 800;
}

.form-head p,
.toolbar p {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 13.5px;
}

.form-grid {
  display: grid;
  grid-template-columns: 160px 1fr 170px;
  gap: 16px;
  align-items: end;
}

.form-group {
  display: grid;
  gap: 8px;
}

.form-group label {
  color: #334155;
  font-size: 13.5px;
  font-weight: 700;
}

.form-group input,
.toolbar input {
  width: 100%;
  height: 44px;
  border: 1px solid #dbe5f0;
  border-radius: 12px;
  padding: 0 14px;
  outline: none;
  font: inherit;
  color: #0f172a;
  background: #fff;
}

.form-group input:focus,
.toolbar input:focus {
  border-color: #42b0f5;
  box-shadow: 0 0 0 4px rgba(66, 176, 245, 0.14);
}

.checkbox-row {
  height: 44px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #334155;
  font-size: 14px;
  font-weight: 700;
}

.form-actions {
  grid-column: 1 / -1;
}

.primary-btn,
.secondary-btn,
.small-btn {
  border: 0;
  font: inherit;
  cursor: pointer;
  transition: 0.18s ease;
}

.primary-btn {
  min-height: 42px;
  padding: 0 18px;
  border-radius: 12px;
  background: #fff;
  color: #1f8fd0;
  font-weight: 800;
}

.form-actions .primary-btn {
  background: #42b0f5;
  color: #fff;
}

.primary-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.secondary-btn {
  min-height: 38px;
  padding: 0 14px;
  border-radius: 10px;
  background: #f1f5f9;
  color: #334155;
  font-weight: 700;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  margin-bottom: 20px;
}

.toolbar input {
  max-width: 280px;
}

.habit-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.habit-card {
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 18px;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  transition: 0.18s ease;
}

.habit-card:hover {
  border-color: #bfdbfe;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}

.habit-main {
  display: flex;
  align-items: center;
  gap: 14px;
}

.habit-code {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: #eaf6ff;
  color: #1f8fd0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 13px;
  flex-shrink: 0;
}

.habit-card h3 {
  margin: 0 0 7px;
  color: #0f172a;
  font-size: 15px;
  font-weight: 800;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 800;
}

.status-pill.active {
  background: #dcfce7;
  color: #15803d;
}

.status-pill.inactive {
  background: #f1f5f9;
  color: #64748b;
}

.habit-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.small-btn {
  min-height: 34px;
  padding: 0 12px;
  border-radius: 10px;
  background: #eaf6ff;
  color: #1f8fd0;
  font-size: 13px;
  font-weight: 800;
}

.small-btn.danger {
  background: #fff1f2;
  color: #be123c;
}

.alert {
  padding: 14px 16px;
  border-radius: 14px;
  font-size: 13.5px;
  font-weight: 700;
}

.alert.success {
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  color: #047857;
}

.alert.error {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  color: #be123c;
}

.empty-state {
  padding: 34px;
  text-align: center;
  color: #64748b;
  font-weight: 700;
}

@media (max-width: 960px) {
  .habit-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .toolbar,
  .page-header,
  .form-head {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar input {
    max-width: 100%;
  }
}
</style>