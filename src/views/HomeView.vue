<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import AllCategorySection from '../components/AllCategorySection.vue'
import AllProductSection from '../components/AllProductSection.vue'
import HeroSection from '../components/HeroSection.vue'
import StatsSection from '../components/StatsSection.vue'
import { useCatalogStore } from '../stores/catalog'

const catalog = useCatalogStore()

const highlights = computed(() => [
  {
    label: 'Students reached',
    value: '3,450+',
    note: 'Learners accessing exam preparation and digital study support.',
  },
  {
    label: 'Materials available',
    value: '500+',
    note: 'Free and paid resources arranged for quick access.',
  },
  {
    label: 'Exam support rating',
    value: '96%',
    note: 'Confidence score from our internal readiness benchmark.',
  },
])

const quickLinks = [
  {
    id: 'outcomes',
    label: 'Platform impact',
    description: 'See trusted numbers and outcomes first.',
  },
  {
    id: 'discover',
    label: 'Discover by category',
    description: 'Jump straight to free or paid options.',
  },
  {
    id: 'featured',
    label: 'Featured resources',
    description: 'Browse the most popular picks quickly.',
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
          to="/materials/free"
          class="rounded-full bg-rose-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-primary"
        >
          Start learning
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
