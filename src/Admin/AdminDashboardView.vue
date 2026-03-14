<script setup lang="ts">
import { computed, ref } from 'vue'

import AdminOverviewCard from './components/AdminOverviewCard.vue'
import AdminQuickLinks from './components/AdminQuickLinks.vue'
import ProductModal from './components/ProductModal.vue'
import { useCatalogStore } from '../stores/catalog'

const catalog = useCatalogStore()

const showModal = ref(false)
const modalType = ref<'material' | 'cbt' | 'bulk'>('material')

const dashboardStats = computed(() => {
  const materials = catalog.products.filter(p => p.type === 'material')
  const cbts = catalog.products.filter(p => p.type === 'cbt')
  
  return [
    {
      label: 'All Materials',
      value: materials.length,
      detail: 'Total study guides and resources uploaded.',
    },
    {
      label: 'Paid Materials',
      value: materials.filter(p => p.accessType === 'paid').length,
      detail: 'Premium PDF guides and lesson notes.',
    },
    {
      label: 'Free Materials',
      value: materials.filter(p => p.accessType === 'free').length,
      detail: 'Publicly available study docs.',
    },
    {
      label: 'Paid CBT',
      value: cbts.filter(p => p.accessType === 'paid').length,
      detail: 'Premium examinations with revenue potential.',
    },
    {
      label: 'Free CBT',
      value: cbts.filter(p => p.accessType === 'free').length,
      detail: 'Open exams for student practice.',
    },
    {
      label: 'Total Students',
      value: '1,248',
      detail: 'Registered students actively using the portal.',
    },
  ]
})

const openAddModal = (type: 'material' | 'cbt' | 'bulk') => {
  modalType.value = type
  showModal.value = true
}

const deleteItem = (id: string) => {
  if (confirm('Are you sure you want to delete this material? This action cannot be undone.')) {
    catalog.deleteProduct(id)
  }
}

// Table State: Search, Sort, Pagination
const searchQuery = ref('')
const categoryFilter = ref('')
const sortKey = ref<'title' | 'category' | 'accessType' | 'price'>('title')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const itemsPerPage = 8

const toggleSort = (key: any) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

const filteredAndSortedProducts = computed(() => {
  let result = [...catalog.products]

  // Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(p => 
      p.title.toLowerCase().includes(q) || 
      p.category.toLowerCase().includes(q)
    )
  }

  // Category Filter
  if (categoryFilter.value) {
    result = result.filter(p => p.category === categoryFilter.value)
  }

  // Sort
  result.sort((a, b) => {
    let modifier = sortOrder.value === 'asc' ? 1 : -1
    const valA = (a as any)[sortKey.value]
    const valB = (b as any)[sortKey.value]

    if (typeof valA === 'string') {
      return valA.localeCompare(valB) * modifier
    }
    return ((valA || 0) - (valB || 0)) * modifier
  })

  return result
})

const totalPages = computed(() => Math.ceil(filteredAndSortedProducts.value.length / itemsPerPage))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredAndSortedProducts.value.slice(start, start + itemsPerPage)
})
</script>

<template>
  <section class="grid gap-6">
    <!-- Header Banner -->
    <div class="grid gap-6 rounded-[2.5rem] bg-secondary p-8 shadow-[0_26px_90px_rgba(117,49,108,0.2)] lg:grid-cols-[minmax(0,1.1fr)_minmax(300px,0.9fr)]">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-rose-100">
          <span class="flex h-1.5 w-1.5 rounded-full bg-rose-400 animate-pulse" />
          Admin Portal
        </div>
        <h2 class="mt-4 text-4xl font-black tracking-tight text-white lg:text-5xl">
          Platform Control Panel
        </h2>
        <p class="mt-4 max-w-2xl text-base leading-8 text-rose-50/80">
          Manage your educational assets, monitor revenue, and configure examinations from a central dashboard.
        </p>
      </div>

      <div class="flex flex-col justify-center rounded-[2rem] border border-white/10 bg-white/5 p-8 text-white">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-200">Revenue Monitor</p>
        <p class="mt-2 text-4xl font-black tabular-nums">₦{{ catalog.revenueEstimate.toLocaleString() }}</p>
        <div class="mt-6 flex gap-2">
          <div class="h-1 w-full rounded-full bg-white/10 overflow-hidden">
            <div class="h-full bg-rose-400 w-2/3" />
          </div>
        </div>
        <p class="mt-3 text-[11px] font-bold text-rose-100/60">Estimated platform growth • +12% this month</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <AdminOverviewCard
        v-for="item in dashboardStats"
        :key="item.label"
        :label="item.label"
        :value="item.value"
        :detail="item.detail"
      />
    </div>

    <!-- Management Section -->
    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
      <section class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-[0_20px_60px_rgba(0,0,0,0.03)]">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex flex-col gap-4">
            <h3 class="text-2xl font-black text-slate-900">Resource Catalog</h3>
            <div class="flex flex-wrap gap-2">
                <div class="relative w-full max-w-[240px]">
                    <input 
                      v-model="searchQuery"
                      @input="currentPage = 1"
                      type="text" 
                      placeholder="Search items..." 
                      class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-4 text-xs font-bold outline-none focus:border-primary/30 focus:ring-4 focus:ring-primary/5 transition-all"
                    />
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                
                <select 
                  v-model="categoryFilter" 
                  @change="currentPage = 1"
                  class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-600 outline-none focus:border-primary/30 focus:ring-4 focus:ring-primary/5 transition-all"
                >
                  <option value="">All Categories</option>
                  <option v-for="cat in catalog.categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
            </div>
          </div>

          <!-- Dropdown Logic -->
          <div class="relative group">
            <button class="flex items-center gap-3 rounded-2xl bg-slate-900 px-6 py-4 text-sm font-black text-white shadow-xl transition hover:bg-primary active:scale-95">
              <span>+ Add New Resource</span>
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div class="invisible absolute right-0 top-full z-10 mt-2 w-56 translate-y-2 rounded-2xl border border-slate-100 bg-white p-2 opacity-0 shadow-2xl transition-all group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
              <button @click="openAddModal('material')" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-xs font-black text-slate-700 hover:bg-slate-50">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">📄</span>
                Study Material
              </button>
              <button @click="openAddModal('cbt')" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-xs font-black text-slate-700 hover:bg-slate-50">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-50 text-blue-600">⏳</span>
                CBT Exam
              </button>
              <div class="my-1 border-t border-slate-50" />
              <button @click="openAddModal('bulk')" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-xs font-black text-primary hover:bg-rose-50">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-rose-50 text-primary">📦</span>
                Bulk Upload
              </button>
            </div>
          </div>
        </div>

        <!-- DataTable -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <th @click="toggleSort('title')" class="px-4 py-4 cursor-pointer hover:text-primary transition-colors">
                  <div class="flex items-center gap-1.5">
                    Title & Type
                    <span v-if="sortKey === 'title'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th @click="toggleSort('category')" class="px-4 py-4 cursor-pointer hover:text-primary transition-colors">
                   <div class="flex items-center gap-1.5">
                    Category
                    <span v-if="sortKey === 'category'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th @click="toggleSort('accessType')" class="px-4 py-4 cursor-pointer hover:text-primary transition-colors">
                   <div class="flex items-center gap-1.5">
                    Access
                    <span v-if="sortKey === 'accessType'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th @click="toggleSort('price')" class="px-4 py-4 cursor-pointer hover:text-primary transition-colors">
                   <div class="flex items-center gap-1.5">
                    Pricing
                    <span v-if="sortKey === 'price'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th class="px-4 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="product in paginatedProducts" :key="product.id" class="group hover:bg-slate-50/50">
                <td class="px-4 py-5">
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-bold" :class="product.type === 'cbt' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600'">
                      {{ product.type === 'cbt' ? 'CBT' : 'PDF' }}
                    </div>
                    <div>
                      <p class="text-sm font-black text-slate-800">{{ product.title }}</p>
                      <p class="text-[10px] font-bold text-slate-400">{{ product.format }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-5 text-xs font-bold text-slate-600">{{ product.category }}</td>
                <td class="px-4 py-5">
                  <span class="rounded-full px-2 py-1 text-[9px] font-black uppercase tracking-wider" :class="product.accessType === 'paid' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'">
                    {{ product.accessType }}
                  </span>
                </td>
                <td class="px-4 py-5 text-sm font-black text-slate-900">₦{{ product.price?.toLocaleString() || '0' }}</td>
                <td class="px-4 py-5 text-right">
                  <button @click="deleteItem(product.id)" class="rounded-lg p-2 text-slate-300 hover:bg-rose-50 hover:text-rose-500 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- Pagination & Empty State -->
          <div v-if="filteredAndSortedProducts.length > 0" class="mt-8 flex items-center justify-between border-t border-slate-50 pt-6">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
              Showing {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredAndSortedProducts.length) }} of {{ filteredAndSortedProducts.length }}
            </p>
            <div class="flex gap-2">
              <button 
                @click="currentPage--" 
                :disabled="currentPage === 1"
                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent"
              >
                Previous
              </button>
              <div class="flex items-center gap-1">
                <button 
                  v-for="page in totalPages" 
                  :key="page"
                  @click="currentPage = page"
                  class="h-8 w-8 rounded-lg text-xs font-black transition-all"
                  :class="currentPage === page ? 'bg-primary text-white shadow-lg' : 'text-slate-400 hover:bg-slate-50'"
                >
                  {{ page }}
                </button>
              </div>
              <button 
                @click="currentPage++" 
                :disabled="currentPage === totalPages"
                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent"
              >
                Next
              </button>
            </div>
          </div>

          <div v-if="filteredAndSortedProducts.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
             <p class="text-sm font-bold">{{ searchQuery ? 'No results found.' : 'Your catalog is empty.' }}</p>
             <p class="text-[10px] mt-1">{{ searchQuery ? 'Try adjusting your search filters.' : 'Start by adding your first material or CBT exam.' }}</p>
          </div>
        </div>
      </section>

      <AdminQuickLinks />
    </div>

    <!-- Modals -->
    <ProductModal v-if="showModal" :type="modalType" @close="showModal = false" />
  </section>
</template>