<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'

import { useCatalogStore, type Product } from '../stores/catalog'
import { getApiBaseUrl, getApiToken } from '../lib/api'

const catalog = useCatalogStore()
const route = useRoute()

// ── Sections ────────────────────────────────────────────────────────────────
const freeProducts = computed(() =>
  catalog.products.filter(
    p => p.type === 'material' && p.accessType === 'free' && catalog.isFreeMaterialDownloaded(p.id),
  ),
)

const purchasedPaid = computed(() =>
  catalog.products.filter(
    p =>
      p.type === 'material' &&
      p.accessType === 'paid' &&
      catalog.isProductPurchased(p.id, p.type),
  ),
)

const recentlyPurchasedMaterialId = computed(() =>
  typeof route.query.purchased_material === 'string' ? route.query.purchased_material : '',
)

const showPurchaseSuccess = computed(() => route.query.purchase === 'success')

const scrollToPurchasedMaterial = async () => {
  if (typeof window === 'undefined' || !recentlyPurchasedMaterialId.value) {
    return
  }

  await nextTick()

  window.setTimeout(() => {
    document
      .getElementById(`purchased-material-${recentlyPurchasedMaterialId.value}`)
      ?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }, 150)
}

// ── Free download ────────────────────────────────────────────────────────────
const downloadingId = ref<string | null>(null)
const downloadError = ref('')

const downloadFree = async (material: Product) => {
  if (downloadingId.value) return
  downloadingId.value = material.id
  downloadError.value = ''
  try {
    const token = getApiToken()
    const headers: Record<string, string> = { Accept: 'application/octet-stream' }
    if (token) headers['Authorization'] = `Bearer ${token}`

    const res = await fetch(`${getApiBaseUrl()}/materials/${material.id}/download`, { headers })
    if (!res.ok) throw new Error('Download failed')

    const blob = await res.blob()
    const objectUrl = URL.createObjectURL(blob)

    const disposition = res.headers.get('Content-Disposition') ?? ''
    const nameMatch = disposition.match(/filename="?([^";]+)"?/i)
    const ext = material.downloadUrl?.split('?')[0].split('.').pop()?.toLowerCase() ?? ''
    const fallbackName = `${material.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}${ext ? '.' + ext : ''}`
    const filename = nameMatch ? nameMatch[1] : fallbackName

    const link = document.createElement('a')
    link.href = objectUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(objectUrl)
    catalog.markFreeDownload(material.id)
  } catch {
    downloadError.value = 'Could not download the file. Please try again.'
  } finally {
    downloadingId.value = null
  }
}

// ── Paid viewer ──────────────────────────────────────────────────────────────
const viewingMaterial = ref<Product | null>(null)
const viewerUrl = ref<string | null>(null)
const isLoadingViewer = ref(false)
const viewerError = ref('')

const openViewer = async (material: Product) => {
  if (isLoadingViewer.value) return
  isLoadingViewer.value = true
  viewerError.value = ''
  try {
    const token = getApiToken()
    const res = await fetch(`${getApiBaseUrl()}/materials/${material.id}/view`, {
      headers: {
        Authorization: `Bearer ${token ?? ''}`,
        Accept: 'application/json',
      },
    })
    const json = await res.json()
    if (!res.ok)
      throw new Error(json.message ?? (res.status === 403 ? 'Access denied. Please purchase this material.' : 'Could not load the file.'))

    viewerUrl.value = json.data.url
    viewingMaterial.value = material
  } catch (err) {
    viewerError.value = err instanceof Error ? err.message : 'Failed to open material.'
  } finally {
    isLoadingViewer.value = false
  }
}

const closeViewer = () => {
  viewerUrl.value = null
  viewingMaterial.value = null
  viewerError.value = ''
}

// Close viewer on Escape key
const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && viewingMaterial.value) closeViewer()
}

onMounted(async () => {
  window.addEventListener('keydown', handleKeydown)
  await catalog.initialize()
  await catalog.syncPurchasedMaterials()

  if (showPurchaseSuccess.value) {
    await scrollToPurchasedMaterial()
  }
})

watch(
  () => [showPurchaseSuccess.value, recentlyPurchasedMaterialId.value, purchasedPaid.value.length] as const,
  ([success, materialId, purchasedCount]) => {
    if (success && materialId && purchasedCount > 0) {
      void scrollToPurchasedMaterial()
    }
  },
)

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <div class="min-h-screen bg-[#fafafa]">

    <!-- Page header -->
    <div class="bg-white border-b border-slate-100 px-6 py-10">
      <div class="mx-auto max-w-6xl">
        <p class="text-xs font-black uppercase tracking-widest text-primary mb-1">Student Portal</p>
        <h1 class="text-3xl font-black text-slate-900">My Materials</h1>
        <p class="mt-2 text-sm text-slate-500">
          Free materials are available to download. Purchased materials can be viewed securely in your browser.
        </p>
      </div>
    </div>

    <div class="mx-auto max-w-6xl px-6 py-10 space-y-14">

      <!-- ── My Purchased Materials ─────────────────────────────────────── -->
      <section>
        <div class="flex items-center gap-3 mb-6">
          <div class="h-8 w-8 rounded-xl bg-primary/10 flex items-center justify-center">
            <svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <div>
            <h2 class="text-lg font-black text-slate-900">My Purchased Materials</h2>
            <p class="text-xs text-slate-400 font-medium">View-only — files open securely in your browser</p>
          </div>
          <span v-if="purchasedPaid.length > 0"
            class="ml-auto rounded-full bg-primary/10 px-3 py-1 text-xs font-black text-primary">
            {{ purchasedPaid.length }}
          </span>
        </div>

        <div
          v-if="showPurchaseSuccess && purchasedPaid.length > 0"
          class="mb-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 text-sm font-medium text-emerald-700"
        >
          Payment successful. Your material has been unlocked and added to My Materials below.
        </div>

        <!-- Error -->
        <p v-if="viewerError"
          class="mb-4 flex items-center gap-2 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm font-bold text-rose-600">
          <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          {{ viewerError }}
        </p>

        <!-- Empty state -->
        <div v-if="purchasedPaid.length === 0"
          class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white py-16 text-center">
          <div class="h-14 w-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <p class="font-black text-slate-500">No purchased materials yet</p>
          <p class="text-sm text-slate-400 mt-1">Browse paid materials and unlock them to see them here.</p>
          <a href="/materials/paid"
            class="mt-5 inline-flex items-center rounded-full bg-primary px-5 py-2.5 text-sm font-black text-white hover:bg-secondary transition-colors">
            Browse Paid Materials
          </a>
        </div>

        <!-- Grid -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="material in purchasedPaid"
            :id="`purchased-material-${material.id}`"
            :key="material.id"
            :class="
              material.id === recentlyPurchasedMaterialId
                ? 'group rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm ring-2 ring-emerald-100 transition hover:shadow-md hover:-translate-y-0.5'
                : 'group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md hover:-translate-y-0.5'
            "
          >
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="h-10 w-10 flex-shrink-0 rounded-xl bg-primary/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="flex flex-wrap gap-1.5 justify-end">
                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-primary">
                  {{ material.category }}
                </span>
                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-600">
                  Purchased
                </span>
              </div>
            </div>

            <h3 class="font-black text-slate-800 text-sm leading-snug mb-1">{{ material.title }}</h3>
            <p class="text-xs text-slate-400 font-medium mb-4 line-clamp-2">{{ material.description || 'No description.' }}</p>

            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
              <span class="text-[10px] font-black uppercase text-slate-400">{{ material.format }}</span>
              <button
                @click="openViewer(material)"
                :disabled="isLoadingViewer && viewingMaterial?.id !== material.id"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-xs font-black text-white transition hover:bg-secondary active:scale-95 disabled:opacity-50"
              >
                <svg v-if="isLoadingViewer && viewingMaterial === null" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ material.id === recentlyPurchasedMaterialId ? 'Open now' : 'View' }}
              </button>
            </div>
          </article>
        </div>
      </section>

      <!-- ── Free Materials ─────────────────────────────────────────────── -->
      <section>
        <div class="flex items-center gap-3 mb-6">
          <div class="h-8 w-8 rounded-xl bg-emerald-100 flex items-center justify-center">
            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
          </div>
          <div>
            <h2 class="text-lg font-black text-slate-900">Free Materials</h2>
            <p class="text-xs text-slate-400 font-medium">Download any of these to your device</p>
          </div>
          <span v-if="freeProducts.length > 0"
            class="ml-auto rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">
            {{ freeProducts.length }}
          </span>
        </div>

        <!-- Download error -->
        <p v-if="downloadError"
          class="mb-4 flex items-center gap-2 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm font-bold text-rose-600">
          {{ downloadError }}
        </p>

        <!-- Empty state -->
        <div v-if="freeProducts.length === 0 && !catalog.isLoading"
          class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white py-16 text-center">
          <div class="h-14 w-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
          </div>
          <p class="font-black text-slate-500">No downloaded materials yet</p>
          <p class="text-sm text-slate-400 mt-1">Materials you download from the free section will appear here.</p>
          <a href="/materials/free"
            class="mt-5 inline-flex items-center rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-700 transition-colors">
            Browse Free Materials
          </a>
        </div>

        <!-- Loading -->
        <div v-else-if="catalog.isLoading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="i in 6" :key="i" class="h-40 rounded-2xl bg-slate-100 animate-pulse" />
        </div>

        <!-- Grid -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article v-for="material in freeProducts" :key="material.id"
            class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="h-10 w-10 flex-shrink-0 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="flex flex-wrap gap-1.5 justify-end">
                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-primary">
                  {{ material.category }}
                </span>
                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-emerald-700">
                  Free
                </span>
              </div>
            </div>

            <h3 class="font-black text-slate-800 text-sm leading-snug mb-1">{{ material.title }}</h3>
            <p class="text-xs text-slate-400 font-medium mb-4 line-clamp-2">{{ material.description || 'No description.' }}</p>

            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
              <span class="text-[10px] font-black uppercase text-slate-400">{{ material.format }}</span>
              <button
                @click="downloadFree(material)"
                :disabled="downloadingId === material.id"
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-4 py-2 text-xs font-black text-white transition hover:bg-emerald-700 active:scale-95 disabled:opacity-60 disabled:cursor-wait"
              >
                <svg v-if="downloadingId === material.id" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                {{ downloadingId === material.id ? 'Downloading…' : 'Download' }}
              </button>
            </div>
          </article>
        </div>
      </section>

    </div>

    <!-- ── Full-screen Paid Material Viewer ──────────────────────────────── -->
    <Teleport to="body">
      <div v-if="viewingMaterial || isLoadingViewer"
        class="fixed inset-0 z-[70] flex flex-col bg-slate-950">

        <!-- Top bar -->
        <div class="flex items-center justify-between gap-4 px-5 py-3 bg-slate-900 border-b border-slate-800 flex-shrink-0">
          <div class="flex items-center gap-3 min-w-0">
            <div class="h-7 w-7 flex-shrink-0 rounded-lg bg-primary/20 flex items-center justify-center">
              <svg class="h-3.5 w-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <p class="font-black text-white text-sm truncate">{{ viewingMaterial?.title ?? 'Loading…' }}</p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <span class="hidden sm:inline text-[10px] font-bold text-slate-500 uppercase tracking-widest">View only</span>
            <button @click="closeViewer"
              class="rounded-xl bg-slate-700 px-4 py-2 text-xs font-black text-white hover:bg-slate-600 active:scale-95 transition">
              ✕ Close
            </button>
          </div>
        </div>

        <!-- Loading state -->
        <div v-if="isLoadingViewer" class="flex flex-1 items-center justify-center">
          <div class="flex flex-col items-center gap-4 text-slate-400">
            <svg class="h-10 w-10 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <p class="text-sm font-bold">Loading your material…</p>
          </div>
        </div>

        <!-- Iframe viewer -->
        <div v-else-if="viewerUrl" class="relative flex-1 overflow-hidden">
          <iframe
            :src="viewerUrl"
            class="h-full w-full border-0 bg-white"
            referrerpolicy="no-referrer"
          />
          <!-- Context-menu blocker overlay (pointer-events off so iframe scrolls normally) -->
          <div
            class="absolute inset-0 select-none"
            style="pointer-events: none;"
            @contextmenu.prevent
          />
        </div>

      </div>
    </Teleport>

  </div>
</template>
