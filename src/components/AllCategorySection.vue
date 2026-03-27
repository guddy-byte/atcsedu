<script setup lang="ts">
import { computed, ref } from 'vue'

import type { Product } from '../stores/catalog'
import MaterialCard from './MaterialCard.vue'

const props = defineProps<{
  products: Product[]
  pendingProductId?: string
}>()

const emit = defineEmits<{
  pay: [payload: { productId: string; productType: Product['type'] }]
}>()

const selectedCategory = ref<'materials' | 'paid' | 'free'>('materials')
const searchQuery = ref('')
const visibleCount = ref(6)
const perPage = 6

const categoryCards = computed(() => [
  { label: 'All Live', value: props.products.length, key: 'materials' as const },
  {
    label: 'Paid',
    value: props.products.filter((product) => product.accessType === 'paid').length,
    key: 'paid' as const,
  },
  {
    label: 'Free',
    value: props.products.filter((product) => product.accessType === 'free').length,
    key: 'free' as const,
  },
])

const filteredProducts = computed(() => {
  const normalizedQuery = searchQuery.value.trim().toLowerCase()

  if (selectedCategory.value === 'materials') {
    return props.products.filter((product) => {
      if (!normalizedQuery) {
        return true
      }

      return [product.title, product.category, product.description, product.format]
        .join(' ')
        .toLowerCase()
        .includes(normalizedQuery)
    })
  }

  return props.products.filter((product) => {
    if (product.accessType !== selectedCategory.value) {
      return false
    }

    if (!normalizedQuery) {
      return true
    }

    return [product.title, product.category, product.description, product.format]
      .join(' ')
      .toLowerCase()
      .includes(normalizedQuery)
  })
})

const paginatedProducts = computed(() => {
  return filteredProducts.value.slice(0, visibleCount.value)
})

const hasMoreProducts = computed(() => filteredProducts.value.length > visibleCount.value)

const selectCategory = (category: 'materials' | 'paid' | 'free') => {
  selectedCategory.value = category
  visibleCount.value = perPage
}

const showMore = () => {
  visibleCount.value += perPage
}
</script>

<template>
  <section class="relative grid gap-6 overflow-hidden rounded-[2rem] border border-white/70 bg-[linear-gradient(180deg,#f7f7f7_0%,#ececec_100%)] p-6 shadow-[0_24px_80px_rgba(15,23,42,0.12),0_3px_0_rgba(255,255,255,0.75)_inset,0_-12px_24px_rgba(15,23,42,0.04)_inset] sm:p-8">
    <div class="pointer-events-none absolute inset-x-10 top-0 h-16 rounded-full bg-white/65 blur-2xl" />
    <div class="pointer-events-none absolute -bottom-10 left-10 h-24 w-24 rounded-full bg-white/40 blur-3xl" />
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.26em] text-secondary">All categories</p>
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
          Browse the live catalog as it grows.
        </h2>
      </div>
      <div class="text-sm text-slate-500">Showing {{ Math.min(visibleCount, filteredProducts.length) }} of {{ filteredProducts.length }}</div>
    </div>

    <div class="relative grid gap-3 md:grid-cols-3">
      <button
        v-for="item in categoryCards"
        :key="item.key"
        class="rounded-[1.4rem] border px-5 py-4 text-left transition"
        :class="
          selectedCategory === item.key
            ? 'border-primary bg-rose-50'
            : 'border-rose-100 bg-white hover:border-primary/30 hover:bg-rose-50/60'
        "
        @click="selectCategory(item.key)"
      >
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-secondary">{{ item.label }}</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-primary">{{ item.value }}</p>
      </button>
    </div>

    <div class="relative">
      <input
        v-model="searchQuery"
        type="search"
        placeholder="Search materials by title, category, or format"
        class="w-full rounded-[1.2rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 shadow-[0_10px_24px_rgba(15,23,42,0.04)] outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10"
      />
    </div>

    <div class="relative grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <MaterialCard
        v-for="product in paginatedProducts"
        :key="product.id"
        :product="product"
        :is-paying="props.pendingProductId === product.id"
        @pay="emit('pay', $event)"
      />
    </div>

    <div
      v-if="!filteredProducts.length"
      class="relative rounded-[1.4rem] border border-dashed border-rose-200 bg-white/70 px-6 py-10 text-center"
    >
      <p class="text-sm font-semibold text-slate-700">
        {{ searchQuery ? 'No live items matched your search.' : 'The catalog is still empty.' }}
      </p>
      <p class="mt-2 text-sm text-slate-500">
        {{ searchQuery ? 'Try a different keyword or clear the filter.' : 'Real materials and exam trainings will appear here once they go live.' }}
      </p>
    </div>

    <div v-if="hasMoreProducts" class="relative flex flex-wrap items-center justify-center gap-2 border-t border-rose-100 pt-4">
      <button
        class="inline-flex items-center justify-center rounded-full border border-rose-100 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary/30 hover:text-primary"
        @click="showMore"
      >
        See More
      </button>
    </div>
  </section>
</template>
