<script setup lang="ts">
import { RouterLink } from 'vue-router'

const links = [
  { label: 'Admin login', path: '/admin/auth/login' },
  { label: 'Reset password', path: '/admin/auth/reset-password' },
]
</script>

<template>
  <section class="grid gap-3 rounded-[1.75rem] border border-rose-100 bg-white p-6 shadow-[0_16px_48px_rgba(117,49,108,0.08)]">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-secondary">Quick links</p>
      <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Admin route shortcuts</h3>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
      <RouterLink
        v-for="link in links"
        :key="link.path"
        :to="link.path"
        class="flex items-center justify-between rounded-[1.2rem] border border-rose-100 px-5 py-4 text-sm font-bold text-slate-700 transition hover:border-primary hover:bg-rose-50 hover:shadow-md active:scale-95"
      >
        <span>{{ link.label }}</span>
        <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
      </RouterLink>

      <!-- Placeholder for future expansion -->
      <div class="flex items-center justify-center rounded-[1.2rem] border border-dashed border-slate-200 bg-slate-50/50 p-4 opacity-60">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Reserved Space</p>
      </div>
    </div>
  </section>
</template>