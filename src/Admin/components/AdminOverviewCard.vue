<script setup lang="ts">
defineProps<{
  label: string
  value: string | number
  detail: string
}>()
</script>

<template>
  <article class="rounded-[1.5rem] border border-white/10 bg-white/8 p-5 text-white">
    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-100/80">{{ label }}</p>
    <strong class="mt-3 block text-3xl font-extrabold tracking-tight">{{ value }}</strong>
    <p class="mt-2 text-sm leading-6 text-rose-50/85">{{ detail }}</p>
  </article>
</template>