<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import logoImage from '../images/logo.png'
import { getStudentSession, logoutStudent, resolveProtectedStudentRoute } from '../utils/studentAuth'
import { getAdminSession, logoutAdmin } from '../utils/adminAuth'
import { authSessionRef } from '../utils/authSession'

const route = useRoute()
const router = useRouter()
const mobileMenuOpen = ref(false)
const profileMenuOpen = ref(false)

const menuItems = [
  { label: 'Home', path: '/' },
  { label: 'Free Material', path: '/materials/free' },
  { label: 'Paid Material', path: '/materials/paid' },
  { label: 'Exam Training', path: '/exam-training', requiresStudentAuth: true },
  { label: 'Contact Us', path: '/contact' },
]

const studentSession = computed(() => {
  const session = authSessionRef.value

  if (!session || session.user.role !== 'student') {
    return null
  }

  return {
    name: session.user.name,
    email: session.user.email,
    authenticatedAt: session.authenticatedAt,
    source: 'api',
  }
})

const adminSession = computed(() => {
  const session = authSessionRef.value

  if (!session || session.user.role !== 'admin') {
    return null
  }

  return {
    name: session.user.name,
    email: session.user.email,
    authenticatedAt: session.authenticatedAt,
  }
})

const isAuthenticated = computed(() => !!authSessionRef.value)
const isStudentDashboard = computed(() => route.path.startsWith('/exam-training') && !!studentSession.value)
const isAdminDashboard = computed(() => route.path.startsWith('/admin') && !!adminSession.value)
const isMainDashboard = computed(() => isAdminDashboard.value)

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

const closeProfileMenu = () => {
  profileMenuOpen.value = false
}

const toggleProfileMenu = () => {
  profileMenuOpen.value = !profileMenuOpen.value
}

const logoutAndGoHome = async () => {
  if (studentSession.value) await logoutStudent()
  if (adminSession.value) await logoutAdmin()
  closeProfileMenu()
  router.push('/')
}

const handleDocumentClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement | null

  if (!target?.closest('[data-profile-menu]')) {
    closeProfileMenu()
  }
}

watch(
  () => route.path,
  () => {
    closeMobileMenu()
    closeProfileMenu()
  },
)

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('click', handleDocumentClick)
  }
})

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('click', handleDocumentClick)
  }
})
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

        </div>

        <div class="flex flex-1 items-center justify-end gap-3 sm:gap-4">
          <nav v-if="!isMainDashboard" class="mx-3 hidden min-w-0 flex-1 items-center justify-center overflow-x-auto whitespace-nowrap [scrollbar-width:none] lg:flex">
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

          <!-- Profile Menu (Show if authenticated) -->
          <div v-if="isAuthenticated" class="relative shrink-0" data-profile-menu>
            <button
              type="button"
              class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-rose-100 bg-white text-sm font-bold text-primary shadow-sm transition hover:border-primary/30"
              :aria-label="adminSession ? 'Open admin profile menu' : 'Open student profile menu'"
              @click.stop="toggleProfileMenu"
            >
              {{ (adminSession?.email || studentSession?.email)?.charAt(0).toUpperCase() }}
            </button>

            <div
              v-if="profileMenuOpen"
              class="absolute right-0 top-[calc(100%+0.6rem)] w-56 rounded-2xl border border-rose-100 bg-white p-2 shadow-[0_18px_32px_rgba(15,23,42,0.14)]"
            >
              <p class="px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 border-b border-rose-50 mb-1">
                {{ adminSession ? 'Admin profile' : 'Student profile' }}
              </p>
              <div class="px-3 py-2">
                <p class="truncate text-xs font-medium text-slate-900">{{ adminSession?.email || studentSession?.email }}</p>
                <p class="text-[10px] text-slate-500 mt-0.5">Logged in</p>
              </div>
              <div class="mt-1 flex flex-col gap-0.5">
                <RouterLink
                  :to="adminSession ? '/admin' : '/exam-training'"
                  class="flex w-full items-center justify-start rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-rose-50 hover:text-primary"
                  @click="closeProfileMenu"
                >
                  Dashboard
                </RouterLink>
                <RouterLink
                  v-if="adminSession"
                  to="/admin/auth/login"
                  class="flex w-full items-center justify-start rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-primary/10 hover:text-primary"
                  @click="closeProfileMenu"
                >
                  Admin login
                </RouterLink>
                <RouterLink
                  v-if="adminSession"
                  to="/admin/auth/reset-password"
                  class="flex w-full items-center justify-start rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-primary/10 hover:text-primary"
                  @click="closeProfileMenu"
                >
                  Reset password
                </RouterLink>
                <button
                  type="button"
                  class="flex w-full items-center justify-start rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                  @click="logoutAndGoHome"
                >
                  Log out
                </button>
              </div>
            </div>
          </div>

          <!-- Login/Signup/Hamburger (Show only if NOT authenticated) -->
          <template v-else>
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
          </template>
        </div>
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
