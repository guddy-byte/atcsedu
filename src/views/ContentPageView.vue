<script setup lang="ts">
defineProps<{
  eyebrow: string
  title: string
  description: string
  accent: string
}>()
</script>

<template>
  <section class="rounded-[2rem] border border-white/75 bg-white/88 p-8 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">{{ eyebrow }}</p>
    <h2 class="mt-3 max-w-4xl text-4xl font-extrabold tracking-tight text-slate-950">
      {{ title }}
    </h2>
    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">{{ description }}</p>

    <div class="mt-8 rounded-[1.5rem] bg-rose-50 p-6">
      <p class="text-sm leading-7 text-slate-700">{{ accent }}</p>
    </div>
  </section>
</template>