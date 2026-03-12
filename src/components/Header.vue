<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import logoImage from '../images/logo.png'
import { resolveProtectedStudentRoute } from '../utils/studentAuth'

const route = useRoute()
const mobileMenuOpen = ref(false)

const menuItems = [
  { label: 'Home', path: '/' },
  { label: 'Free Material', path: '/materials/free' },
  { label: 'Paid Material', path: '/materials/paid' },
  { label: 'Exam Training', path: '/exam-training', requiresStudentAuth: true },
  { label: 'Contact Us', path: '/contact' },
]

const isActive = computed(() => (path: string) => {
  if (route.path === path) {
    return true
  }

  return (
    typeof route.query.redirect === 'string' &&
    route.query.redirect === path &&
    (route.path === '/auth/signup' || route.path === '/auth/login')
  )
})

const getMenuTarget = (path: string, requiresStudentAuth = false) =>
  requiresStudentAuth ? resolveProtectedStudentRoute(path) : path

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

watch(
  () => route.path,
  () => {
    closeMobileMenu()
  },
)
</script>

<template>
  <header class="sticky top-0 z-40 pt-5">
    <div class="header-shell rounded-[1.75rem] px-4 py-4 backdrop-blur-xl sm:px-5 xl:px-6">
      <div class="flex flex-nowrap items-center justify-between gap-3 xl:gap-4">
        <div class="flex shrink-0 items-center gap-3 sm:gap-4">
          <RouterLink to="/" class="flex items-center">
            <img
              :src="logoImage"
              alt="ATCS Edu logo"
              class="h-12 w-auto object-contain sm:h-14"
            />
          </RouterLink>

          <RouterLink
            to="/admin/auth/login"
            class="header-chip hidden shrink-0 rounded-full px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] 2xl:inline-flex"
          >
            Admin
          </RouterLink>
        </div>

        <nav class="mx-3 hidden min-w-0 flex-1 items-center justify-center overflow-x-auto whitespace-nowrap [scrollbar-width:none] lg:flex">
          <div class="flex flex-nowrap items-center gap-1.5 xl:gap-2">
            <RouterLink
              v-for="item in menuItems"
              :key="item.path"
              :to="getMenuTarget(item.path, item.requiresStudentAuth)"
              class="header-menu-link shrink-0 px-3 py-2.5 text-sm font-medium transition xl:px-4"
              :class="
                isActive(item.path)
                  ? 'header-menu-link-active'
                  : 'header-menu-link-idle'
              "
            >
              {{ item.label }}
            </RouterLink>
          </div>
        </nav>

        <div class="hidden shrink-0 items-center gap-2 xl:flex xl:gap-3">
          <RouterLink
            to="/auth/login"
            class="header-secondary-link hidden shrink-0 rounded-full px-3 py-2.5 text-sm font-semibold xl:inline-flex"
          >
            Student Login
          </RouterLink>
          <RouterLink
            to="/auth/signup"
            class="header-cta inline-flex shrink-0 items-center justify-center rounded-full px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 xl:px-5"
          >
            Get Started
          </RouterLink>
        </div>

        <button
          type="button"
          class="header-hamburger inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl lg:hidden"
          :aria-expanded="mobileMenuOpen"
          aria-label="Toggle navigation menu"
          @click="toggleMobileMenu"
        >
          <span class="header-hamburger-lines" :class="{ open: mobileMenuOpen }">
            <span />
            <span />
            <span />
          </span>
        </button>
      </div>
    </div>

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileMenuOpen"
        class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-[2px] lg:hidden"
        @click="closeMobileMenu"
      />
    </Transition>

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-x-full opacity-80"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="translate-x-full opacity-80"
    >
      <aside
        v-if="mobileMenuOpen"
        class="fixed right-0 top-0 z-50 flex h-screen w-[min(88vw,360px)] flex-col bg-white px-5 pb-6 pt-5 shadow-[0_24px_80px_rgba(15,23,42,0.22)] lg:hidden"
      >
        <div class="flex items-center justify-between gap-4 border-b border-black/6 pb-4">
          <img
            :src="logoImage"
            alt="ATCS Edu logo"
            class="h-11 w-auto object-contain"
          />

          <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-black/8 text-slate-600"
            aria-label="Close navigation menu"
            @click="closeMobileMenu"
          >
            <span class="text-xl leading-none">×</span>
          </button>
        </div>

        <nav class="mt-6 grid gap-2">
          <RouterLink
            v-for="item in menuItems"
            :key="`${item.path}-mobile-drawer`"
            :to="getMenuTarget(item.path, item.requiresStudentAuth)"
            class="rounded-2xl px-4 py-3 text-sm font-semibold transition"
            :class="
              isActive(item.path)
                ? 'bg-[color-mix(in_srgb,var(--color-primary)_10%,white)] text-[color-mix(in_srgb,var(--color-primary)_88%,black_8%)]'
                : 'text-slate-700 hover:bg-[color-mix(in_srgb,var(--color-primary)_5%,white)] hover:text-[color-mix(in_srgb,var(--color-primary)_84%,black_10%)]'
            "
            @click="closeMobileMenu"
          >
            {{ item.label }}
          </RouterLink>
        </nav>

        <div class="mt-6 grid gap-3 border-t border-black/6 pt-5">
          <RouterLink
            to="/auth/login"
            class="header-secondary-link inline-flex items-center justify-center rounded-full px-4 py-3 text-sm font-semibold"
            @click="closeMobileMenu"
          >
            Student Login
          </RouterLink>

          <RouterLink
            to="/auth/signup"
            class="header-cta inline-flex items-center justify-center rounded-full px-4 py-3 text-sm font-semibold text-white"
            @click="closeMobileMenu"
          >
            Get Started
          </RouterLink>
        </div>
      </aside>
    </Transition>
  </header>
</template>