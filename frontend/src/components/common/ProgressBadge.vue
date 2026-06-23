<template>
  <div class="rounded-2xl bg-white p-4 shadow-sm">
    <div class="mb-2 flex items-center justify-between">
      <h3 class="font-medium">
        Progress Hari Ini
      </h3>

      <span class="text-sm text-slate-500">
        {{ completed }}/{{ total }}
      </span>
    </div>

    <div class="h-3 rounded-full bg-slate-200">
      <div
        class="h-3 rounded-full bg-violet-600 transition-all"
        :style="{ width: `${percentage}%` }"
      />
    </div>

    <p class="mt-2 text-sm text-slate-500">
      {{ percentage }}% selesai
    </p>
  </div>
</template>

<script setup>
defineProps({
  completed: {
    type: Number,
    default: 0,
  },

  total: {
    type: Number,
    default: 0,
  },

  percentage: {
    type: Number,
    default: 0,
  },
})
</script>