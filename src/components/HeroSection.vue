<script setup lang="ts">
import { RouterLink } from 'vue-router'

import AvatarGroup, { type AvatarGroupItem } from './AvatarGroup.vue'
import heroImage from '../images/hero.png'

const trustedUsers = [
  {
    src: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=120&q=80',
    fallback: 'AM',
    tooltip: 'Amaka',
  },
  {
    src: 'https://images.unsplash.com/photo-1507591064344-4c6ce005b128?auto=format&fit=crop&w=120&q=80',
    fallback: 'TU',
    tooltip: 'Tunde',
  },
  {
    src: 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=120&q=80',
    fallback: 'AI',
    tooltip: 'Aisha',
  },
  {
    src: 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?auto=format&fit=crop&w=120&q=80',
    fallback: 'DA',
    tooltip: 'David',
  },
] satisfies AvatarGroupItem[]
</script>

<template>
  <section class="relative overflow-hidden rounded-[2.2rem] border border-white/70 bg-[linear-gradient(160deg,#ec4899_0%,#e11d48_45%,#7c3aed_100%)] px-6 py-8 text-white shadow-[0_28px_80px_rgba(15,23,42,0.28)] sm:px-8 sm:py-10 lg:px-10 lg:py-12 xl:px-12">
    <div class="pointer-events-none absolute -right-16 top-[-120px] h-[260px] w-[260px] rounded-full bg-white/18 blur-3xl" />
    <div class="pointer-events-none absolute -bottom-20 -left-8 h-[180px] w-[180px] rounded-full bg-white/20 blur-3xl" />

    <div class="relative grid items-center gap-10 xl:grid-cols-[minmax(0,1.08fr)_minmax(380px,0.92fr)] xl:gap-12">
      <div class="grid gap-6 lg:gap-7">
        <div class="grid max-w-2xl gap-4">
          <p class="w-fit rounded-full bg-white/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]">
            Learning made simple
          </p>
          <h2 class="text-[2.05rem] leading-[1.05] font-extrabold tracking-[-0.04em] sm:text-[2.45rem] lg:text-[2.8rem]">
            Find the right exam materials in seconds.
          </h2>
          <p class="text-sm leading-7 text-rose-50/95 sm:text-base">
            Inspired by top learning platforms, this homepage is structured so students can quickly
            spot free resources, premium bundles, and focused exam support as soon as they scroll.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <RouterLink
            to="/materials/free"
            class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-bold text-rose-600 transition hover:-translate-y-0.5"
          >
            Start with free materials
          </RouterLink>
          <RouterLink
            to="/materials/paid"
            class="inline-flex items-center justify-center rounded-full border border-white/45 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/20"
          >
            Explore premium options
          </RouterLink>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
          <article class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-100">Step 1</p>
            <p class="mt-1 text-sm font-semibold">Pick your track</p>
          </article>
          <article class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-100">Step 2</p>
            <p class="mt-1 text-sm font-semibold">Choose format</p>
          </article>
          <article class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-100">Step 3</p>
            <p class="mt-1 text-sm font-semibold">Start learning</p>
          </article>
        </div>

        <div class="flex flex-wrap items-center gap-4 text-rose-50">
          <AvatarGroup :avatars="trustedUsers" />
          <p class="text-sm font-medium sm:text-base">Trusted by 100+ active learners</p>
        </div>
      </div>

      <div class="flex justify-center xl:justify-end">
        <div class="relative w-full max-w-[420px]">
          <div class="absolute inset-x-8 bottom-4 h-12 rounded-full bg-black/20 blur-2xl" />
          <img
            :src="heroImage"
            alt="Students using digital learning materials"
            class="relative mx-auto w-full max-w-[330px] drop-shadow-[0_26px_55px_rgba(0,0,0,0.3)] sm:max-w-[390px]"
          />
        </div>
      </div>
    </div>
  </section>
</template>
