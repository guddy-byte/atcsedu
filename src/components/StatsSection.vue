<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

interface StatItem {
  label: string
  value: string
  note: string
}

const props = withDefaults(defineProps<{
  stats: StatItem[]
  title?: string
}>(), {
  title: 'Measured outcomes that show why students keep choosing this platform.',
})

interface ParsedStatValue {
  target: number
  suffix: string
  minimumIntegerDigits: number
  useGrouping: boolean
}

const animatedValues = ref<number[]>([])
const frameIds: number[] = []

const parsedStats = computed<ParsedStatValue[]>(() =>
  props.stats.map((stat) => {
    const numericPart = stat.value.replace(/[^\d.]/g, '')
    const digitsOnly = numericPart.replace(/\D/g, '')

    return {
      target: Number(numericPart) || 0,
      suffix: stat.value.replace(/[\d,.\s]/g, ''),
      minimumIntegerDigits: digitsOnly.length > 1 && digitsOnly.startsWith('0') ? digitsOnly.length : 1,
      useGrouping: stat.value.includes(','),
    }
  }),
)

const cancelAnimations = () => {
  frameIds.forEach((id) => cancelAnimationFrame(id))
  frameIds.length = 0
}

const formatValue = (value: number, parsed: ParsedStatValue) => {
  const roundedValue = Math.round(value)

  return `${new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
    minimumIntegerDigits: parsed.minimumIntegerDigits,
    useGrouping: parsed.useGrouping,
  }).format(roundedValue)}${parsed.suffix}`
}

const startAnimation = () => {
  cancelAnimations()
  animatedValues.value = props.stats.map(() => 0)

  parsedStats.value.forEach((parsed, index) => {
    const duration = 1200 + index * 140
    const startTime = performance.now()

    const tick = (currentTime: number) => {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)
      const easedProgress = 1 - (1 - progress) ** 3

      animatedValues.value[index] = parsed.target * easedProgress

      if (progress < 1) {
        frameIds[index] = requestAnimationFrame(tick)
      }
    }

    frameIds[index] = requestAnimationFrame(tick)
  })
}

onMounted(() => {
  startAnimation()
})

watch(
  () => props.stats,
  () => {
    startAnimation()
  },
  { deep: true },
)

onBeforeUnmount(() => {
  cancelAnimations()
})
</script>

<template>
  <section class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-[linear-gradient(180deg,#f7f7f7_0%,#ececec_100%)] px-6 py-8 shadow-[0_24px_80px_rgba(15,23,42,0.12),0_3px_0_rgba(255,255,255,0.75)_inset,0_-12px_24px_rgba(15,23,42,0.04)_inset] sm:px-8 sm:py-10 lg:px-10">
    <div class="pointer-events-none absolute inset-x-10 top-0 h-16 rounded-full bg-white/65 blur-2xl" />
    <div class="pointer-events-none absolute -bottom-10 left-10 h-24 w-24 rounded-full bg-white/40 blur-3xl" />

    <div class="grid gap-8">
      <div class="grid gap-6 md:grid-cols-3 md:gap-0">
        <article
          v-for="(stat, index) in props.stats"
          :key="stat.label"
          class="relative px-4 md:px-5"
          :class="index === 0 ? 'md:pl-0' : 'md:border-l md:border-[#cfcfcf] md:pl-5 lg:pl-6'"
        >
          <div class="pointer-events-none absolute inset-x-4 top-0 h-px bg-white/80 md:hidden" />
          <strong class="block text-[40px] font-extrabold leading-none tracking-[-0.05em] text-[#111111] sm:text-[40px]">
            {{ formatValue(animatedValues[index] ?? 0, parsedStats[index]) }}
          </strong>
          <p class="mt-3 text-[13px] font-semibold leading-5 text-[#1b1b1b]">
            {{ stat.label }}
          </p>
          <p class="mt-1 max-w-[18rem] text-[12px] leading-5 text-[#666b73]">
            {{ stat.note }}
          </p>
        </article>
      </div>
    </div>
  </section>
</template>