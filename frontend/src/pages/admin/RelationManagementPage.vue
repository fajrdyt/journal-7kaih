<template>
  <section class="page-stack">
    <div class="section-head">
      <div>
        <p class="kicker">Admin</p>
        <h2>Relasi Orang Tua - Siswa</h2>
        <p>Kelola hubungan akun orang tua dengan akun siswa.</p>
      </div>

      <div class="head-actions">
        <button class="secondary-button" @click="loadRelations">Refresh</button>
        <button class="primary-button" @click="openCreateForm">Tambah Relasi</button>
      </div>
    </div>

    <div v-if="showForm" class="form-card">
      <div class="form-head">
        <div>
          <h3>{{ editingRelation ? 'Edit Relasi' : 'Tambah Relasi' }}</h3>
          <p>Masukkan ID siswa dan ID orang tua sesuai data user.</p>
        </div>
        <button class="ghost-button" @click="closeForm">Batal</button>
      </div>

      <form class="form-grid" @submit.prevent="submitRelation">
        <div class="form-group">
          <label>Student ID</label>
          <input v-model="form.student_id" type="number" required />
        </div>

        <div class="form-group">
          <label>Parent ID</label>
          <input v-model="form.parent_id" type="number" required />
        </div>

        <div class="form-group">
          <label>Tipe Relasi</label>
          <select v-model="form.relation_type" required>
            <option value="ayah">Ayah</option>
            <option value="ibu">Ibu</option>
            <option value="wali">Wali</option>
          </select>
        </div>

        <label class="check-row">
          <input v-model="form.is_active" type="checkbox" />
          <span>Relasi aktif</span>
        </label>

        <div class="form-actions">
          <button class="primary-button" type="submit" :disabled="saving">
            {{ saving ? 'Menyimpan...' : editingRelation ? 'Simpan Perubahan' : 'Tambah Relasi' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="message" class="state-card success">{{ message }}</div>
    <div v-if="loading" class="state-card">Memuat relasi...</div>
    <div v-else-if="error" class="state-card error">{{ error }}</div>

    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Siswa</th>
            <th>Orang Tua</th>
            <th>Relasi</th>
            <th>Status</th>
            <th class="right">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="relation in relations" :key="relation.id">
            <td>{{ relation.student?.full_name || relation.student?.name || relation.student_id }}</td>
            <td>{{ relation.parent?.full_name || relation.parent?.name || relation.parent_id }}</td>
            <td><span class="badge">{{ relation.relation_type || 'orang_tua' }}</span></td>
            <td>
              <span class="status" :class="{ inactive: relation.is_active === false || relation.is_active === 0 }">
                {{ relation.is_active === false || relation.is_active === 0 ? 'Nonaktif' : 'Aktif' }}
              </span>
            </td>
            <td class="right">
              <div class="row-actions">
                <button class="small-button" @click="openEditForm(relation)">Edit</button>
                <button class="small-button danger" @click="deleteRelation(relation)">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!relations.length" class="empty">Belum ada data relasi.</div>
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
const relations = ref([])
const showForm = ref(false)
const editingRelation = ref(null)

const form = reactive({
  student_id: '',
  parent_id: '',
  relation_type: 'ayah',
  is_active: true,
})

onMounted(loadRelations)

async function loadRelations() {
  try {
    loading.value = true
    error.value = ''
    message.value = ''

    const response = await userApi.getRelations({ per_page: 50 })
    relations.value = normalizeList(response.data.data)
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat relasi.'
  } finally {
    loading.value = false
  }
}

function normalizeList(data) {
  if (Array.isArray(data)) return data
  return data?.items || data?.data || []
}

function openCreateForm() {
  editingRelation.value = null
  resetForm()
  showForm.value = true
}

function openEditForm(relation) {
  editingRelation.value = relation
  form.student_id = relation.student_id || relation.student?.id || ''
  form.parent_id = relation.parent_id || relation.parent?.id || ''
  form.relation_type = relation.relation_type || 'ayah'
  form.is_active = relation.is_active === false || relation.is_active === 0 ? false : true
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingRelation.value = null
  resetForm()
}

function resetForm() {
  form.student_id = ''
  form.parent_id = ''
  form.relation_type = 'ayah'
  form.is_active = true
}

function buildPayload() {
  return {
    student_id: Number(form.student_id),
    parent_id: Number(form.parent_id),
    relation_type: form.relation_type,
    is_active: form.is_active,
  }
}

async function submitRelation() {
  try {
    saving.value = true
    error.value = ''
    message.value = ''

    if (editingRelation.value) {
      await userApi.updateRelation(editingRelation.value.id, buildPayload())
      message.value = 'Relasi berhasil diperbarui.'
    } else {
      await userApi.createRelation(buildPayload())
      message.value = 'Relasi baru berhasil ditambahkan.'
    }

    closeForm()
    await loadRelations()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menyimpan relasi.'
  } finally {
    saving.value = false
  }
}

async function deleteRelation(relation) {
  const ok = confirm('Hapus relasi ini?')
  if (!ok) return

  try {
    error.value = ''
    message.value = ''

    await userApi.deleteRelation(relation.id)
    message.value = 'Relasi berhasil dihapus.'
    await loadRelations()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menghapus relasi.'
  }
}
</script>

<style scoped>
.page-stack{display:flex;flex-direction:column;gap:18px}.section-head,.form-card,.table-card,.state-card{background:white;border:1px solid #e5edf6;border-radius:24px;padding:22px;box-shadow:0 12px 28px rgba(24,33,58,.06)}.section-head{display:flex;justify-content:space-between;gap:16px;background:linear-gradient(135deg,#fff,#f3f8ff)}.kicker{margin:0 0 7px;color:#1976d2;font-weight:900;text-transform:uppercase;font-size:12px;letter-spacing:.08em}h2,h3{margin:0;color:#122033}.section-head p,.form-head p{margin:5px 0 0;color:#667085}.head-actions,.form-actions,.row-actions{display:flex;gap:10px;align-items:center}.primary-button,.secondary-button,.ghost-button,.small-button{border:0;border-radius:12px;font-weight:800;cursor:pointer}.primary-button{background:#1976d2;color:white;padding:11px 16px}.secondary-button{background:#eaf3ff;color:#0f4c81;padding:11px 16px}.ghost-button{background:#f1f5f9;color:#475569;padding:10px 14px}.small-button{background:#f1f5f9;color:#334155;padding:8px 10px}.small-button.danger{background:#fff1f2;color:#be123c}.form-head{display:flex;justify-content:space-between;gap:16px;margin-bottom:18px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.form-group label{display:block;margin-bottom:7px;color:#334155;font-weight:800;font-size:13px}.form-group input,.form-group select{width:100%;border:1px solid #dbe3ef;border-radius:12px;padding:11px 12px;font:inherit}.check-row{display:flex;align-items:center;gap:9px;font-weight:800;color:#334155}.form-actions{grid-column:1/-1}.table-card{overflow:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:14px;border-bottom:1px solid #e5edf6}th{color:#667085;font-size:13px}.right{text-align:right}.badge,.status{display:inline-flex;padding:6px 10px;border-radius:999px;font-weight:800;font-size:12px}.badge{background:#eaf3ff;color:#0f4c81}.status{background:#e8fff1;color:#14834d}.status.inactive{background:#f1f5f9;color:#64748b}.empty{padding:18px;color:#667085}.state-card.error{background:#fff1f2;border-color:#fecdd3;color:#be123c}.state-card.success{background:#f0fdf4;border-color:#bbf7d0;color:#166534}@media(max-width:760px){.section-head,.form-head{flex-direction:column}.form-grid{grid-template-columns:1fr}.head-actions{width:100%;flex-wrap:wrap}.right{text-align:left}.row-actions{justify-content:flex-start;flex-wrap:wrap}}
</style>