<script setup>
import { computed } from 'vue'

const props = defineProps({
  habit: {
    type: Object,
    required: true,
  },
  editable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['toggle', 'update-note'])

const isDone = computed(() => {
  return Boolean(props.habit.is_done ?? props.habit.completed)
})

const habitCode = computed(() => {
  return String(props.habit.code ?? '').toUpperCase()
})

const habitIcon = computed(() => {
  const icons = {
    BANGUN_PAGI: '☼',
    BERIBADAH: '✦',
    BEROLAHRAGA: '⚡',
    MAKAN_SEHAT_BERGIZI: '♨',
    GEMAR_BELAJAR: '▣',
    BERMASYARAKAT: '⌘',
    TIDUR_CEPAT: '☾',
  }

  return icons[habitCode.value] ?? '✓'
})

function handleToggle() {
  if (!props.editable) return

  emit('toggle')
}

function handleNoteInput(event) {
  if (!props.editable) return

  emit('update-note', event.target.value)
}
</script>

<template>
  <article
    class="group flex min-h-[200px] flex-col rounded-2xl border bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.05)] transition-all duration-300 ease-out hover:-translate-y-1.5 hover:shadow-[0_20px_45px_rgba(14,165,233,0.15)] active:-translate-y-1 active:scale-[0.995]"
    :class="
      isDone
        ? 'border-sky-300 hover:border-sky-400'
        : 'border-slate-200 hover:border-sky-300'
    "
  >
    <div class="flex items-start justify-between gap-4">
      <div class="flex min-w-0 items-center gap-3">
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg font-bold transition-all duration-300 ease-out group-hover:scale-110 group-active:scale-105"
          :class="
            isDone
              ? 'bg-sky-100 text-sky-700 group-hover:bg-sky-600 group-hover:text-white group-active:bg-sky-600 group-active:text-white'
              : 'bg-slate-100 text-slate-500 group-hover:bg-sky-500 group-hover:text-white group-active:bg-sky-500 group-active:text-white'
          "
        >
          {{ habitIcon }}
        </div>

        <h3
          class="truncate text-sm font-bold text-slate-900 transition-colors duration-300 group-hover:text-sky-700"
        >
          {{ habit.name }}
        </h3>
      </div>

      <button
        type="button"
        class="flex h-6 w-6 shrink-0 items-center justify-center rounded border transition-all duration-200 hover:scale-110 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
        :class="
          isDone
            ? 'border-sky-600 bg-sky-600 text-white'
            : 'border-slate-300 bg-white text-transparent hover:border-sky-400'
        "
        :disabled="!props.editable"
        :aria-label="
          isDone
            ? `Batalkan ${habit.name}`
            : `Selesaikan ${habit.name}`
        "
        @click="handleToggle"
      >
        <svg
          viewBox="0 0 20 20"
          fill="none"
          class="h-4 w-4"
          aria-hidden="true"
        >
          <path
            d="M4.5 10.5 8 14l7.5-8"
            stroke="currentColor"
            stroke-width="2.2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </div>

    <div class="mt-4 flex items-center gap-2">
      <span
        class="h-2 w-2 rounded-full transition-colors duration-300"
        :class="isDone ? 'bg-sky-500' : 'bg-slate-400'"
      />

      <p
        class="text-xs font-semibold transition-colors duration-300"
        :class="isDone ? 'text-sky-600' : 'text-slate-500'"
      >
        {{ isDone ? 'Selesai' : 'Belum dilakukan' }}
      </p>
    </div>

    <textarea
      :value="habit.notes ?? habit.note ?? ''"
      :readonly="!props.editable"
      rows="3"
      class="mt-4 min-h-[78px] w-full resize-none rounded-xl border border-transparent bg-indigo-50/70 px-4 py-3 text-xs leading-5 text-slate-700 outline-none transition-all duration-300 placeholder:text-slate-400 group-hover:bg-sky-50/70 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100 read-only:cursor-default read-only:bg-slate-100 read-only:text-slate-500 read-only:focus:border-transparent read-only:focus:ring-0"
      placeholder="Tulis aktivitas atau catatan singkat..."
      @input="handleNoteInput"
    />
  </article>
</template>