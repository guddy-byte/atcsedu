<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import MaterialCard from '../components/MaterialCard.vue'
import { useCatalogStore } from '../stores/catalog'

const props = defineProps<{
  mode: 'free' | 'paid'
  title: string
  description: string
}>()

const catalog = useCatalogStore()
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = 6

const filteredProducts = computed(() =>
  catalog.products.filter((product) => {
    if (product.accessType !== props.mode) {
      return false
    }

    const normalizedQuery = searchQuery.value.trim().toLowerCase()

    if (!normalizedQuery) {
      return true
    }

    return [product.title, product.category, product.description, product.format]
      .join(' ')
      .toLowerCase()
      .includes(normalizedQuery)
  }),
)

const pageCount = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / perPage)))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredProducts.value.slice(start, start + perPage)
})

const pageNumbers = computed(() => Array.from({ length: pageCount.value }, (_, index) => index + 1))

watch(searchQuery, () => {
  currentPage.value = 1
})

watch(filteredProducts, () => {
  if (currentPage.value > pageCount.value) {
    currentPage.value = pageCount.value
  }
})
</script>

<template>
  <section class="grid gap-6">
    <div class="rounded-[2rem] border border-white/75 bg-white/88 p-8 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
      <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">Material library</p>
      <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950">{{ title }}</h2>
      <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">{{ description }}</p>
    </div>

    <div class="rounded-[1.5rem] border border-rose-100 bg-white p-4 shadow-[0_12px_34px_rgba(117,49,108,0.06)]">
      <input
        v-model="searchQuery"
        type="search"
        placeholder="Search materials by title, category, or format"
        class="w-full rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10"
      />
    </div>

    <div v-if="filteredProducts.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <MaterialCard
        v-for="product in paginatedProducts"
        :key="product.id"
        :product="product"
        @pay="catalog.addToCart"
      />
    </div>

    <div
      v-else
      class="rounded-[1.6rem] border border-dashed border-rose-200 bg-white/80 px-6 py-12 text-center shadow-[0_12px_34px_rgba(117,49,108,0.05)]"
    >
      <p class="text-sm font-semibold text-slate-700">No materials matched your search.</p>
      <p class="mt-2 text-sm text-slate-500">Try a different keyword or browse another material type.</p>
    </div>

    <div
      v-if="filteredProducts.length > perPage"
      class="flex flex-wrap items-center justify-center gap-2"
    >
      <button
        v-for="page in pageNumbers"
        :key="page"
        class="inline-flex h-10 min-w-10 items-center justify-center rounded-full border px-3 text-sm font-semibold transition"
        :class="
          currentPage === page
            ? 'border-primary bg-primary text-white shadow-[0_10px_20px_rgba(232,49,52,0.18)]'
            : 'border-rose-100 bg-white text-slate-600 hover:border-primary/30 hover:text-primary'
        "
        @click="currentPage = page"
      >
        {{ page }}
      </button>
    </div>
  </section>
</template>