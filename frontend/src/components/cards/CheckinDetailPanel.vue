<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="panel-layer"
      @click.self="emit('close')"
    >
      <aside class="detail-panel">
        <header class="panel-header">
          <div>
            <p class="header-kicker">Detail Jurnal Siswa</p>
            <h2>{{ studentName }}</h2>
            <p>{{ className }} · {{ formattedDate }}</p>
          </div>

          <button
            type="button"
            class="close-button"
            aria-label="Tutup detail"
            @click="emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M6 6l12 12" />
              <path d="M18 6 6 18" />
            </svg>
          </button>
        </header>

        <div v-if="loading" class="panel-state">
          <span class="loader"></span>
          <p>Memuat detail jurnal...</p>
        </div>

        <div v-else-if="!checkin" class="panel-state">
          <strong>Detail jurnal tidak tersedia</strong>
          <p>Data check-in tidak berhasil dimuat.</p>
        </div>

        <template v-else>
          <div class="panel-content">
            <section class="summary-grid">
              <article>
                <span>Kebiasaan Selesai</span>
                <strong>{{ totalDoneItems }}/{{ totalItems }}</strong>
              </article>

              <article>
                <span>Validasi Guru</span>
                <strong>{{ teacherValidatedCount }}</strong>
              </article>

              <article>
                <span>Menunggu</span>
                <strong>{{ pendingItems.length }}</strong>
              </article>
            </section>

            <section class="general-note">
              <div class="section-heading">
                <div>
                  <p class="section-kicker">Catatan Harian</p>
                  <h3>Catatan Umum</h3>
                </div>
              </div>

              <p v-if="checkin.notes" class="note-content">
                {{ checkin.notes }}
              </p>

              <p v-else class="empty-note">
                Tidak ada catatan umum pada jurnal ini.
              </p>
            </section>

            <section class="habit-section">
              <div class="section-heading habit-heading">
                <div>
                  <p class="section-kicker">Daftar Kebiasaan</p>
                  <h3>Aktivitas Siswa</h3>
                </div>

                <button
                  type="button"
                  class="validate-all-button"
                  :disabled="pendingItems.length === 0 || validatingAll"
                  @click="validateAll"
                >
                  <span v-if="validatingAll" class="small-loader"></span>

                  {{
                    validatingAll
                      ? 'Memvalidasi...'
                      : `Validasi Semua (${pendingItems.length})`
                  }}
                </button>
              </div>

              <div class="habit-list">
                <article
                  v-for="item in items"
                  :key="item.id"
                  :class="[
                    'habit-card',
                    {
                      completed: item.is_done,
                      inactive: !item.is_done,
                    },
                  ]"
                >
                  <div class="habit-number">
                    {{ habitOrder(item) }}
                  </div>

                  <div class="habit-copy">
                    <div class="habit-title-row">
                      <div>
                        <h4>{{ habitName(item) }}</h4>

                        <ValidationStatusBadge
                          :status="itemStatus(item)"
                          :label="itemStatusLabel(item)"
                        />
                      </div>

                      <button
                        v-if="canValidateItem(item)"
                        type="button"
                        class="validate-item-button"
                        :disabled="
                          validatingAll ||
                          String(validatingItemId) === String(item.id)
                        "
                        @click="emit('validate-item', item.id)"
                      >
                        <span
                          v-if="String(validatingItemId) === String(item.id)"
                          class="small-loader dark"
                        ></span>

                        {{
                          String(validatingItemId) === String(item.id)
                            ? 'Memproses...'
                            : 'Validasi'
                        }}
                      </button>
                    </div>

                    <div class="habit-note">
                      <span>Catatan kebiasaan</span>

                      <p v-if="itemNote(item)">
                        {{ itemNote(item) }}
                      </p>

                      <p v-else class="empty-note">
                        Tidak ada catatan untuk kebiasaan ini.
                      </p>
                    </div>

                    <div
                      v-if="itemValidations(item).length"
                      class="validation-info"
                    >
                      <span
                        v-for="validation in itemValidations(item)"
                        :key="validation.id"
                      >
                        {{ validatorRoleLabel(validation.validator_role) }}
                        <template v-if="validatorName(validation)">
                          · {{ validatorName(validation) }}
                        </template>
                      </span>
                    </div>
                  </div>
                </article>
              </div>
            </section>
          </div>
        </template>
      </aside>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue'
import ValidationStatusBadge from '@/components/common/ValidationStatusBadge.vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  checkin: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  validatingItemId: {
    type: [Number, String],
    default: null,
  },
  validatingAll: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'close',
  'validate-item',
  'validate-all',
])

const rawItems = computed(() => {
  return Array.isArray(props.checkin?.items)
    ? props.checkin.items
    : []
})

const items = computed(() => {
  const uniqueItems = new Map()

  rawItems.value.forEach((item) => {
    const key = habitIdentity(item)
    const existing = uniqueItems.get(key)

    if (!existing) {
      uniqueItems.set(key, {
        ...item,
        validations: itemValidations(item),
      })
      return
    }

    uniqueItems.set(key, mergeDuplicateItems(existing, item))
  })

  return Array.from(uniqueItems.values()).sort((left, right) => {
    return Number(habitOrder(left) || 999) - Number(habitOrder(right) || 999)
  })
})

const studentName = computed(() => {
  return (
    props.checkin?.student?.full_name ||
    props.checkin?.student_name ||
    'Siswa'
  )
})

const className = computed(() => {
  const classData =
    props.checkin?.student?.class ||
    props.checkin?.student?.class_room ||
    props.checkin?.student?.classRoom

  if (!classData) {
    return 'Kelas tidak tersedia'
  }

  return [classData.grade_level, classData.name]
    .filter(Boolean)
    .join(' · ')
})

const formattedDate = computed(() => {
  return formatDate(props.checkin?.checkin_date)
})

const totalItems = computed(() => items.value.length)

const totalDoneItems = computed(() => {
  return items.value.filter((item) => Boolean(item.is_done)).length
})

const teacherValidatedCount = computed(() => {
  return items.value.filter((item) => isTeacherValidated(item)).length
})

const pendingItems = computed(() => {
  return items.value.filter((item) => {
    return item.is_done && !isTeacherValidated(item)
  })
})

watch(
  () => props.open,
  (isOpen) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = isOpen ? 'hidden' : ''
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
})

function habitIdentity(item) {
  const code = item?.habit?.code || item?.habit_code

  if (code) {
    return `code:${String(code).trim().toLowerCase()}`
  }

  const name = item?.habit?.name || item?.habit_name

  if (name) {
    return `name:${String(name).trim().toLowerCase()}`
  }

  return `id:${item?.habit_id ?? item?.id}`
}

function mergeDuplicateItems(existing, candidate) {
  const existingDone = Boolean(existing?.is_done)
  const candidateDone = Boolean(candidate?.is_done)

  let preferred = existing
  let alternate = candidate

  if (
    (candidateDone && !existingDone) ||
    (candidateDone === existingDone &&
      Number(candidate?.id || 0) > Number(existing?.id || 0))
  ) {
    preferred = candidate
    alternate = existing
  }

  const validations = [
    ...itemValidations(existing),
    ...itemValidations(candidate),
  ].filter((validation, index, all) => {
    const identity =
      validation?.id ||
      `${validation?.validator_role}:${validation?.validator_id}`

    return all.findIndex((item) => {
      const itemIdentity =
        item?.id || `${item?.validator_role}:${item?.validator_id}`

      return String(itemIdentity) === String(identity)
    }) === index
  })

  return {
    ...preferred,
    is_done: existingDone || candidateDone,
    notes:
      preferred?.notes ||
      preferred?.note ||
      alternate?.notes ||
      alternate?.note ||
      null,
    validations,
  }
}

function itemValidations(item) {
  if (Array.isArray(item?.validations)) {
    return item.validations
  }

  if (item?.validation && typeof item.validation === 'object') {
    return [item.validation]
  }

  return []
}

function isTeacherValidated(item) {
  return itemValidations(item).some((validation) => {
    return validation?.validator_role === 'guru'
  })
}

function canValidateItem(item) {
  return Boolean(item?.is_done) && !isTeacherValidated(item)
}

function itemStatus(item) {
  if (!item?.is_done) return 'not-done'
  if (isTeacherValidated(item)) return 'validated'
  return 'pending'
}

function itemStatusLabel(item) {
  if (!item?.is_done) return 'Belum Dilakukan'
  if (isTeacherValidated(item)) return 'Tervalidasi Guru'
  return 'Menunggu Validasi Guru'
}

function habitName(item) {
  return (
    item?.habit?.name ||
    item?.habit_name ||
    `Kebiasaan ${item?.habit_id || ''}`
  )
}

function habitOrder(item) {
  return item?.habit?.sort_order || item?.sort_order || item?.habit_id || '–'
}

function itemNote(item) {
  return item?.notes || item?.note || ''
}

function validatorRoleLabel(role) {
  const labels = {
    guru: 'Guru',
    orang_tua: 'Orang Tua',
  }

  return labels[role] || role || 'Validator'
}

function validatorName(validation) {
  return (
    validation?.validator?.full_name ||
    validation?.validator_name ||
    ''
  )
}

function validateAll() {
  emit(
    'validate-all',
    pendingItems.value.map((item) => item.id),
  )
}

function formatDate(value) {
  if (!value) return 'Tanggal tidak tersedia'

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
.panel-layer {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  justify-content: flex-end;
  background: rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(3px);
}

.detail-panel {
  width: min(620px, 100%);
  height: 100vh;
  overflow-y: auto;
  background: #f5f8fc;
  box-shadow: -20px 0 60px rgba(15, 23, 42, 0.18);
  animation: slide-in 0.24s ease;
}

.panel-header {
  position: sticky;
  top: 0;
  z-index: 5;
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: flex-start;
  padding: 24px;
  border-bottom: 1px solid #dbe6f0;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(14px);
}

.header-kicker,
.section-kicker {
  margin: 0 0 6px;
  color: #209cee;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.panel-header h2 {
  margin: 0;
  color: #0f172a;
  font-size: 22px;
  font-weight: 900;
}

.panel-header p:last-child {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 12px;
}

.close-button {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #ffffff;
  color: #64748b;
  cursor: pointer;
}

.close-button svg {
  width: 18px;
  height: 18px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.panel-content {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 22px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 11px;
}

.summary-grid article,
.general-note,
.habit-section {
  border: 1px solid #e4edf6;
  border-radius: 18px;
  background: #ffffff;
}

.summary-grid article {
  padding: 15px;
}

.summary-grid span {
  display: block;
  color: #64748b;
  font-size: 10.5px;
  font-weight: 700;
}

.summary-grid strong {
  display: block;
  margin-top: 7px;
  color: #0f172a;
  font-size: 22px;
  font-weight: 900;
}

.general-note,
.habit-section {
  padding: 19px;
}

.section-heading {
  margin-bottom: 14px;
}

.habit-heading {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  align-items: center;
}

.section-heading h3 {
  margin: 0;
  color: #0f172a;
  font-size: 16px;
  font-weight: 900;
}

.note-content,
.empty-note {
  margin: 0;
  font-size: 12.5px;
  line-height: 1.75;
}

.note-content {
  color: #334155;
}

.empty-note {
  color: #94a3b8;
  font-style: italic;
}

.validate-all-button,
.validate-item-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 11px;
  font: inherit;
  font-weight: 800;
  cursor: pointer;
}

.validate-all-button {
  min-height: 38px;
  padding: 0 14px;
  border: 0;
  background: #209cee;
  color: #ffffff;
  font-size: 11.5px;
}

.validate-item-button {
  min-height: 33px;
  padding: 0 11px;
  flex-shrink: 0;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1686d1;
  font-size: 10.5px;
}

.validate-all-button:disabled,
.validate-item-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.habit-list {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.habit-card {
  display: flex;
  gap: 13px;
  padding: 15px;
  border: 1px solid #e2e8f0;
  border-radius: 15px;
  background: #ffffff;
}

.habit-card.completed {
  border-color: #cfe9fa;
  background: #fbfdff;
}

.habit-card.inactive {
  background: #f8fafc;
}

.habit-number {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 11px;
  background: #eaf6ff;
  color: #1686d1;
  font-size: 12px;
  font-weight: 900;
}

.habit-copy {
  min-width: 0;
  flex: 1;
}

.habit-title-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.habit-title-row h4 {
  margin: 0 0 7px;
  color: #1e293b;
  font-size: 13.5px;
  font-weight: 900;
}

.habit-note {
  margin-top: 13px;
  padding: 11px 12px;
  border-radius: 11px;
  background: #f8fafc;
}

.habit-note > span {
  display: block;
  margin-bottom: 5px;
  color: #64748b;
  font-size: 9.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.habit-note p {
  margin: 0;
  color: #334155;
  font-size: 11.5px;
  line-height: 1.65;
}

.validation-info {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}

.validation-info span {
  padding: 5px 8px;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
  font-size: 9.5px;
  font-weight: 700;
}

.panel-state {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 30px;
  color: #64748b;
  text-align: center;
}

.panel-state p {
  margin: 0;
  font-size: 12px;
}

.loader,
.small-loader {
  display: inline-block;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.loader {
  width: 26px;
  height: 26px;
  border: 3px solid #dbeafe;
  border-top-color: #209cee;
}

.small-loader {
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #ffffff;
}

.small-loader.dark {
  border-color: #bfdbfe;
  border-top-color: #1686d1;
}

@keyframes slide-in {
  from {
    transform: translateX(35px);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 560px) {
  .panel-content,
  .panel-header {
    padding-left: 16px;
    padding-right: 16px;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .habit-heading,
  .habit-title-row {
    align-items: stretch;
    flex-direction: column;
  }

  .validate-all-button,
  .validate-item-button {
    width: 100%;
  }
}
</style>
