<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import AllCategorySection from '../components/AllCategorySection.vue'
import AllProductSection from '../components/AllProductSection.vue'
import HeroSection from '../components/HeroSection.vue'
import StatsSection from '../components/StatsSection.vue'
import { useCatalogStore } from '../stores/catalog'

const catalog = useCatalogStore()

const highlights = computed(() => {
  const materials = catalog.products.filter((product) => product.type === 'material')
  const exams = catalog.products.filter((product) => product.type === 'cbt')
  const paidListings = catalog.products.filter((product) => product.accessType === 'paid')

  return [
    {
      label: 'Materials live',
      value: String(materials.length),
      note: materials.length
        ? 'Resources currently published on the platform.'
        : 'No study materials have been published yet.',
    },
    {
      label: 'Exam trainings live',
      value: String(exams.length),
      note: exams.length
        ? 'Live CBT exams available to learners.'
        : 'No exam training tests are live yet.',
    },
    {
      label: 'Paid listings',
      value: String(paidListings.length),
      note: paidListings.length
        ? 'Items with active pricing in the catalog.'
        : 'No prices or premium listings are published yet.',
    },
  ]
})

const quickLinks = [
  {
    id: 'outcomes',
    label: 'Live totals',
    description: 'See the current platform counts at a glance.',
  },
  {
    id: 'discover',
    label: 'Browse catalog',
    description: 'Check what is currently published in the library.',
  },
  {
    id: 'featured',
    label: 'Latest items',
    description: 'Review the newest live resources and exam entries.',
  },
]
</script>

<template>
  <div class="grid gap-8">
    <HeroSection />

    <section class="rounded-[1.8rem] border border-rose-100 bg-white p-4 shadow-[0_14px_34px_rgba(117,49,108,0.08)] sm:p-6">
      <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">Quick navigation</h2>
        <RouterLink
          to="/admin/auth/login"
          class="rounded-full bg-rose-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-primary"
        >
          Open admin
        </RouterLink>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <a
          v-for="item in quickLinks"
          :key="item.id"
          :href="`#${item.id}`"
          class="rounded-2xl border border-rose-100 bg-rose-50/45 px-4 py-4 transition hover:-translate-y-0.5 hover:border-primary/35"
        >
          <p class="text-sm font-bold text-slate-900">{{ item.label }}</p>
          <p class="mt-1 text-xs leading-5 text-slate-600">{{ item.description }}</p>
        </a>
      </div>
    </section>

    <section id="outcomes" class="scroll-mt-28">
      <StatsSection :stats="highlights" />
    </section>

    <section id="discover" class="scroll-mt-28">
      <AllCategorySection :products="catalog.products" @pay="catalog.addToCart" />
    </section>

    <section id="featured" class="scroll-mt-28">
      <AllProductSection :products="catalog.products" @pay="catalog.addToCart" />
    </section>
  </div>
</template>
