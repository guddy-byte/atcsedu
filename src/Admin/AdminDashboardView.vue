<script setup lang="ts">
import { computed } from 'vue'

import AdminOverviewCard from './components/AdminOverviewCard.vue'
import AdminQuickLinks from './components/AdminQuickLinks.vue'
import { useCatalogStore } from '../stores/catalog'

const catalog = useCatalogStore()

const dashboardStats = computed(() => [
  {
    label: 'Total materials',
    value: catalog.products.length,
    detail: 'Every free and paid resource currently available in the platform catalog.',
  },
  {
    label: 'Paid resources',
    value: catalog.products.filter((product) => product.accessType === 'paid').length,
    detail: 'Premium products prepared for checkout and payment flow expansion.',
  },
  {
    label: 'Free resources',
    value: catalog.products.filter((product) => product.accessType === 'free').length,
    detail: 'Instant-access downloads available to attract and support students.',
  },
])
</script>

<template>
  <section class="grid gap-6">
    <div class="grid gap-6 rounded-[2rem] bg-secondary p-8 shadow-[0_26px_90px_rgba(117,49,108,0.2)] lg:grid-cols-[minmax(0,1.1fr)_minmax(300px,0.9fr)]">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-100">Admin dashboard</p>
        <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-white">
          Centralized catalog and platform administration.
        </h2>
        <p class="mt-4 max-w-3xl text-base leading-8 text-rose-50/90">
          This admin area is now separated into its own folder structure, with dedicated authentication routes and modular dashboard components for future expansion.
        </p>
      </div>

      <div class="rounded-[1.7rem] border border-white/10 bg-white/10 p-6 text-white">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-100">Current focus</p>
        <p class="mt-3 text-2xl font-bold">Catalog governance and exam platform setup</p>
        <p class="mt-3 text-sm leading-7 text-rose-50/85">
          Use the quick links below to test admin authentication pages while you continue refining the UI.
        </p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <AdminOverviewCard
        v-for="item in dashboardStats"
        :key="item.label"
        :label="item.label"
        :value="item.value"
        :detail="item.detail"
      />
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
      <section class="rounded-[1.75rem] border border-rose-100 bg-white p-6 shadow-[0_16px_48px_rgba(117,49,108,0.08)]">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-secondary">Admin components</p>
        <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Dashboard structure is ready</h3>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <div class="rounded-[1.25rem] bg-rose-50 p-4">
            <p class="text-sm font-semibold text-slate-800">Overview cards</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Modular stat cards inside the Admin components folder.</p>
          </div>
          <div class="rounded-[1.25rem] bg-rose-50 p-4">
            <p class="text-sm font-semibold text-slate-800">Authentication routes</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Login, OTP reset, and password reset screens already routed.</p>
          </div>
        </div>
      </section>

      <AdminQuickLinks />
    </div>
  </section>
</template>