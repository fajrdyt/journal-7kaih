<template>
  <div class="flex gap-2 overflow-x-auto pb-2">
    <button
      v-for="day in days"
      :key="day.id"
      @click="$emit('select', day.id)"
      class="flex min-w-[70px] flex-col items-center rounded-xl border px-3 py-2 transition"
      :class="
        selectedDay === day.id
          ? 'border-violet-600 bg-violet-50 text-violet-600'
          : 'border-slate-200 bg-white'
      "
    >
      <span class="text-xs">
        {{ day.label }}
      </span>

      <span class="text-lg font-semibold">
        {{ day.date }}
      </span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  selectedDay: {
    type: Number,
    required: true,
  },
})

defineEmits(['select'])

const days = [
  { id: 1, label: 'Sen', date: 12 },
  { id: 2, label: 'Sel', date: 13 },
  { id: 3, label: 'Rab', date: 14 },
  { id: 4, label: 'Kam', date: 15 },
  { id: 5, label: 'Jum', date: 16 },
  { id: 6, label: 'Sab', date: 17 },
  { id: 7, label: 'Min', date: 18 },
]
</script>