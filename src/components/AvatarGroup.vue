<script setup lang="ts">
export interface AvatarGroupItem {
  src: string
  fallback: string
  tooltip: string
}

defineProps<{
  avatars: AvatarGroupItem[]
}>()
</script>

<template>
  <div class="flex items-center">
    <div
      v-for="(avatar, index) in avatars"
      :key="`${avatar.tooltip}-${index}`"
      class="group relative"
      :class="index === 0 ? '' : '-ml-3'"
    >
      <div class="relative flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border-[3px] border-[#f1f1f1] bg-slate-200 text-xs font-bold text-slate-700 shadow-sm transition-transform duration-200 group-hover:-translate-y-1">
        <img
          :src="avatar.src"
          :alt="avatar.tooltip"
          class="h-full w-full object-cover"
          loading="lazy"
        />
        <span class="absolute inset-0 hidden items-center justify-center bg-slate-200">{{ avatar.fallback }}</span>
      </div>

      <div class="pointer-events-none absolute left-1/2 top-full z-10 mt-2 hidden -translate-x-1/2 rounded-full bg-[#131313] px-3 py-1 text-xs font-medium text-white shadow-lg group-hover:block">
        {{ avatar.tooltip }}
      </div>
    </div>
  </div>
</template>