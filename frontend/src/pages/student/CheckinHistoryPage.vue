<script setup>
import { onMounted } from 'vue'
import { useCheckinStore } from '@/stores/checkinStore'

const checkinStore = useCheckinStore()

onMounted(() => {
  checkinStore.fetchHistory()
})

function formatDate(date) {
  if (!date) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(new Date(date))
}
</script>

<template>
  <div class="space-y-6">
    <section>
      <h1 class="text-2xl font-bold text-slate-900">
        Riwayat Check-in
      </h1>

      <p class="text-sm text-slate-500">
        Lihat daftar check-in yang sudah pernah kamu isi.
      </p>
    </section>

    <section class="rounded-xl border bg-white p-6 shadow-sm">
      <p
        v-if="checkinStore.loading && !checkinStore.history.length"
        class="text-sm text-slate-500"
      >
        Mengambil riwayat check-in...
      </p>

      <p
        v-else-if="checkinStore.error"
        class="rounded-lg bg-red-50 p-4 text-sm text-red-600"
      >
        {{ checkinStore.error }}
      </p>

      <p
        v-else-if="!checkinStore.history.length"
        class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500"
      >
        Belum ada riwayat check-in.
      </p>

      <div
        v-else
        class="space-y-3"
      >
        <article
          v-for="item in checkinStore.history"
          :key="item.id ?? item.checkin_date"
          class="rounded-lg border border-slate-200 p-4"
        >
          <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="font-semibold text-slate-900">
                {{ formatDate(item.checkin_date) }}
              </h2>

              <p class="text-sm text-slate-500">
                {{ item.notes || 'Tidak ada catatan umum.' }}
              </p>
            </div>

            <span class="rounded-full bg-sky-50 px-3 py-1 text-sm font-medium text-sky-700">
              {{ item.completed_count }}/{{ item.total_count }} selesai
            </span>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>
