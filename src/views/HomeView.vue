<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import AllCategorySection from '../components/AllCategorySection.vue'
import AllProductSection from '../components/AllProductSection.vue'
import HeroSection from '../components/HeroSection.vue'
import StatsSection from '../components/StatsSection.vue'
import { useCatalogStore } from '../stores/catalog'
import { setPendingStudentPurchase } from '../utils/pendingStudentPurchase'
import { isStudentAuthenticated } from '../utils/studentAuth'

const catalog = useCatalogStore()
const router = useRouter()
const actionError = ref('')
const purchasePendingId = ref('')

const highlights = computed(() => {
  const materials = catalog.products.filter((product) => product.type === 'material')
  const exams = catalog.products.filter((product) => product.type === 'cbt')
  const paidListings = catalog.products.filter((product) => product.accessType === 'paid')

  return [
    {
      label: 'Materials live',
      value: String(materials.length),
      note: materials.length
        ? 'Resources currently published on the platform.'
        : 'No study materials have been published yet.',
    },
    {
      label: 'Exam trainings live',
      value: String(exams.length),
      note: exams.length
        ? 'Live CBT exams available to learners.'
        : 'No exam training tests are live yet.',
    },
    {
      label: 'Paid listings',
      value: String(paidListings.length),
      note: paidListings.length
        ? 'Items with active pricing in the catalog.'
        : 'No prices or premium listings are published yet.',
    },
  ]
})

type PurchasePayload = {
  productId: string
  productType: 'material' | 'cbt'
}

const handleHomePurchase = async ({ productId, productType }: PurchasePayload) => {
  actionError.value = ''

  if (!isStudentAuthenticated()) {
    setPendingStudentPurchase(productId, productType)
    await router.push('/auth/signup')
    return
  }

  purchasePendingId.value = productId

  try {
    await catalog.purchaseProduct(productId, productType)
  } catch (error) {
    actionError.value = error instanceof Error ? error.message : 'Unable to start payment right now.'
  } finally {
    purchasePendingId.value = ''
  }
}
</script>

<template>
  <div class="grid gap-8">
    <section v-if="actionError || catalog.loadError" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
      {{ actionError || catalog.loadError }}
    </section>

    <HeroSection />

    <section id="outcomes" class="scroll-mt-28">
      <StatsSection :stats="highlights" />
    </section>

    <section id="discover" class="scroll-mt-28">
      <AllCategorySection :products="catalog.products" :pending-product-id="purchasePendingId" @pay="handleHomePurchase" />
    </section>

    <section id="featured" class="scroll-mt-28">
      <AllProductSection :products="catalog.products" :pending-product-id="purchasePendingId" @pay="handleHomePurchase" />
    </section>
  </div>
</template>
