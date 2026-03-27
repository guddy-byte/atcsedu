<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter, type RouteLocationRaw } from 'vue-router'

import { useCatalogStore } from '../stores/catalog'
import { consumePendingStudentPurchase, getPendingStudentPurchase, setPendingStudentPurchase } from '../utils/pendingStudentPurchase'
import {
  loginStudent,
  registerStudent,
} from '../utils/studentAuth'

const props = withDefaults(defineProps<{
  eyebrow: string
  title: string
  description: string
  primaryLabel: string
  primaryTo: string
  secondaryLabel: string
  secondaryTo: string
  mode?: 'default' | 'signup' | 'login'
}>(), {
  mode: 'default',
})

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()
const fullName = ref('')
const email = ref('')
const secret = ref('')
const errorMessage = ref('')
const showPassword = ref(false)
const isSubmitting = ref(false)

const redirectTarget = computed(() =>
  typeof route.query.redirect === 'string' ? route.query.redirect : undefined,
)

const buildRouteWithRedirect = (path: string): RouteLocationRaw => {
  if (!redirectTarget.value || !path.startsWith('/auth')) {
    return path
  }

  return {
    path,
    query: {
      redirect: redirectTarget.value,
    },
  }
}

const primaryRoute = computed(() => buildRouteWithRedirect(props.primaryTo))
const secondaryRoute = computed(() => buildRouteWithRedirect(props.secondaryTo))
const hasPendingPayment = computed(() => Boolean(getPendingStudentPurchase()))
const secretLabel = computed(() => (props.mode === 'signup' ? 'Create password' : 'Password'))
const secretPlaceholder = computed(() =>
  props.mode === 'signup' ? 'Create a secure password' : 'Enter your password',
)
const authPanelSummary = computed(() => {
  if (hasPendingPayment.value) {
    return props.mode === 'signup'
      ? 'Create your student account to continue directly to secure payment for this material.'
      : 'Sign in to continue directly to secure payment for the item you selected.'
  }

  return 'Use your details below to continue.'
})
const authChecklist = computed(() =>
  hasPendingPayment.value
    ? [
        'Your selected paid item will be remembered while you complete authentication.',
        'Once you finish, checkout will continue automatically.',
        'After payment, your protected material will open from your dashboard.',
      ]
    : props.mode === 'signup'
    ? [
        'Create your account in less than 1 minute.',
        'Access free and paid exam preparation instantly.',
        'Track progress from your personal student dashboard.',
      ]
    : [
        'Resume from where you stopped on exam practice.',
        'Access your purchased exam packs and free practice tests.',
        'View updated dashboard stats after each attempt.',
      ],
)

const continuePendingPurchase = async () => {
  const pendingPurchase = consumePendingStudentPurchase()

  if (!pendingPurchase) {
    return false
  }

  try {
    await catalog.initialize()
    await catalog.purchaseProduct(pendingPurchase.productId, pendingPurchase.productType)
    return true
  } catch (error) {
    setPendingStudentPurchase(pendingPurchase.productId, pendingPurchase.productType)
    throw error
  }
}

const submitPrimaryAction = async () => {
  const normalizedEmail = email.value.trim().toLowerCase()
  const normalizedSecret = secret.value.trim()
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    if (props.mode === 'signup') {
      if (!fullName.value.trim()) {
        errorMessage.value = 'Enter your full name to create your student account.'
        return
      }

      if (!normalizedEmail) {
        errorMessage.value = 'Enter an email address to create your student account.'
        return
      }

      if (!normalizedSecret || normalizedSecret.length < 8) {
        errorMessage.value = 'Create a password with at least 8 characters.'
        return
      }

      await registerStudent(fullName.value.trim(), normalizedEmail, normalizedSecret)

      if (await continuePendingPurchase()) {
        return
      }

      router.push(primaryRoute.value)
      return
    }

    if (props.mode === 'login') {
      if (!normalizedEmail) {
        errorMessage.value = 'Enter your registered email address to continue.'
        return
      }

      if (!normalizedSecret) {
        errorMessage.value = 'Enter your password to continue.'
        return
      }

      await loginStudent(normalizedEmail, normalizedSecret)

      if (await continuePendingPurchase()) {
        return
      }

      router.push(redirectTarget.value ?? props.primaryTo)
      return
    }

    router.push(primaryRoute.value)
  } catch (error) {
    errorMessage.value = error instanceof Error
      ? error.message
      : 'We could not complete your request right now.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="mx-auto grid w-full max-w-6xl gap-6 rounded-[2rem] border border-white/80 bg-white p-4 shadow-[0_25px_70px_rgba(117,49,108,0.1)] md:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(380px,1fr)] lg:p-8">
    <div class="rounded-[1.6rem] bg-[linear-gradient(160deg,#ec4899_0%,#e11d48_55%,#7c3aed_100%)] p-6 text-white sm:p-7">
      <p class="text-xs font-semibold uppercase tracking-[0.25em] text-rose-100">{{ eyebrow }}</p>
      <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-[2rem]">{{ title }}</h2>
      <p class="mt-4 max-w-md text-sm leading-7 text-rose-50/95">{{ description }}</p>

      <div class="mt-6 grid gap-2.5">
        <div
          v-for="item in authChecklist"
          :key="item"
          class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm"
        >
          {{ item }}
        </div>
      </div>
    </div>

    <form class="grid content-start gap-4 rounded-[1.6rem] border border-rose-100 bg-rose-50/55 p-5 sm:p-6" @submit.prevent="submitPrimaryAction">
      <div>
        <h3 class="text-xl font-extrabold tracking-tight text-slate-900">Welcome</h3>
        <p class="mt-1 text-sm text-slate-600">{{ authPanelSummary }}</p>
      </div>

      <label v-if="mode === 'signup'" class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">Full name</span>
        <input
          v-model="fullName"
          type="text"
          placeholder="Enter your full name"
          class="rounded-[0.95rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/45 focus:ring-2 focus:ring-primary/10"
        />
      </label>

      <label class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">Email address</span>
        <input
          v-model="email"
          type="email"
          placeholder="student@school.edu"
          class="rounded-[0.95rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/45 focus:ring-2 focus:ring-primary/10"
        />
      </label>

      <label class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">{{ secretLabel }}</span>
        <div class="flex items-center rounded-[0.95rem] border border-rose-100 bg-white pr-2 focus-within:border-primary/45 focus-within:ring-2 focus-within:ring-primary/10">
          <input
            v-model="secret"
            :type="showPassword ? 'text' : 'password'"
            :placeholder="secretPlaceholder"
            class="w-full rounded-[0.95rem] bg-transparent px-4 py-3 text-sm text-slate-700 outline-none placeholder:text-slate-400"
          />
          <button
            type="button"
            class="rounded-full px-2 py-1 text-xs font-semibold text-slate-500 hover:text-primary"
            @click="showPassword = !showPassword"
          >
            {{ showPassword ? 'Hide' : 'Show' }}
          </button>
        </div>
      </label>

      <p v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-primary">
        {{ errorMessage }}
      </p>

      <button
        type="submit"
        :disabled="isSubmitting"
        class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_24px_rgba(237,69,97,0.24)] disabled:opacity-70"
      >
        {{ isSubmitting ? 'Please wait...' : primaryLabel }}
      </button>

      <RouterLink
        :to="secondaryRoute"
        class="inline-flex items-center justify-center rounded-full border border-rose-100 bg-white px-5 py-3 text-sm font-semibold text-secondary"
      >
        {{ secondaryLabel }}
      </RouterLink>
    </form>
  </section>
</template>
