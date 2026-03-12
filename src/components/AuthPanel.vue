<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter, type RouteLocationRaw } from 'vue-router'

import {
  DEMO_STUDENT_CREDENTIALS,
  hasRegisteredStudent,
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
const email = ref('')
const secret = ref('')
const errorMessage = ref('')

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
const secretLabel = computed(() => (props.mode === 'signup' ? 'Create password' : 'Password'))
const secretPlaceholder = computed(() =>
  props.mode === 'signup' ? 'Create a secure password' : 'Enter your password',
)

const submitPrimaryAction = () => {
  const normalizedEmail = email.value.trim().toLowerCase()
  const normalizedSecret = secret.value.trim()
  errorMessage.value = ''

  if (props.mode === 'signup') {
    if (!normalizedEmail) {
      errorMessage.value = 'Enter an email address to create your student account.'
      return
    }

    if (!normalizedSecret) {
      errorMessage.value = 'Create a password before continuing.'
      return
    }

    registerStudent(normalizedEmail, normalizedSecret)
    router.push(primaryRoute.value)
    return
  }

  if (props.mode === 'login') {
    if (!hasRegisteredStudent()) {
      router.push(buildRouteWithRedirect('/auth/signup'))
      return
    }

    if (!normalizedEmail) {
      errorMessage.value = 'Enter your registered email address to continue.'
      return
    }

    if (!normalizedSecret) {
      errorMessage.value = 'Enter your password to continue.'
      return
    }

    const loggedIn = loginStudent(normalizedEmail, normalizedSecret)

    if (!loggedIn) {
      errorMessage.value = 'The login details do not match any registered or demo student account.'
      return
    }

    router.push(redirectTarget.value ?? props.primaryTo)
    return
  }

  router.push(primaryRoute.value)
}
</script>

<template>
  <section class="mx-auto grid w-full max-w-5xl gap-6 rounded-[2rem] border border-white/75 bg-white/88 p-6 shadow-[0_20px_70px_rgba(117,49,108,0.08)] lg:grid-cols-[minmax(0,0.95fr)_minmax(360px,1.05fr)] lg:p-8">
    <div class="rounded-[1.7rem] bg-secondary p-6 text-white">
      <p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-100">{{ eyebrow }}</p>
      <h2 class="mt-3 text-3xl font-extrabold tracking-tight">{{ title }}</h2>
      <p class="mt-4 text-sm leading-7 text-rose-50/90">{{ description }}</p>

      <div class="mt-8 grid gap-3 text-sm text-rose-50/90">
        <div class="rounded-[1.25rem] bg-white/10 p-4">Scaffolded route structure is in place for future UI refinement.</div>
        <div class="rounded-[1.25rem] bg-white/10 p-4">You can now connect real API flows, OTP validation, and persistence.</div>
        <div v-if="mode === 'login'" class="rounded-[1.25rem] bg-white/12 p-4">
          Demo login: {{ DEMO_STUDENT_CREDENTIALS.email }} / {{ DEMO_STUDENT_CREDENTIALS.password }}
        </div>
      </div>
    </div>

    <form class="grid gap-4 self-center rounded-[1.7rem] bg-rose-50 p-6" @submit.prevent="submitPrimaryAction">
      <label class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">Email address</span>
        <input
          v-model="email"
          type="email"
          placeholder="student@school.edu"
          class="rounded-[1rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10"
        />
      </label>
      <label class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">{{ secretLabel }}</span>
        <input
          v-model="secret"
          type="password"
          :placeholder="secretPlaceholder"
          class="rounded-[1rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10"
        />
      </label>

      <p v-if="errorMessage" class="text-sm font-medium text-primary">
        {{ errorMessage }}
      </p>

      <button
        type="submit"
        class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_26px_rgba(237,69,97,0.22)]"
      >
        {{ primaryLabel }}
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