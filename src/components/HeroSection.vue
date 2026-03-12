<script setup lang="ts">
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
  <section class="relative overflow-hidden rounded-[2.2rem] border border-white/70 bg-[linear-gradient(180deg,#f7f7f7_0%,#ececec_100%)] px-6 py-8 shadow-[0_24px_80px_rgba(15,23,42,0.12),0_3px_0_rgba(255,255,255,0.75)_inset,0_-12px_24px_rgba(15,23,42,0.04)_inset] sm:px-8 sm:py-10 lg:px-10 lg:py-12 xl:px-12">
    <div class="pointer-events-none absolute inset-x-10 top-0 h-16 rounded-full bg-white/65 blur-2xl" />
    <div class="pointer-events-none absolute -bottom-10 left-10 h-24 w-24 rounded-full bg-white/40 blur-3xl" />
    <div class="grid items-center gap-10 xl:grid-cols-[minmax(0,1.12fr)_minmax(420px,0.88fr)] xl:gap-12">
      <div class="grid gap-6 lg:gap-8">
        <div class="hero-copy-slide grid gap-4">
          <h2 class="max-w-4xl text-[40px] leading-[1.02] font-semibold tracking-[-0.04em] text-[#131313] sm:text-[2.35rem] lg:text-[2rem] xl:text-[2.55rem]">
            Exam Materials Platform for Students & Digital Learning.
          </h2>
          <p class="max-w-3xl text-[14px] leading-[1.75] text-[#5f6470] sm:text-[14px]">
            Explore free and paid exam materials, guided preparation resources, and a student-first
            platform built for confident learning.
          </p>
        </div>

        <div class="hero-trust-fade flex flex-wrap items-center gap-4 text-[#51607b] sm:gap-5">
          <AvatarGroup :avatars="trustedUsers" />
          <p class="text-base font-medium sm:text-[1.05rem]">Trusted by 100+ Students</p>
        </div>
      </div>

      <div class="hero-slide-right flex justify-center xl:justify-end">
        <div class="relative w-full max-w-[440px] pb-8 pt-3">
          <div class="absolute inset-x-10 bottom-0 h-10 rounded-full bg-black/10 blur-2xl" />
          <img
            :src="heroImage"
            alt="Hero illustration"
            class="hero-pulse relative mx-auto w-full max-w-[350px] drop-shadow-[0_28px_55px_rgba(0,0,0,0.14)] sm:max-w-[390px] xl:max-w-[420px]"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero-copy-slide {
  animation: hero-copy-slide 820ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.hero-slide-right {
  animation: hero-slide-right 760ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.hero-trust-fade {
  animation: hero-trust-fade 900ms ease-out 180ms both;
}

.hero-pulse {
  animation: hero-pulse 3.2s ease-in-out 900ms infinite;
}

@keyframes hero-copy-slide {
  from {
    opacity: 0;
    transform: translate3d(-22px, 0, 0);
  }

  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes hero-trust-fade {
  from {
    opacity: 0;
    transform: translate3d(0, 8px, 0);
  }

  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes hero-slide-right {
  from {
    opacity: 0;
    transform: translate3d(26px, 0, 0);
  }

  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes hero-pulse {
  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.025);
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-copy-slide,
  .hero-slide-right,
  .hero-trust-fade,
  .hero-pulse {
    animation: none;
  }
}
</style>