<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter, type RouteLocationRaw } from 'vue-router'

import { ApiError, apiRequest } from '../lib/api'
import { useCatalogStore } from '../stores/catalog'

interface PaymentVerifyResponse {
  status: string
  message: string
  data: {
    reference: string
    payment_status: string
    purchase: {
      id: number
      item_type: 'exam' | 'material'
      item_id: number
      status: string
      paid_at?: string | null
    }
  }
}

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()

const status = ref<'verifying' | 'success' | 'error'>('verifying')
const message = ref('Confirming your payment. Please wait...')
const verifiedPurchase = ref<PaymentVerifyResponse['data']['purchase'] | null>(null)
const autoRedirectId = ref<number | null>(null)

const continuePath = computed<RouteLocationRaw>(() => {
  if (verifiedPurchase.value?.item_type === 'material') {
    return {
      path: '/exam-training',
      query: {
        library: 'paid-materials',
        purchase: 'success',
        purchased_material: String(verifiedPurchase.value.item_id),
      },
    }
  }

  return '/exam-training'
})

const continueLabel = computed(() => (
  verifiedPurchase.value?.item_type === 'exam'
    ? 'Continue to exam training'
    : 'Open material in dashboard'
))

const verifyPayment = async () => {
  const referenceCandidate = route.query.reference ?? route.query.trxref
  const reference = typeof referenceCandidate === 'string' ? referenceCandidate.trim() : ''

  if (!reference) {
    status.value = 'error'
    message.value = 'The payment reference is missing, so this payment could not be verified.'
    return
  }

  try {
    const response = await apiRequest<PaymentVerifyResponse>(`/payments/${encodeURIComponent(reference)}/verify`)

    if (response.data.payment_status !== 'paid') {
      status.value = 'error'
      message.value = 'The payment was received, but Paystack has not marked it as paid yet.'
      return
    }

    verifiedPurchase.value = response.data.purchase
    catalog.markServerPurchase(response.data.purchase.item_type, response.data.purchase.item_id)

    if (response.data.purchase.item_type === 'material') {
      await catalog.initialize()
      await catalog.fetchMaterialDetails(String(response.data.purchase.item_id))
    }

    status.value = 'success'
    message.value = 'Payment verified successfully. Your access is now unlocked.'
    scheduleAutoRedirect()
  } catch (error) {
    status.value = 'error'

    if (error instanceof ApiError && error.status === 401) {
      message.value = 'Please sign in again so we can verify and unlock this payment.'
      return
    }

    message.value = error instanceof Error
      ? error.message
      : 'We could not verify this payment right now.'
  }
}

const goToContinuePath = () => {
  void router.push(continuePath.value)
}

const goToLogin = () => {
  void router.push('/auth/login')
}

const goHome = () => {
  void router.push('/')
}

const scheduleAutoRedirect = () => {
  if (typeof window === 'undefined') {
    return
  }

  if (autoRedirectId.value) {
    window.clearTimeout(autoRedirectId.value)
  }

  autoRedirectId.value = window.setTimeout(() => {
    void router.push(continuePath.value)
  }, 1200)
}

onMounted(() => {
  void verifyPayment()
})

onUnmounted(() => {
  if (typeof window !== 'undefined' && autoRedirectId.value) {
    window.clearTimeout(autoRedirectId.value)
  }
})
</script>

<template>
  <section class="mx-auto max-w-3xl px-6 py-16">
    <div class="overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
      <div class="bg-[linear-gradient(120deg,rgba(237,69,97,0.1),rgba(117,49,108,0.12))] px-8 py-7">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">Payment status</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Paystack verification</h1>
        <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600">{{ message }}</p>
      </div>

      <div class="px-8 py-8">
        <div
          v-if="status === 'verifying'"
          class="rounded-2xl border border-sky-100 bg-sky-50 px-5 py-4 text-sm font-medium text-sky-700"
        >
          Verifying your payment with the API...
        </div>

        <div
          v-else-if="status === 'success'"
          class="rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
        >
          Payment confirmed and access granted. Redirecting you to the correct dashboard view...
        </div>

        <div
          v-else
          class="rounded-2xl border border-rose-100 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700"
        >
          Verification could not be completed automatically.
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            v-if="status === 'success'"
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-secondary"
            @click="goToContinuePath"
          >
            {{ continueLabel }}
          </button>

          <button
            v-else
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-secondary"
            @click="goToLogin"
          >
            Sign in to retry
          </button>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-rose-50 px-5 py-2.5 text-sm font-semibold text-primary transition hover:bg-rose-100"
            @click="goHome"
          >
            Back home
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
