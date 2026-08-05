<script setup lang="ts">
const links = [
  { to: "/", label: "Home" },
  { to: "/status", label: "Status" }
];
</script>

<template>
  <main class="min-h-screen bg-slate-100 p-8 text-slate-900">
    <section class="mx-auto max-w-3xl rounded-2xl bg-white p-8 shadow-sm">
      <h1 class="text-2xl font-bold">Speed Optimized Route</h1>
      <p class="mt-3 leading-7 text-slate-700">
        This page is prerendered and prefetched so navigation feels instant after first load.
      </p>
      <div class="mt-6 flex flex-wrap gap-3">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
        >
          {{ link.label }}
        </NuxtLink>
      </div>
    </section>
  </main>
</template>
