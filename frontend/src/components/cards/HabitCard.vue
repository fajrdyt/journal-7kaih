<template>
  <div
    class="rounded-xl border bg-white p-4 shadow-sm transition"
    :class="habit.completed ? 'border-sky-200 bg-sky-50' : 'border-slate-200'"
  >
    <div class="flex items-start justify-between gap-4">
      <div>
        <h4 class="font-semibold text-slate-900">
          {{ habit.name }}
        </h4>

        <p class="mt-1 text-xs text-slate-500">
          {{ habit.completed ? 'Sudah dilakukan' : 'Belum dilakukan' }}
        </p>
      </div>

      <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
        <input
          type="checkbox"
          class="h-5 w-5 rounded border-slate-300"
          :checked="habit.completed"
          @change="$emit('update', habit.id, { completed: $event.target.checked })"
        >
        Selesai
      </label>
    </div>

    <textarea
      class="mt-4 w-full rounded-lg border border-slate-200 p-3 text-sm outline-none focus:border-sky-500"
      rows="3"
      placeholder="Tulis aktivitas atau catatan singkat..."
      :value="habit.note"
      @input="$emit('update', habit.id, { note: $event.target.value })"
    />
  </div>
</template>

<script setup>
defineProps({
  habit: {
    type: Object,
    required: true,
  },
})

defineEmits(['update'])
</script>
