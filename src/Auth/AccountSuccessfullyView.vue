<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()

const loginTarget = computed(() => {
  const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : undefined

  if (!redirect) {
    return '/auth/login'
  }

  return {
    path: '/auth/login',
    query: {
      redirect,
    },
  }
})
</script>

<template>
  <section class="mx-auto grid w-full max-w-3xl gap-6 rounded-[2rem] border border-white/75 bg-white/88 p-8 text-center shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
    <img
      src="/success.gif"
      alt="Successful account creation"
      class="mx-auto h-20 w-20 rounded-full object-contain"
    />
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">Account created</p>
      <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950">
        Student account created successfully.
      </h2>
      <p class="mt-4 text-base leading-8 text-slate-600">
        This success screen is now routed and ready for the next UI pass. You can later connect it to the real signup workflow and post-registration messaging.
      </p>
    </div>

    <div class="flex flex-wrap justify-center gap-3">
      <RouterLink
        :to="loginTarget"
        class="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_26px_rgba(237,69,97,0.22)]"
      >
        Go to login
      </RouterLink>
      <RouterLink
        to="/exam-training"
        class="inline-flex items-center justify-center rounded-full border border-rose-100 bg-white px-6 py-3 text-sm font-semibold text-secondary"
      >
        View exam training
      </RouterLink>
    </div>
  </section>
</template>