<script setup lang="ts">
import { computed } from 'vue'

import AllCategorySection from '../components/AllCategorySection.vue'
import AllProductSection from '../components/AllProductSection.vue'
import HeroSection from '../components/HeroSection.vue'
import StatsSection from '../components/StatsSection.vue'
import { useCatalogStore } from '../stores/catalog'

const catalog = useCatalogStore()

const highlights = computed(() => [
  {
    label: 'Students reached',
    value: '3,450+',
    note: 'Learners accessing exam preparation and digital study support.',
  },
  {
    label: 'Materials available',
    value: '500+',
    note: 'Free and paid resources arranged for quick access.',
  },
  {
    label: 'Exam support rating',
    value: '96%',
    note: 'Confidence score from our internal readiness benchmark.',
  },
])
</script>

<template>
  <div class="grid gap-8">
    <HeroSection />
    <StatsSection :stats="highlights" />
    <AllCategorySection :products="catalog.products" @pay="catalog.addToCart" />
    <AllProductSection :products="catalog.products" @pay="catalog.addToCart" />
  </div>
</template>