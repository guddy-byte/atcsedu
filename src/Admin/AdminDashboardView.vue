<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import AdminOverviewCard from './components/AdminOverviewCard.vue'
import ProductModal from './components/ProductModal.vue'
import { apiRequest } from '../lib/api'
import { useCatalogStore, type Product } from '../stores/catalog'

interface AdminDashboardStats {
  students: number
  materials: number
  exams: number
  attempts: number
  revenue_total: number
  pending_theory_reviews: number
}

interface AdminDashboardStatsResponse {
  status: string
  data: AdminDashboardStats
}

const catalog = useCatalogStore()

const defaultStats = (): AdminDashboardStats => ({
  students: 0,
  materials: 0,
  exams: 0,
  attempts: 0,
  revenue_total: 0,
  pending_theory_reviews: 0,
})

const dashboard = ref<AdminDashboardStats>(defaultStats())
const showModal = ref(false)
const modalType = ref<'material' | 'cbt' | 'bulk'>('material')
const editProduct = ref<Product | null>(null)
const loadError = ref('')
const isLoading = ref(false)
const isEditingResource = ref(false)

const searchQuery = ref('')
const categoryFilter = ref('')
const typeFilter = ref('')
const sortKey = ref<'title' | 'category' | 'accessType' | 'price'>('title')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const itemsPerPage = 8

const dashboardStats = computed(() => [
  {
    label: 'Students',
    value: dashboard.value.students,
    detail: dashboard.value.students
      ? 'Student accounts that have been created in the platform.'
      : 'No student accounts have been created yet.',
  },
  {
    label: 'Materials',
    value: dashboard.value.materials,
    detail: dashboard.value.materials
      ? 'Published study materials currently visible in the catalog.'
      : 'No study materials are published yet.',
  },
  {
    label: 'Exam Trainings',
    value: dashboard.value.exams,
    detail: dashboard.value.exams
      ? 'Live CBT exam entries currently available.'
      : 'No exam training tests are published yet.',
  },
  {
    label: 'Attempts',
    value: dashboard.value.attempts,
    detail: dashboard.value.attempts
      ? 'Submitted exam attempts recorded by the API.'
      : 'No exam attempts have been recorded yet.',
  },
  {
    label: 'Pending Reviews',
    value: dashboard.value.pending_theory_reviews,
    detail: dashboard.value.pending_theory_reviews
      ? 'Theory answers still waiting for manual grading.'
      : 'There are no pending theory reviews right now.',
  },
  {
    label: 'Revenue',
    value: `NGN ${dashboard.value.revenue_total.toLocaleString()}`,
    detail: dashboard.value.revenue_total
      ? 'Paid purchase value recorded by the backend.'
      : 'No paid purchases have been recorded yet.',
  },
])

const totalResources = computed(() => dashboard.value.materials + dashboard.value.exams)

const refreshDashboard = async (forceCatalog = false) => {
  isLoading.value = true
  loadError.value = ''

  try {
    await catalog.initialize(forceCatalog)
    const response = await apiRequest<AdminDashboardStatsResponse>('/admin/dashboard/stats')
    dashboard.value = response.data
  } catch (error) {
    loadError.value = error instanceof Error ? error.message : 'Unable to load the admin dashboard.'
  } finally {
    isLoading.value = false
  }
}

const openAddModal = (type: 'material' | 'cbt' | 'bulk') => {
  modalType.value = type
  editProduct.value = null
  showModal.value = true
}

const handleModalClose = () => {
  showModal.value = false
  editProduct.value = null
  void refreshDashboard(true)
}

const openEditModal = async (product: Product) => {
  modalType.value = product.type === 'cbt' ? 'cbt' : 'material'
  isEditingResource.value = true
  loadError.value = ''

  try {
    editProduct.value = product.type === 'cbt'
      ? await catalog.fetchAdminExam(product.id)
      : product

    showModal.value = true
  } catch (error) {
    loadError.value = error instanceof Error ? error.message : 'Unable to load this resource.'
  } finally {
    isEditingResource.value = false
  }
}

const deleteItem = async (product: Product) => {
  if (!confirm(`Delete "${product.title}"? This action cannot be undone.`)) {
    return
  }

  try {
    await catalog.deleteProduct(product.id)
    await refreshDashboard(true)
  } catch (error) {
    loadError.value = error instanceof Error ? error.message : 'Unable to delete this resource.'
  }
}

const toggleSort = (key: 'title' | 'category' | 'accessType' | 'price') => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    return
  }

  sortKey.value = key
  sortOrder.value = 'asc'
}

const filteredAndSortedProducts = computed(() => {
  let result = [...catalog.products]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((product) =>
      product.title.toLowerCase().includes(query) ||
      product.category.toLowerCase().includes(query),
    )
  }

  if (categoryFilter.value) {
    result = result.filter((product) => product.category === categoryFilter.value)
  }

  if (typeFilter.value) {
    result = result.filter((product) => product.type === typeFilter.value)
  }

  result.sort((left, right) => {
    const modifier = sortOrder.value === 'asc' ? 1 : -1
    const leftValue = left[sortKey.value]
    const rightValue = right[sortKey.value]

    if (typeof leftValue === 'string' && typeof rightValue === 'string') {
      return leftValue.localeCompare(rightValue) * modifier
    }

    return ((Number(leftValue) || 0) - (Number(rightValue) || 0)) * modifier
  })

  return result
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredAndSortedProducts.value.length / itemsPerPage)))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredAndSortedProducts.value.slice(start, start + itemsPerPage)
})

watch(filteredAndSortedProducts, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value
  }
})

onMounted(() => {
  void refreshDashboard()
})
</script>

<template>
  <section class="grid gap-6">
    <div
      v-if="loadError || catalog.loadError"
      class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"
    >
      {{ loadError || catalog.loadError }}
    </div>

    <div class="grid gap-6 rounded-[2.5rem] bg-secondary p-8 shadow-[0_26px_90px_rgba(117,49,108,0.2)] lg:grid-cols-[minmax(0,1.1fr)_minmax(300px,0.9fr)]">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-rose-100">
          <span class="flex h-1.5 w-1.5 rounded-full bg-rose-300" />
          Admin Portal
        </div>
        <h2 class="mt-4 text-4xl font-black tracking-tight text-white lg:text-5xl">
          Fresh dashboard, real data only.
        </h2>
        <p class="mt-4 max-w-2xl text-base leading-8 text-rose-50/85">
          Dummy catalog content has been removed. This dashboard now stays clean until you publish
          actual materials, pricing, and exam trainings from the admin area.
        </p>
      </div>

      <div class="flex flex-col justify-center rounded-[2rem] border border-white/10 bg-white/5 p-8 text-white">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-200">Catalog Status</p>
        <p class="mt-2 text-4xl font-black tabular-nums">{{ totalResources }}</p>
        <p class="mt-3 text-sm font-semibold text-rose-50/90">
          {{ totalResources ? 'Live resources are currently published.' : 'No materials or exam trainings are live yet.' }}
        </p>
        <p class="mt-2 text-[11px] font-bold uppercase tracking-[0.14em] text-rose-100/70">
          Revenue: NGN {{ dashboard.revenue_total.toLocaleString() }}
        </p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <AdminOverviewCard
        v-for="item in dashboardStats"
        :key="item.label"
        :label="item.label"
        :value="item.value"
        :detail="item.detail"
      />
    </div>

    <div class="grid gap-6">
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
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <select
                v-model="categoryFilter"
                @change="currentPage = 1"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-600 outline-none focus:border-primary/30 focus:ring-4 focus:ring-primary/5 transition-all"
              >
                <option value="">All Categories</option>
                <option v-for="category in catalog.categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
              <select
                v-model="typeFilter"
                @change="currentPage = 1"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-600 outline-none focus:border-primary/30 focus:ring-4 focus:ring-primary/5 transition-all"
              >
                <option value="">All Types</option>
                <option value="material">Materials</option>
                <option value="cbt">CBT Exams</option>
              </select>
            </div>
          </div>
          <div class="relative group">
            <button class="flex items-center gap-3 rounded-2xl bg-slate-900 px-6 py-4 text-sm font-black text-white shadow-xl transition hover:bg-primary active:scale-95">
              <span>+ Add New Resource</span>
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div class="invisible absolute right-0 top-full z-10 mt-2 w-56 translate-y-2 rounded-2xl border border-slate-100 bg-white p-2 opacity-0 shadow-2xl transition-all group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
              <button @click="openAddModal('material')" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-xs font-black text-slate-700 hover:bg-slate-50">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">📄</span> Study Material 
              </button>
              <button @click="openAddModal('cbt')" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-xs font-black text-slate-700 hover:bg-slate-50">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-50 text-blue-600">⏳</span> CBT Exam 
              </button>
              <div class="my-1 border-t border-slate-50"></div>
              <button @click="openAddModal('bulk')" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-xs font-black text-primary hover:bg-rose-50">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-rose-50 text-primary">📦</span> Bulk Upload 
              </button>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <th @click="toggleSort('title')" class="cursor-pointer px-4 py-4 transition-colors hover:text-primary">
                  <div class="flex items-center gap-1.5">
                    Title and Type
                    <span v-if="sortKey === 'title'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th @click="toggleSort('category')" class="cursor-pointer px-4 py-4 transition-colors hover:text-primary">
                  <div class="flex items-center gap-1.5">
                    Category
                    <span v-if="sortKey === 'category'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th @click="toggleSort('accessType')" class="cursor-pointer px-4 py-4 transition-colors hover:text-primary">
                  <div class="flex items-center gap-1.5">
                    Access
                    <span v-if="sortKey === 'accessType'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th @click="toggleSort('price')" class="cursor-pointer px-4 py-4 transition-colors hover:text-primary">
                  <div class="flex items-center gap-1.5">
                    Pricing
                    <span v-if="sortKey === 'price'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                  </div>
                </th>
                <th class="px-4 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="product in paginatedProducts" :key="`${product.type}-${product.id}`" class="group hover:bg-slate-50/50">
                <td class="px-4 py-5">
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-bold"
                      :class="product.type === 'cbt' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600'"
                    >
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
                  <span
                    class="rounded-full px-2 py-1 text-[9px] font-black uppercase tracking-wider"
                    :class="product.accessType === 'paid' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'"
                  >
                    {{ product.accessType }}
                  </span>
                </td>
                <td class="px-4 py-5 text-sm font-black text-slate-900">NGN {{ product.price?.toLocaleString() || '0' }}</td>
                <td class="px-4 py-5 text-right">
                  <button
                    :disabled="isEditingResource"
                    @click="void openEditModal(product)"
                    class="mr-2 rounded-lg p-2 text-slate-300 transition-colors hover:bg-blue-50 hover:text-blue-500 disabled:opacity-50"
                    title="Edit"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.536-6.536a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-2.828 0L9 13z" />
                    </svg>
                  </button>
                  <button
                    @click="void deleteItem(product)"
                    class="rounded-lg p-2 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500"
                    title="Delete"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        <div v-if="filteredAndSortedProducts.length > 0" class="mt-8 flex items-center justify-between border-t border-slate-50 pt-6">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
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

        <div v-if="filteredAndSortedProducts.length === 0" class="flex flex-col items-center justify-center py-20 text-center text-slate-400">
          <p class="text-sm font-bold text-slate-600">
            {{ searchQuery ? 'No resources matched your search.' : 'Your dashboard is clean and empty.' }}
          </p>
          <p class="mt-1 text-[11px] font-medium text-slate-400">
            {{ searchQuery ? 'Try a different keyword or clear the category filter.' : 'Add your first real study material or CBT exam when you are ready.' }}
          </p>
        </div>
        </div>
      </section>
    </div>

    <ProductModal
      v-if="showModal"
      :type="modalType"
      :editProduct="editProduct"
      @close="handleModalClose"
    />
  </section>
</template>
