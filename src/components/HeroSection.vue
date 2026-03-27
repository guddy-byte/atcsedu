<script setup lang="ts">
import { RouterLink } from 'vue-router'

import heroImage from '../images/hero.png'
</script>

<template>
  <section class="relative overflow-hidden rounded-[2.2rem] border border-white/70 bg-[linear-gradient(160deg,#ec4899_0%,#e11d48_45%,#7c3aed_100%)] px-6 py-8 text-white shadow-[0_28px_80px_rgba(15,23,42,0.28)] sm:px-8 sm:py-10 lg:px-10 lg:py-12 xl:px-12">
    <div class="pointer-events-none absolute -right-16 top-[-120px] h-[260px] w-[260px] rounded-full bg-white/18 blur-3xl" />
    <div class="pointer-events-none absolute -bottom-20 -left-8 h-[180px] w-[180px] rounded-full bg-white/20 blur-3xl" />

    <div class="relative grid items-center gap-10 xl:grid-cols-[minmax(0,1.08fr)_minmax(380px,0.92fr)] xl:gap-12">
      <div class="grid gap-6 lg:gap-7">
        <div class="grid max-w-2xl gap-4">
          <p class="w-fit rounded-full bg-white/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]">
            Fresh platform
          </p>
          <h2 class="text-[2.05rem] leading-[1.05] font-extrabold tracking-[-0.04em] sm:text-[2.45rem] lg:text-[2.8rem]">
            Start from a clean catalog and publish only real content.
          </h2>
          <p class="text-sm leading-7 text-rose-50/95 sm:text-base">
            The landing page is now set up as a fresh workspace. Add your actual materials, pricing,
            and exam training items from the admin area whenever you are ready to go live.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <RouterLink
            to="/materials/free"
            class="inline-flex items-center justify-center rounded-full border border-white/45 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/20"
          >
            View live catalog
          </RouterLink>
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
