<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

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

const modeLabel = computed(() => (props.mode === 'free' ? 'Free materials' : 'Paid materials'))

const modeDescription = computed(() =>
  props.mode === 'free'
    ? 'Browse downloadable resources you can access immediately at no cost.'
    : 'Find premium resources crafted for deeper learning outcomes.',
)

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

const currentRange = computed(() => {
  if (!filteredProducts.value.length) {
    return '0 of 0'
  }

  const start = (currentPage.value - 1) * perPage + 1
  const end = Math.min(start + perPage - 1, filteredProducts.value.length)
  return `${start}-${end} of ${filteredProducts.value.length}`
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
    <div class="overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
      <div class="bg-[linear-gradient(120deg,rgba(237,69,97,0.1),rgba(117,49,108,0.12))] px-8 py-7">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">Material library</p>
        <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950">{{ title }}</h2>
        <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">{{ description }}</p>
      </div>

      <div class="grid gap-4 border-t border-rose-100/70 px-8 py-5 md:grid-cols-3">
        <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4">
          <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Collection</p>
          <p class="mt-1 text-sm font-bold text-slate-900">{{ modeLabel }}</p>
          <p class="mt-1 text-xs leading-5 text-slate-500">{{ modeDescription }}</p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4">
          <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Available now</p>
          <p class="mt-1 text-sm font-bold text-slate-900">{{ filteredProducts.length }} materials</p>
          <p class="mt-1 text-xs leading-5 text-slate-500">Updated and arranged for quick discovery.</p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4">
          <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Current page</p>
          <p class="mt-1 text-sm font-bold text-slate-900">{{ currentRange }}</p>
          <p class="mt-1 text-xs leading-5 text-slate-500">Navigate pages to view the full collection.</p>
        </div>
      </div>
    </div>


    <div class="rounded-[1.3rem] border border-rose-100 bg-white p-2 shadow-[0_12px_30px_rgba(117,49,108,0.05)]">
      <div class="grid grid-cols-2 gap-2">
        <RouterLink
          to="/materials/free"
          class="inline-flex items-center justify-center rounded-[0.95rem] px-4 py-2.5 text-sm font-semibold transition"
          :class="props.mode === 'free' ? 'bg-primary text-white shadow-[0_10px_22px_rgba(237,69,97,0.22)]' : 'bg-rose-50/70 text-slate-600 hover:text-primary'"
        >
          Free materials
        </RouterLink>
        <RouterLink
          to="/materials/paid"
          class="inline-flex items-center justify-center rounded-[0.95rem] px-4 py-2.5 text-sm font-semibold transition"
          :class="props.mode === 'paid' ? 'bg-primary text-white shadow-[0_10px_22px_rgba(237,69,97,0.22)]' : 'bg-rose-50/70 text-slate-600 hover:text-primary'"
        >
          Paid materials
        </RouterLink>
      </div>
    </div>

    <div class="rounded-[1.5rem] border border-rose-100 bg-white p-4 shadow-[0_12px_34px_rgba(117,49,108,0.06)]">
      <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.17em] text-slate-500">
        Search materials
      </label>

      <div class="flex items-center gap-3 rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 transition focus-within:border-primary/40 focus-within:ring-2 focus-within:ring-primary/10">
        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-4 w-4 shrink-0 text-slate-400">
          <path
            d="M11 4a7 7 0 0 1 5.292 11.584l3.562 3.562a1 1 0 0 1-1.414 1.414l-3.562-3.562A7 7 0 1 1 11 4Zm0 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z"
            fill="currentColor"
          />
        </svg>

        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search by title, category, description, or format"
          class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400"
        />

        <button
          v-if="searchQuery"
          type="button"
          class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500 transition hover:text-primary"
          @click="searchQuery = ''"
        >
          Clear
        </button>
      </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 px-1">
      <p class="text-sm font-semibold text-slate-700">Showing {{ currentRange }}</p>
      <p class="text-xs text-slate-500">Tip: Use specific keywords to narrow down results quickly.</p>
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
