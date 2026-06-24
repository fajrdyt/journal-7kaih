<template>
  <section class="student-dashboard">
    <div class="hero-card">
      <div>
        <p class="eyebrow">Dashboard Siswa</p>
        <h2>Halo, {{ studentName }}</h2>
        <p>
          Isi jurnal kebiasaan hari ini dan pantau perkembanganmu secara rutin.
        </p>
      </div>

      <div class="date-card">
        <span>{{ todayLabel }}</span>
        <strong>{{ checkinStatus }}</strong>
      </div>
    </div>

    <div v-if="loading" class="state-card">
      Memuat data check-in...
    </div>

    <div v-else-if="error" class="state-card error">
      {{ error }}
    </div>

    <template v-else>
      <div v-if="todayCheckin" class="summary-grid">
        <div class="summary-card">
          <span>Total Kebiasaan</span>
          <strong>{{ summary.total }}</strong>
        </div>

        <div class="summary-card">
          <span>Sudah Dilakukan</span>
          <strong>{{ summary.done }}</strong>
        </div>

        <div class="summary-card">
          <span>Belum Dilakukan</span>
          <strong>{{ summary.notDone }}</strong>
        </div>

        <div class="summary-card">
          <span>Validasi</span>
          <strong>{{ summary.validations }}</strong>
        </div>
      </div>

      <div class="main-card">
        <div class="card-header">
          <div>
            <h3>Check-in Kebiasaan Hari Ini</h3>
            <p>
              Centang kebiasaan yang sudah kamu lakukan hari ini.
            </p>
          </div>

          <button
            v-if="todayCheckin"
            class="secondary-button"
            @click="enableEdit"
          >
            Ubah Check-in
          </button>
        </div>

        <div v-if="todayCheckin && !editing" class="done-state">
          <div class="done-icon">✓</div>
          <div>
            <h4>Check-in hari ini sudah tersimpan.</h4>
            <p>
              Kamu masih bisa mengubah data hari ini dengan menekan tombol
              “Ubah Check-in”.
            </p>
          </div>
        </div>

        <form v-else @submit.prevent="submitCheckin">
          <div v-if="habits.length === 0" class="state-card">
            Data kebiasaan belum tersedia.
          </div>

          <div v-else class="habit-list">
            <label
              v-for="habit in habits"
              :key="habit.id"
              class="habit-item"
              :class="{ checked: form.items[habit.id] }"
            >
              <input
                v-model="form.items[habit.id]"
                type="checkbox"
              />

              <span class="habit-check"></span>

              <div>
                <strong>{{ habit.name }}</strong>
                <small v-if="habit.code">{{ habit.code }}</small>
              </div>
            </label>
          </div>

          <div class="notes-field">
            <label for="notes">Catatan hari ini</label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="4"
              placeholder="Tulis catatan singkat jika diperlukan..."
            ></textarea>
          </div>

          <button class="primary-button" :disabled="submitting || habits.length === 0">
            {{ submitting ? 'Menyimpan...' : 'Simpan Check-in' }}
          </button>
        </form>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { checkinApi } from '@/api/checkin'

const auth = useAuthStore()

const loading = ref(true)
const submitting = ref(false)
const editing = ref(false)
const error = ref('')
const habits = ref([])
const todayCheckin = ref(null)

const form = reactive({
  notes: '',
  items: {},
})

const studentName = computed(() => {
  return auth.user?.full_name || auth.user?.name || auth.user?.username || 'Siswa'
})

const todayLabel = computed(() => {
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date())
})

const checkinStatus = computed(() => {
  return todayCheckin.value ? 'Sudah Check-in' : 'Belum Check-in'
})

const summary = computed(() => {
  const items = todayCheckin.value?.items || []

  return {
    total: items.length,
    done: items.filter((item) => item.is_done).length,
    notDone: items.filter((item) => !item.is_done).length,
    validations: items.reduce((total, item) => {
      return total + (item.validations?.length || 0)
    }, 0),
  }
})

onMounted(async () => {
  await loadDashboard()
})

async function loadDashboard() {
  try {
    loading.value = true
    error.value = ''

    const [todayResponse, habitsResponse] = await Promise.all([
      checkinApi.today(),
      checkinApi.getHabits(),
    ])

    todayCheckin.value = todayResponse.data.data

    const habitData = habitsResponse.data.data
    habits.value = Array.isArray(habitData) ? habitData : habitData?.items || []

    prepareForm()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat dashboard siswa.'
  } finally {
    loading.value = false
  }
}

function prepareForm() {
  form.notes = todayCheckin.value?.notes || ''
  form.items = {}

  for (const habit of habits.value) {
    const existingItem = todayCheckin.value?.items?.find((item) => {
      return Number(item.habit_id) === Number(habit.id)
    })

    form.items[habit.id] = existingItem ? Boolean(existingItem.is_done) : false
  }
}

function enableEdit() {
  editing.value = true
  prepareForm()
}

async function submitCheckin() {
  try {
    submitting.value = true
    error.value = ''

    const payload = {
      notes: form.notes || null,
      items: habits.value.map((habit) => ({
        habit_id: habit.id,
        is_done: Boolean(form.items[habit.id]),
      })),
    }

    const response = await checkinApi.store(payload)

    todayCheckin.value = response.data.data
    editing.value = false
    prepareForm()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menyimpan check-in.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.student-dashboard {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.hero-card {
  background: linear-gradient(135deg, #ffffff, #eaf3ff);
  border: 1px solid #dbeafe;
  border-radius: 26px;
  padding: 28px;
  display: flex;
  justify-content: space-between;
  gap: 20px;
  box-shadow: 0 14px 34px rgba(15, 76, 129, 0.08);
}

.eyebrow {
  margin: 0 0 8px;
  color: #1976d2;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 12px;
}

.hero-card h2 {
  margin: 0 0 8px;
  font-size: 28px;
  color: #10243f;
}

.hero-card p {
  margin: 0;
  color: #64748b;
  max-width: 560px;
}

.date-card {
  min-width: 180px;
  border-radius: 20px;
  padding: 18px;
  background: #0f4c81;
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.date-card span {
  font-size: 13px;
  opacity: 0.85;
}

.date-card strong {
  font-size: 18px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.summary-card,
.main-card,
.state-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 22px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
}

.summary-card {
  padding: 20px;
}

.summary-card span {
  display: block;
  color: #64748b;
  font-size: 14px;
  margin-bottom: 8px;
}

.summary-card strong {
  font-size: 28px;
  color: #0f4c81;
}

.main-card {
  padding: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.card-header h3 {
  margin: 0 0 6px;
  color: #10243f;
  font-size: 22px;
}

.card-header p {
  margin: 0;
  color: #64748b;
}

.habit-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.habit-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #f8fafc;
  cursor: pointer;
  transition: 0.2s ease;
}

.habit-item:hover {
  border-color: #93c5fd;
  background: #f0f7ff;
}

.habit-item.checked {
  border-color: #1976d2;
  background: #eaf3ff;
}

.habit-item input {
  display: none;
}

.habit-check {
  width: 22px;
  height: 22px;
  border: 2px solid #cbd5e1;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.habit-item.checked .habit-check {
  background: #1976d2;
  border-color: #1976d2;
}

.habit-item.checked .habit-check::after {
  content: '✓';
  color: white;
  font-size: 14px;
  font-weight: 700;
}

.habit-item strong {
  display: block;
  color: #10243f;
}

.habit-item small {
  display: block;
  margin-top: 3px;
  color: #64748b;
}

.notes-field {
  margin-top: 18px;
}

.notes-field label {
  display: block;
  margin-bottom: 8px;
  font-weight: 700;
  color: #10243f;
}

.notes-field textarea {
  width: 100%;
  border: 1px solid #dbe3ef;
  border-radius: 16px;
  padding: 14px;
  resize: vertical;
  font: inherit;
  outline: none;
}

.notes-field textarea:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.12);
}

.primary-button,
.secondary-button {
  border: 0;
  border-radius: 14px;
  padding: 12px 18px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button {
  margin-top: 18px;
  background: #1976d2;
  color: white;
}

.primary-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.secondary-button {
  background: #eaf3ff;
  color: #0f4c81;
}

.done-state {
  display: flex;
  gap: 16px;
  align-items: center;
  padding: 18px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 18px;
}

.done-icon {
  width: 42px;
  height: 42px;
  background: #16a34a;
  color: white;
  border-radius: 999px;
  display: grid;
  place-items: center;
  font-weight: 800;
}

.done-state h4 {
  margin: 0 0 4px;
  color: #166534;
}

.done-state p {
  margin: 0;
  color: #4b5563;
}

.state-card {
  padding: 20px;
  color: #64748b;
}

.state-card.error {
  color: #b91c1c;
  background: #fef2f2;
  border-color: #fecaca;
}

@media (max-width: 900px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .habit-list {
    grid-template-columns: 1fr;
  }

  .hero-card,
  .card-header {
    flex-direction: column;
  }

  .date-card {
    min-width: 0;
  }
}

@media (max-width: 520px) {
  .hero-card,
  .main-card {
    padding: 20px;
    border-radius: 22px;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>