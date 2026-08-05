<script setup lang="ts">
const data = ref<HealthResponse>({ status: "ok" });
const error = ref(false);

type HealthResponse = {
  status?: string;
  time?: string;
};

const refresh = async () => {
  try {
    const payload = await $fetch<HealthResponse>(`/api/healthz`);
    data.value = payload;
    error.value = false;
  } catch {
    error.value = true;
  }
};

onMounted(() => {
  void refresh();
});
</script>

<template>
  <main class="min-h-screen bg-slate-100 p-8 text-slate-900">
    <section class="mx-auto max-w-3xl rounded-2xl bg-white p-8 shadow-sm">
      <h1 class="text-2xl font-bold">Stack Status</h1>
      <p class="mt-3 leading-7 text-slate-700">
        This route confirms Nuxt, MySQL and Redis are connected.
      </p>
      <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
        <template v-if="error">
          Health: unreachable
        </template>
        <template v-else>
          Health: {{ data.status || "unknown" }}
          <span v-if="data.time"> at {{ data.time }}</span>
        </template>
      </div>
      <div class="mt-6 flex flex-wrap gap-3">
        <button
          type="button"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
          @click="refresh"
        >
          Refresh Status
        </button>
        <NuxtLink to="/" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
          Back Home
        </NuxtLink>
      </div>
    </section>
  </main>
</template>
