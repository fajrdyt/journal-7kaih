<template>
  <div
    class="rounded-lg border p-4"
    :class="
      habit.completed
        ? 'bg-slate-50'
        : 'border-teal-600 bg-white'
    "
  >
    <div
      class="flex items-center justify-between"
    >
      <div>
        <h4 class="font-medium">
          {{ habit.name }}
        </h4>

        <p
          class="text-xs text-slate-500"
        >
          {{ habit.note || 'Tambahkan catatan...' }}
        </p>
      </div>

      <div
        class="h-5 w-5 rounded-full border"
      />
    </div>

    <template v-if="!habit.completed">
      <textarea
        class="mt-4 w-full rounded border p-2"
        rows="3"
        placeholder="Tulis aktivitas..."
      />

      <div
        class="mt-3 flex justify-end"
      >
        <button
          class="rounded bg-teal-700 px-4 py-2 text-white"
        >
          Simpan
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  habit: Object,
})
</script>