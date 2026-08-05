<script setup lang="ts">
const loading = useState<boolean>('route-loading-active', () => false);
const progress = useState<number>('route-loading-progress', () => 0);

const visible = computed(() => loading.value || progress.value > 0);
</script>

<template>
  <div
    v-show="visible"
    class="pointer-events-none fixed left-0 right-0 top-0 z-[120] h-[2px]"
    aria-hidden="true"
  >
    <div
      class="h-full bg-indigo-500 transition-[width,opacity] duration-200 ease-out"
      :style="{
        width: `${progress}%`,
        opacity: visible ? 1 : 0,
        boxShadow: '0 0 12px rgba(79, 70, 229, 0.55)'
      }"
    />
  </div>
</template>
