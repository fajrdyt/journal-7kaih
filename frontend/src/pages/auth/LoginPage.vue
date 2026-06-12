<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Eye, EyeOff } from 'lucide-vue-next'

import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
})

const errorMessage = ref('')
const loading = ref(false)
const showPassword = ref(false)

async function handleLogin() {
  errorMessage.value = ''
  loading.value = true

  try {
    await authStore.login(form.value)

    router.push('/student/dashboard')
  } catch (error) {
    errorMessage.value = 'Email atau password salah'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#EDEDED] flex items-center justify-center px-6 py-10">
    
    <!-- Container -->
    <div
      class="w-full max-w-6xl bg-white overflow-hidden shadow-lg grid grid-cols-1 md:grid-cols-2"
    >

      <!-- LEFT -->
      <div class="flex items-center justify-center px-12 py-16">

        <div class="w-full max-w-sm">

          <h1 class="text-[42px] font-bold text-black leading-none">
            Masuk
          </h1>

          <p class="text-[#8A8A8A] text-sm mt-2 mb-12">
            Masukkan detail akun kamu!
          </p>

          <form
            class="space-y-8"
            @submit.prevent="handleLogin"
          >

            <!-- Username -->
            <div>
              <label class="block text-[18px] text-black mb-2">
                Username
              </label>

              <input
                v-model="form.email"
                type="email"
                class="w-full border-0 border-b border-[#A0A0A0] bg-transparent focus:ring-0 focus:border-black px-0 py-2 text-[16px]"
              >
            </div>

            <!-- Password -->
            <div>
              <label class="block text-[18px] text-black mb-2">
                Password
              </label>

              <div class="relative">

                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="w-full border-0 border-b border-[#A0A0A0] bg-transparent focus:ring-0 focus:border-black px-0 py-2 pr-10 text-[16px]"
                >

                <button
                  type="button"
                  class="absolute right-0 top-2 text-black"
                  @click="showPassword = !showPassword"
                >
                  <EyeOff
                    v-if="!showPassword"
                    class="w-5 h-5"
                  />

                  <Eye
                    v-else
                    class="w-5 h-5"
                  />
                </button>

              </div>
            </div>

            <!-- Error -->
            <p
              v-if="errorMessage"
              class="text-red-500 text-sm"
            >
              {{ errorMessage }}
            </p>

            <!-- Button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full h-[44px] bg-[#2196F3] hover:bg-[#1E88E5] rounded-md text-white text-[16px] font-medium transition-all"
            >
              {{ loading ? 'Loading...' : 'Masuk' }}
            </button>

          </form>

        </div>

      </div>

      <!-- RIGHT -->
      <div class="bg-[#2196F3] relative flex items-center justify-center overflow-hidden">

        <!-- Content -->
        <div class="w-full max-w-md px-10 py-12 text-white">

          <h2 class="text-[52px] font-bold leading-[1.05]">
            Journal 7 <br>
            Kebiasaan Anak <br>
            Indonesia Hebat
          </h2>

          <p class="mt-4 text-[14px] text-blue-100">
            Bangun Kebiasaan Baik, Ciptakan Generasi Hebat
          </p>

          <!-- Illustration -->
          <div class="mt-10 flex justify-center">
            <img
              src="/images/login-illustration.png"
              alt="Login Illustration"
              class="w-full max-w-[360px] object-contain"
            >
          </div>

        </div>

      </div>

    </div>

  </div>
</template>