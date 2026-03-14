<script setup lang="ts">
defineProps<{
  label: string
  value: string | number
  detail: string
}>()
</script>

<template>
  <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6 text-white shadow-xl shadow-slate-200/60 transition-transform hover:-translate-y-1">
    <h6 class="text-sm font-bold uppercase tracking-[0.24em] text-rose-300/80">{{ label }}</h6>
    <strong class="mt-4 block text-4xl font-black tracking-tight leading-none">{{ value }}</strong>
    <p class="mt-3 text-sm font-medium leading-7 text-slate-400">{{ detail }}</p>
  </article>
</template>