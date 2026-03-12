<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import type { Product } from '../stores/catalog'
import MaterialCard from './MaterialCard.vue'

const props = defineProps<{
  products: Product[]
}>()

const emit = defineEmits<{
  pay: [productId: string]
}>()

const featuredProducts = computed(() => props.products.slice(0, 6))
</script>

<template>
  <section class="relative grid gap-6 overflow-hidden rounded-[2rem] border border-white/70 bg-[linear-gradient(180deg,#f7f7f7_0%,#ececec_100%)] p-6 shadow-[0_24px_80px_rgba(15,23,42,0.12),0_3px_0_rgba(255,255,255,0.75)_inset,0_-12px_24px_rgba(15,23,42,0.04)_inset] sm:p-8">
    <div class="pointer-events-none absolute inset-x-10 top-0 h-16 rounded-full bg-white/65 blur-2xl" />
    <div class="pointer-events-none absolute -bottom-10 left-10 h-24 w-24 rounded-full bg-white/40 blur-3xl" />

    <div class="relative flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.26em] text-secondary">All products</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
          Explore the current material collection.
        </h2>
      </div>
      <RouterLink
        to="/auth/signup"
        class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_26px_rgba(237,69,97,0.22)]"
      >
        Apply as a student
      </RouterLink>
    </div>

    <div class="relative grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <MaterialCard
        v-for="product in featuredProducts"
        :key="product.id"
        :product="product"
        @pay="emit('pay', $event)"
      />
    </div>
  </section>
</template>