<template>
  <div
    ref="rootElement"
    class="searchable-user-select"
  >
    <div
      v-if="selectedOption && !isOpen"
      class="selected-control"
      :class="{ disabled }"
    >
      <button
        type="button"
        class="selected-main"
        :disabled="disabled"
        @click="openForChange"
      >
        <span class="user-avatar">
          {{ getInitials(selectedOption.full_name) }}
        </span>

        <span class="selected-copy">
          <strong>
            {{ selectedOption.full_name || 'Tanpa nama' }}
          </strong>

          <small>
            {{ getOptionMeta(selectedOption) }}
          </small>
        </span>
      </button>

      <button
        type="button"
        class="clear-selected"
        aria-label="Hapus pilihan"
        :disabled="disabled"
        @click="clearSelection"
      >
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" />
        </svg>
      </button>
    </div>

    <div
      v-else
      class="search-control"
      :class="{
        open: isOpen,
        disabled,
        invalid: error,
      }"
      @click="focusInput"
    >
      <svg
        class="search-icon"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-3.5-3.5" />
      </svg>

      <input
        :id="inputId"
        ref="inputElement"
        v-model="query"
        type="search"
        role="combobox"
        autocomplete="off"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-expanded="isOpen"
        :aria-controls="listboxId"
        :aria-activedescendant="activeOptionId"
        @focus="openDropdown"
        @input="handleInput"
        @keydown="handleKeydown"
      />

      <button
        v-if="query"
        type="button"
        class="clear-query"
        aria-label="Hapus pencarian"
        @mousedown.prevent
        @click.stop="clearQuery"
      >
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" />
        </svg>
      </button>
    </div>

    <Transition name="dropdown">
      <div
        v-if="isOpen"
        :id="listboxId"
        class="suggestion-panel"
        role="listbox"
      >
        <div class="suggestion-header">
          <span>
            {{ resultLabel }}
          </span>

          <small>
            ↑↓ navigasi · Enter pilih
          </small>
        </div>

        <div
          v-if="filteredOptions.length"
          class="suggestion-list"
        >
          <button
            v-for="(option, index) in filteredOptions"
            :id="getOptionId(index)"
            :key="option.id"
            type="button"
            role="option"
            class="suggestion-item"
            :class="{
              active: index === activeIndex,
              selected:
                Number(option.id) ===
                Number(modelValue),
            }"
            :aria-selected="
              Number(option.id) ===
              Number(modelValue)
            "
            @mouseenter="activeIndex = index"
            @mousedown.prevent="selectOption(option)"
          >
            <span class="user-avatar">
              {{ getInitials(option.full_name) }}
            </span>

            <span class="suggestion-copy">
              <strong>
                {{ option.full_name || 'Tanpa nama' }}
              </strong>

              <small>
                {{ getOptionMeta(option) }}
              </small>
            </span>

            <svg
              v-if="
                Number(option.id) ===
                Number(modelValue)
              "
              class="check-icon"
              viewBox="0 0 24 24"
              fill="none"
            >
              <path d="m5 12 4 4L19 6" />
            </svg>
          </button>
        </div>

        <div
          v-else
          class="empty-result"
        >
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
          </div>

          <strong>{{ emptyText }}</strong>

          <p>
            Periksa kembali kata pencarian yang dimasukkan.
          </p>
        </div>
      </div>
    </Transition>

    <small
      v-if="error"
      class="error-message"
    >
      {{ error }}
    </small>
  </div>
</template>

<script setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  inputId: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: 'Cari dan pilih pengguna...',
  },
  emptyText: {
    type: String,
    default: 'Pengguna tidak ditemukan',
  },
  context: {
    type: String,
    default: 'user',
    validator: (value) => {
      return [
        'user',
        'teacher',
        'student',
        'parent',
      ].includes(value)
    },
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  limit: {
    type: Number,
    default: 8,
  },
})

const emit = defineEmits([
  'update:modelValue',
  'change',
])

const rootElement = ref(null)
const inputElement = ref(null)

const query = ref('')
const isOpen = ref(false)
const activeIndex = ref(0)

const listboxId = computed(() => {
  return `${props.inputId}-listbox`
})

const selectedOption = computed(() => {
  return props.options.find((option) => {
    return (
      Number(option.id) ===
      Number(props.modelValue)
    )
  })
})

const matchedOptions = computed(() => {
  const keyword = normalizeText(query.value)

  const options = [...props.options].sort(
    (first, second) => {
      return String(first.full_name ?? '').localeCompare(
        String(second.full_name ?? ''),
        'id',
      )
    },
  )

  if (!keyword) {
    return options
  }

  return options.filter((option) => {
    return getSearchValues(option).some((value) => {
      return normalizeText(value).includes(keyword)
    })
  })
})

const filteredOptions = computed(() => {
  return matchedOptions.value.slice(0, props.limit)
})

const resultLabel = computed(() => {
  const total = matchedOptions.value.length
  const visible = filteredOptions.value.length

  if (!query.value.trim()) {
    return `${visible} pilihan tersedia`
  }

  if (total > visible) {
    return `${visible} dari ${total} hasil`
  }

  return `${total} hasil ditemukan`
})

const activeOptionId = computed(() => {
  if (
    !isOpen.value ||
    !filteredOptions.value.length
  ) {
    return undefined
  }

  return getOptionId(activeIndex.value)
})

watch(query, () => {
  activeIndex.value = 0
})

watch(
  () => filteredOptions.value.length,
  (length) => {
    if (!length) {
      activeIndex.value = 0
      return
    }

    if (activeIndex.value >= length) {
      activeIndex.value = length - 1
    }
  },
)

watch(
  () => props.modelValue,
  (value) => {
    if (!value) {
      query.value = ''
    }
  },
)

onMounted(() => {
  document.addEventListener(
    'mousedown',
    handleOutsideClick,
  )
})

onBeforeUnmount(() => {
  document.removeEventListener(
    'mousedown',
    handleOutsideClick,
  )
})

function openDropdown() {
  if (props.disabled) {
    return
  }

  isOpen.value = true
  activeIndex.value = 0
}

function openForChange() {
  if (props.disabled) {
    return
  }

  query.value = ''
  isOpen.value = true
  activeIndex.value = 0

  nextTick(() => {
    inputElement.value?.focus()
  })
}

function focusInput() {
  if (props.disabled) {
    return
  }

  inputElement.value?.focus()
}

function handleInput() {
  if (!isOpen.value) {
    isOpen.value = true
  }
}

function handleKeydown(event) {
  if (event.key === 'ArrowDown') {
    event.preventDefault()

    if (!isOpen.value) {
      openDropdown()
      return
    }

    if (!filteredOptions.value.length) {
      return
    }

    activeIndex.value =
      (activeIndex.value + 1) %
      filteredOptions.value.length

    return
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault()

    if (!isOpen.value) {
      openDropdown()
      return
    }

    if (!filteredOptions.value.length) {
      return
    }

    activeIndex.value =
      (
        activeIndex.value -
        1 +
        filteredOptions.value.length
      ) % filteredOptions.value.length

    return
  }

  if (event.key === 'Enter') {
    if (
      !isOpen.value ||
      !filteredOptions.value.length
    ) {
      return
    }

    event.preventDefault()

    selectOption(
      filteredOptions.value[activeIndex.value],
    )

    return
  }

  if (event.key === 'Escape') {
    event.preventDefault()
    closeDropdown()
  }

  if (event.key === 'Tab') {
    closeDropdown()
  }
}

function selectOption(option) {
  emit('update:modelValue', option.id)
  emit('change', option)

  query.value = ''
  isOpen.value = false
}

function clearSelection() {
  if (props.disabled) {
    return
  }

  emit('update:modelValue', null)
  emit('change', null)

  query.value = ''
  isOpen.value = true

  nextTick(() => {
    inputElement.value?.focus()
  })
}

function clearQuery() {
  query.value = ''
  activeIndex.value = 0

  nextTick(() => {
    inputElement.value?.focus()
  })
}

function closeDropdown() {
  isOpen.value = false
  query.value = ''
}

function handleOutsideClick(event) {
  if (!rootElement.value?.contains(event.target)) {
    closeDropdown()
  }
}

function getOptionId(index) {
  return `${props.inputId}-option-${index}`
}

function getSearchValues(option) {
  return [
    option.full_name,
    option.name,
    option.username,
    option.email,
    option.phone,
    option.class?.name,
    option.class?.grade_level,
  ]
}

function getOptionMeta(option) {
  const username = option.username
    ? `@${option.username}`
    : 'Username tidak tersedia'

  if (props.context === 'student') {
    const className = option.class?.name
      ? `Kelas ${option.class.name}`
      : 'Tanpa kelas'

    return `${username} · ${className}`
  }

  if (props.context === 'teacher') {
    const email =
      option.email || 'Email belum diisi'

    return `${username} · ${email}`
  }

  if (props.context === 'parent') {
    const contact =
      option.email ||
      option.phone ||
      'Kontak belum diisi'

    return `${username} · ${contact}`
  }

  return username
}

function normalizeText(value) {
  return String(value ?? '')
    .trim()
    .toLowerCase()
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
    return words[0]
      .slice(0, 2)
      .toUpperCase()
  }

  return `${words[0][0]}${words.at(-1)[0]}`
    .toUpperCase()
}
</script>

<style scoped>
.searchable-user-select {
  position: relative;
  width: 100%;
}

.search-control,
.selected-control {
  width: 100%;
  min-height: 50px;
  border: 1px solid #dce5ef;
  border-radius: 13px;
  background: #ffffff;
  transition:
    border-color 0.18s ease,
    box-shadow 0.18s ease,
    background 0.18s ease;
}

.search-control {
  position: relative;
  display: flex;
  align-items: center;
}

.search-control:hover:not(.disabled),
.selected-control:hover:not(.disabled) {
  border-color: #bae6fd;
}

.search-control.open {
  border-color: #38bdf8;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
}

.search-control.invalid {
  border-color: #fda4af;
}

.search-control.disabled,
.selected-control.disabled {
  background: #f8fafc;
  opacity: 0.65;
}

.search-icon {
  position: absolute;
  left: 14px;
  width: 19px;
  height: 19px;
  stroke: #94a3b8;
  stroke-width: 1.8;
  stroke-linecap: round;
  pointer-events: none;
}

.search-control input {
  width: 100%;
  height: 48px;
  padding: 0 45px 0 43px;
  border: 0;
  outline: none;
  background: transparent;
  color: #1e293b;
  font: inherit;
  font-size: 12.5px;
}

.search-control input::placeholder {
  color: #94a3b8;
}

.search-control input:disabled {
  cursor: not-allowed;
}

.clear-query,
.clear-selected {
  display: grid;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  place-items: center;
  border: 0;
  border-radius: 9px;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
}

.clear-query {
  position: absolute;
  right: 8px;
}

.clear-query:hover,
.clear-selected:hover:not(:disabled) {
  background: #e2e8f0;
  color: #334155;
}

.clear-query svg,
.clear-selected svg {
  width: 15px;
  height: 15px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.selected-control {
  display: flex;
  align-items: center;
  padding: 6px 7px 6px 9px;
}

.selected-main {
  display: flex;
  min-width: 0;
  flex: 1;
  align-items: center;
  gap: 11px;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.selected-main:disabled,
.clear-selected:disabled {
  cursor: not-allowed;
}

.user-avatar {
  display: grid;
  width: 37px;
  height: 37px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 11px;
  background: #eaf6ff;
  color: #168ad3;
  font-size: 10.5px;
  font-weight: 900;
}

.selected-copy,
.suggestion-copy {
  min-width: 0;
  flex: 1;
}

.selected-copy strong,
.selected-copy small,
.suggestion-copy strong,
.suggestion-copy small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.selected-copy strong,
.suggestion-copy strong {
  color: #1e293b;
  font-size: 12.5px;
  font-weight: 800;
}

.selected-copy small,
.suggestion-copy small {
  margin-top: 4px;
  color: #94a3b8;
  font-size: 10.5px;
  font-weight: 600;
}

.suggestion-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  left: 0;
  z-index: 60;
  overflow: hidden;
  border: 1px solid #dce5ef;
  border-radius: 15px;
  background: #ffffff;
  box-shadow:
    0 24px 55px rgba(15, 23, 42, 0.16),
    0 8px 20px rgba(15, 23, 42, 0.08);
}

.suggestion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 11px 13px;
  border-bottom: 1px solid #edf2f7;
  background: #f8fafc;
}

.suggestion-header span {
  color: #475569;
  font-size: 10.5px;
  font-weight: 800;
}

.suggestion-header small {
  color: #94a3b8;
  font-size: 9.5px;
}

.suggestion-list {
  max-height: 300px;
  overflow-y: auto;
  padding: 6px;
}

.suggestion-item {
  display: flex;
  width: 100%;
  min-width: 0;
  align-items: center;
  gap: 11px;
  padding: 10px;
  border: 0;
  border-radius: 11px;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition:
    background 0.14s ease,
    color 0.14s ease;
}

.suggestion-item:hover,
.suggestion-item.active {
  background: #edf7fe;
}

.suggestion-item.selected {
  background: #f0f9ff;
}

.suggestion-item.selected .user-avatar {
  background: #168ad3;
  color: #ffffff;
}

.check-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  stroke: #168ad3;
  stroke-width: 2.2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.empty-result {
  display: grid;
  min-height: 180px;
  place-items: center;
  align-content: center;
  padding: 24px;
  text-align: center;
}

.empty-icon {
  display: grid;
  width: 44px;
  height: 44px;
  place-items: center;
  border-radius: 14px;
  background: #f1f5f9;
  color: #94a3b8;
}

.empty-icon svg {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
}

.empty-result strong {
  margin-top: 11px;
  color: #334155;
  font-size: 12px;
}

.empty-result p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 10.5px;
}

.error-message {
  display: block;
  margin-top: 6px;
  color: #dc2626;
  font-size: 10.5px;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
  transform-origin: top;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-5px) scale(0.99);
}

@media (max-width: 540px) {
  .suggestion-header small {
    display: none;
  }

  .suggestion-list {
    max-height: 260px;
  }

  .selected-copy small,
  .suggestion-copy small {
    font-size: 10px;
  }
}
</style>