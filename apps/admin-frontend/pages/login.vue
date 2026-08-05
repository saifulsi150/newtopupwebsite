<script setup lang="ts">
definePageMeta({ layout: 'default' });
const auth = useAdminAuth();
if (auth.isLoggedIn.value) navigateTo('/');

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

async function handleLogin() {
  error.value = '';
  if (!email.value || !password.value) {
    error.value = 'Please enter email and password.';
    return;
  }
  loading.value = true;
  try {
    const res = await $fetch<any>('/api/auth/login', {
      method: 'POST',
      body: { email: email.value, password: password.value }
    });
    if (res?.success) {
      auth.setAdmin(res.admin);
      navigateTo('/');
    }
  } catch (e: any) {
    error.value = e?.data?.statusMessage || 'Login failed. Check credentials.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#0f172a] via-[#172554] to-[#1e293b] p-4">
    <div class="w-full max-w-sm">
      <!-- Logo -->
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-3xl font-black text-white shadow-2xl shadow-indigo-500/30">A</div>
        <h1 class="text-2xl font-black text-white">Admin Panel</h1>
        <p class="mt-1 text-sm text-slate-300">GhostBazar Control Center</p>
      </div>

      <!-- Form -->
      <div class="rounded-3xl border border-white/20 bg-white/10 p-8 shadow-2xl backdrop-blur-xl">
        <div v-if="error" class="mb-5 rounded-lg bg-red-500/20 border border-red-500/30 px-4 py-3 text-sm text-red-300">
          {{ error }}
        </div>

        <div class="space-y-5">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-300">Email</label>
            <input
              v-model="email"
              type="email"
              placeholder="admin@example.com"
              class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder-slate-400 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-400/20"
              @keyup.enter="handleLogin"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
            <input
              v-model="password"
              type="password"
              placeholder="••••••••"
              class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder-slate-400 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-400/20"
              @keyup.enter="handleLogin"
            />
          </div>

          <button
            :disabled="loading"
            @click="handleLogin"
            class="w-full rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/30 transition hover:from-indigo-600 hover:to-violet-600 disabled:opacity-60"
          >
            {{ loading ? 'Logging in...' : 'Login to Admin' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
