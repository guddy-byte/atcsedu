<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { loginAdmin } from '../../utils/adminAuth'

const router = useRouter()
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const showPassword = ref(false)

const submitLogin = () => {
  errorMessage.value = ''

  if (!email.value.trim() || !password.value.trim()) {
    errorMessage.value = 'Enter your admin email and password.'
    return
  }

  if (!loginAdmin(email.value, password.value)) {
    errorMessage.value = 'Invalid admin credentials.'
    return
  }

  router.push('/admin')
}
</script>

<template>
  <section class="mx-auto grid w-full max-w-5xl gap-6 rounded-[2rem] border border-white/80 bg-white p-4 shadow-[0_25px_70px_rgba(117,49,108,0.1)] md:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(360px,1fr)] lg:p-8">
    <div class="rounded-[1.6rem] bg-[#0f172a] p-6 text-white sm:p-7">
      <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">Admin authentication</p>
      <h2 class="mt-3 text-3xl font-extrabold tracking-tight">Admin login</h2>
      <p class="mt-4 text-sm leading-7 text-slate-200">
        Manage materials, exam uploads, and student performance records from your admin dashboard.
      </p>

      <div class="mt-6 rounded-xl border border-white/15 bg-white/5 px-4 py-4 text-sm">
        <p class="font-semibold">Dashboard URL</p>
        <p class="mt-1 break-all text-slate-300">/admin</p>
      </div>

    </div>

    <form class="grid content-start gap-4 rounded-[1.6rem] border border-slate-200 bg-slate-50/70 p-5 sm:p-6" @submit.prevent="submitLogin">
      <div>
        <h3 class="text-xl font-extrabold tracking-tight text-slate-900">Admin access</h3>
        <p class="mt-1 text-sm text-slate-600">Sign in to continue to the dashboard.</p>
      </div>

      <label class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">Admin email</span>
        <input
          v-model="email"
          type="email"
          class="rounded-[0.95rem] border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-primary/45 focus:ring-2 focus:ring-primary/10"
        />
      </label>

      <label class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">Password</span>
        <div class="flex items-center rounded-[0.95rem] border border-slate-200 bg-white pr-2 focus-within:border-primary/45 focus-within:ring-2 focus-within:ring-primary/10">
          <input
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            class="w-full rounded-[0.95rem] bg-transparent px-4 py-3 text-sm text-slate-700 outline-none"
          />
          <button type="button" class="rounded-full px-2 py-1 text-xs font-semibold text-slate-500" @click="showPassword = !showPassword">
            {{ showPassword ? 'Hide' : 'Show' }}
          </button>
        </div>
      </label>

      <p v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-primary">
        {{ errorMessage }}
      </p>

      <button type="submit" class="inline-flex items-center justify-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">
        Access dashboard
      </button>

      <RouterLink
        to="/admin/auth/reset-otp"
        class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700"
      >
        Reset admin OTP
      </RouterLink>
    </form>
  </section>
</template>
