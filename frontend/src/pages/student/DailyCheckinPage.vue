<template>
  <div class="space-y-6">
    <section>
      <h2 class="mb-4 font-semibold">
        Pilih Hari
      </h2>

      <DaySelector />
    </section>

    <section
      class="rounded-xl border bg-white p-6"
    >
      <div
        class="mb-6 flex items-center justify-between"
      >
        <h3 class="font-semibold">
          Kebiasaan Hari Ini
        </h3>

        <ProgressBadge
          :completed="3"
          :total="5"
        />
      </div>

      <div class="space-y-4">
        <HabitCard
          v-for="habit in habits"
          :key="habit.id"
          :habit="habit"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import DaySelector from '@/components/common/DaySelector.vue'
import ProgressBadge from '@/components/common/ProgressBadge.vue'
import HabitCard from '@/components/cards/HabitCard.vue'

const habits = [
  {
    id: 1,
    name: 'Bangun Pagi',
    note: 'Bangun jam 04:30 WIB',
    completed: true,
  },

  {
    id: 2,
    name: 'Beribadah',
    note: 'Sholat Subuh berjamaah',
    completed: true,
  },

  {
    id: 3,
    name: 'Makan',
    note: 'Sarapan bubur ayam',
    completed: true,
  },

  {
    id: 4,
    name: 'Berolahraga',
    note: '',
    completed: false,
  },

  {
    id: 5,
    name: 'Gemar Belajar',
    note: '',
    completed: false,
  },
]
</script>