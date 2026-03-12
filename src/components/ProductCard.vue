<script setup lang="ts">
import type { Product } from '../stores/catalog'

defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  add: [productId: string]
}>()
</script>

<template>
  <article class="group relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white/88 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(15,23,42,0.14)]">
    <div class="absolute inset-x-0 top-0 h-24 bg-[linear-gradient(135deg,rgba(15,23,42,0.05),rgba(14,165,233,0.12))]" />

    <div class="relative grid gap-5">
      <div class="flex items-center justify-between gap-3">
        <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-800">
          {{ product.category }}
        </span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-slate-600">
          {{ product.format }}
        </span>
      </div>

      <div>
        <h3 class="text-xl font-bold tracking-tight text-slate-950">{{ product.title }}</h3>
        <p class="mt-3 text-sm leading-7 text-slate-600">{{ product.description }}</p>
      </div>

      <div class="flex items-end justify-between gap-4 border-t border-slate-100 pt-4">
        <div>
          <strong class="block text-2xl font-extrabold tracking-tight text-slate-950">
            ${{ product.price }}
          </strong>
          <small class="mt-1 block text-sm text-slate-500">{{ product.stock }} licenses available</small>
        </div>

        <button
          class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition group-hover:bg-sky-700"
          @click="emit('add', product.id)"
        >
          Add to cart
        </button>
      </div>
    </div>
  </article>
</template>