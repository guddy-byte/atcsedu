<script setup lang="ts">
import { computed, ref } from 'vue'

import { useCatalogStore } from '../stores/catalog'
import type { Product } from '../stores/catalog'
import logoImage from '../images/logo.png'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  pay: [productId: string]
}>()

const catalog = useCatalogStore()
const viewerVisible = ref(false)

const DESCRIPTION_WORD_LIMIT = 12

const descriptionWords = computed(() =>
  props.product.description.trim().split(/\s+/).filter(Boolean),
)

const isDescriptionTruncated = computed(() => descriptionWords.value.length > DESCRIPTION_WORD_LIMIT)

const shortDescription = computed(() =>
  isDescriptionTruncated.value
    ? `${descriptionWords.value.slice(0, DESCRIPTION_WORD_LIMIT).join(' ')}...`
    : props.product.description,
)

const statusText = computed(() =>
  props.product.accessType === 'free' ? 'Free Material' : 'Paid Material',
)

const formattedPrice = computed(() =>
  new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
  }).format(props.product.price),
)

const downloadUrl = computed(() => props.product.downloadUrl ?? props.product.imageUrl)

const isPdfAsset = computed(() => /\.pdf(?:$|[?#])/i.test(downloadUrl.value))

const isPurchased = computed(() => catalog.purchasedIds.includes(props.product.id))
const isUnlocked = computed(() => props.product.accessType === 'free' || isPurchased.value)

const downloadFileName = computed(() => {
  const sanitizedTitle = props.product.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')
  return isPdfAsset.value ? `${sanitizedTitle}.pdf` : sanitizedTitle
})

const statusClass = computed(() =>
  props.product.accessType === 'free'
    ? 'text-[13px] font-semibold text-secondary'
    : 'text-[13px] font-semibold text-secondary',
)

const triggerFreeDownload = () => {
  if (typeof window === 'undefined' || !downloadUrl.value) {
    return
  }

  const link = document.createElement('a')
  link.href = downloadUrl.value
  link.rel = 'noreferrer'

  if (isPdfAsset.value) {
    link.download = downloadFileName.value
  } else {
    link.target = '_blank'
  }

  document.body.appendChild(link)
  link.click()
  link.remove()
}

const openPaidMaterial = () => {
  if (!downloadUrl.value) {
    return
  }

  if (isPdfAsset.value) {
    viewerVisible.value = true
    return
  }

  // Other file types can be opened in a new tab; avoid direct download for paid content
  window.open(downloadUrl.value, '_blank', 'noopener')
}
</script>

<template>
  <article class="grid gap-4 rounded-[1.3rem] border border-rose-100 bg-white p-4 shadow-[0_12px_34px_rgba(117,49,108,0.08)] transition hover:-translate-y-1 hover:shadow-[0_16px_40px_rgba(117,49,108,0.12)]">
    <div class="flex items-start justify-between gap-3">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-[1rem] bg-[linear-gradient(135deg,rgba(237,69,97,0.12),rgba(117,49,108,0.14))] p-1">
        <img :src="logoImage" alt="ATCS Edu logo" class="h-full w-full object-contain" />
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2">
        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-primary">
          {{ product.category }}
        </span>
        <span :class="statusClass">{{ statusText }}</span>
      </div>
    </div>

    <div class="group relative">
      <h3 class="text-[15px] font-bold tracking-tight text-slate-950">{{ product.title }}</h3>
      <p class="mt-1.5 text-[13px] leading-6 text-slate-600">{{ shortDescription }}</p>

      <div
        v-if="isDescriptionTruncated"
        class="pointer-events-none absolute left-0 top-full z-20 mt-2 hidden w-[min(18rem,100%)] rounded-[0.9rem] bg-[#111111] px-3 py-2 text-[12px] leading-5 text-white shadow-[0_14px_28px_rgba(15,23,42,0.24)] group-hover:block"
      >
        {{ product.description }}
      </div>
    </div>

    <div class="grid gap-2.5 rounded-[1rem] bg-rose-50/70 p-3.5">
      <div class="flex items-center justify-between gap-4">
        <span class="text-[10px] font-medium uppercase tracking-[0.15em] text-slate-400">
          {{ product.accessType === 'free' ? 'Instant access' : 'Premium access' }}
        </span>
        <span class="text-[12px] font-semibold text-slate-500">{{ product.format }}</span>
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-rose-100 pt-2.5">
        <button
          v-if="product.accessType === 'free'"
          type="button"
          class="inline-flex cursor-pointer items-center justify-center rounded-full bg-rose-50 px-3.5 py-2 text-[12px] font-semibold text-primary transition hover:bg-rose-100"
          @click="triggerFreeDownload"
        >
          Download now
        </button>

        <span
          v-if="product.accessType !== 'free'"
          class="text-[18px] font-extrabold tracking-[-0.03em] text-[#111111]"
        >
          {{ formattedPrice }}
        </span>

        <button
          v-if="product.accessType !== 'free' && !isUnlocked"
          class="inline-flex min-w-[88px] items-center justify-center rounded-full bg-primary px-5 py-2 text-[12px] font-semibold text-white transition hover:bg-secondary"
          @click="emit('pay', product.id)"
        >
          Pay
        </button>

        <button
          v-if="product.accessType !== 'free' && isUnlocked"
          class="inline-flex min-w-[88px] items-center justify-center rounded-full bg-green-600 px-5 py-2 text-[12px] font-semibold text-white transition hover:bg-emerald-500"
          @click="openPaidMaterial"
        >
          View now
        </button>
      </div>
    </div>
  </article>

  <div
    v-if="viewerVisible"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
  >
    <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
      <button
        type="button"
        class="absolute right-3 top-3 z-10 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200"
        @click="viewerVisible = false"
      >
        Close
      </button>
      <iframe
        v-if="isPdfAsset"
        :src="downloadUrl"
        class="h-[80vh] w-full border-0"
        sandbox="allow-scripts allow-same-origin"
      />
      <div v-else class="p-6 text-sm text-slate-700">Cannot preview this format in-platform. <a :href="downloadUrl" target="_blank" rel="noreferrer" class="font-bold text-primary">Open in new tab</a>.</div>
    </div>
  </div>
</template>