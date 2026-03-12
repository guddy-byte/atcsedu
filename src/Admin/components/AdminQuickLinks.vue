<script setup lang="ts">
import { RouterLink } from 'vue-router'

const links = [
  { label: 'Admin login', path: '/admin/auth/login' },
  { label: 'Reset OTP', path: '/admin/auth/reset-otp' },
  { label: 'Reset password', path: '/admin/auth/reset-password' },
  { label: 'Student signup', path: '/auth/signup' },
]
</script>

<template>
  <section class="grid gap-3 rounded-[1.75rem] border border-rose-100 bg-white p-6 shadow-[0_16px_48px_rgba(117,49,108,0.08)]">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-secondary">Quick links</p>
      <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Admin route shortcuts</h3>
    </div>

    <RouterLink
      v-for="link in links"
      :key="link.path"
      :to="link.path"
      class="rounded-[1.2rem] border border-rose-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary hover:bg-rose-50"
    >
      {{ link.label }}
    </RouterLink>
  </section>
</template>