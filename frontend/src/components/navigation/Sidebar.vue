<template>
  <aside
    class="fixed left-0 top-0 flex h-screen w-[240px] flex-col border-r bg-white"
  >
    <div class="border-b p-5">
      <h1 class="font-bold text-sky-700">
        Jurnal 7 Kaih
      </h1>

      <p class="text-xs text-slate-500">
        Habit Tracking System
      </p>
    </div>

    <nav class="flex-1 p-4">
      <ul class="space-y-2">
        <li>
          <RouterLink
            to="/student/checkin"
            class="block rounded-lg bg-teal-300 px-4 py-3 text-sm font-medium"
          >
            Beranda
          </RouterLink>
        </li>

        <li>
          <a
            class="block rounded-lg px-4 py-3 text-sm text-slate-600 hover:bg-slate-100"
          >
            Laporan
          </a>
        </li>

        <li>
          <a
            class="block rounded-lg px-4 py-3 text-sm text-slate-600 hover:bg-slate-100"
          >
            Habit
          </a>
        </li>

        <li>
          <a
            class="block rounded-lg px-4 py-3 text-sm text-slate-600 hover:bg-slate-100"
          >
            Pengaturan
          </a>
        </li>
      </ul>
    </nav>

    <div class="border-t p-4">
      <button
        class="w-full text-left text-sm text-slate-600"
      >
        Logout
      </button>
    </div>
  </aside>
</template>