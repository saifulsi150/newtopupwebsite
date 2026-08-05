<script setup lang="ts">
definePageMeta({ middleware: 'auth' });
const auth = useAuth();

async function syncAuthUser() {
  if (!auth.isLoggedIn.value) return;
  try {
    const res = await $fetch<{ user: any | null }>('/api/user/me', {
      method: 'GET',
      timeout: 8000,
      retry: 0
    });
    if (res?.user) {
      auth.setUser(res.user);
    }
  } catch {
    // Do not block profile rendering on transient sync errors.
  }
}

async function refreshAll() {
  await refresh();
  await syncAuthUser();
}

const fallbackProfile = computed(() => {
  const u = auth.user.value || {};
  return {
    name: String(u.name || ''),
    email: String(u.email || ''),
    phone: String(u.phone || ''),
    avatar: String(u.avatar || u.picture || ''),
    wallet_balance: Number(u.wallet_balance || 0),
    orders_count: 0,
    total_spend: 0,
    support_pin: 25
  };
});

const { data, pending, refresh } = await useFetch('/api/user/profile', {
  server: false,
  watch: [() => auth.user.value?.id],
  getCachedData: () => undefined,
  default: () => ({ profile: fallbackProfile.value })
});

const profile = computed(() => ({
  ...fallbackProfile.value,
  ...(data.value?.profile || {})
}));

const profileReady = computed(() => {
  return Boolean(profile.value.name || profile.value.email || profile.value.avatar);
});

const displayName = computed(() => profile.value.name || 'User');
const firstLetter = computed(() => displayName.value.trim().charAt(0).toUpperCase() || 'U');
const avatarSrc = computed(() => {
  const src = profile.value.avatar || fallbackProfile.value.avatar || '';
  return src && src.startsWith('http') ? src : '';
});
</script>

<template>
  <section class="bg-[#f1f6fc] px-4 py-6 md:py-8">
    <!-- Skeleton Loading -->
    <div v-if="pending && !profileReady" class="mx-auto w-full max-w-4xl space-y-6">
      <div class="animate-pulse flex flex-col items-center space-y-3">
        <div class="h-28 w-28 rounded-full bg-slate-300" />
        <div class="h-6 w-48 rounded bg-slate-300" />
        <div class="h-6 w-60 rounded bg-slate-300" />
      </div>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
        <div v-for="i in 4" :key="i" class="h-24 rounded-lg bg-white/80 shadow-sm" />
      </div>
      <div class="h-36 rounded-lg bg-white/80 shadow-sm" />
    </div>

    <!-- Main Profile UI -->
    <div v-else class="mx-auto w-full max-w-4xl">
      <!-- Profile Header -->
      <div class="flex flex-col items-center text-center">
        <!-- Avatar Ring Frame -->
        <div class="relative flex h-28 w-28 items-center justify-center rounded-full border-[5px] border-[#618fae] bg-gradient-to-tr from-[#9c388a] to-[#be2a7a] p-1 shadow-md">
          <img v-if="avatarSrc" :src="avatarSrc" alt="Profile" class="h-full w-full rounded-full object-cover" referrerpolicy="no-referrer" />
          <span v-else class="text-5xl font-bold text-white">{{ firstLetter }}</span>
        </div>

        <!-- Name & Balance -->
        <h1 class="mt-3 text-lg font-semibold text-[#1a5b82] md:text-xl">Hi, {{ displayName }}</h1>
        <div class="mt-1 flex items-center justify-center gap-2 text-base font-medium text-slate-800 md:text-lg">
          <span>Available Balance : ৳{{ profile.wallet_balance || 0 }}</span>
          <button 
            type="button" 
            class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-xs text-slate-600 transition hover:bg-slate-50 active:scale-95 shadow-sm"
            title="Refresh"
            @click="refreshAll"
          >
            <svg class="h-3.5 w-3.5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
          </button>
        </div>
      </div>

      <!-- 4 Summary Cards (Responsive: 2 Columns in Mobile, 4 Columns in Desktop) -->
      <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
        <!-- Balance -->
        <div class="flex flex-col items-center justify-center rounded-lg border border-[#7ba8c2]/50 bg-white py-4 px-2 text-center shadow-sm">
          <div class="text-lg font-semibold text-[#1a5b82]">৳{{ profile.wallet_balance || 0 }}</div>
          <div class="mt-1 text-sm font-bold text-slate-800">Balance</div>
        </div>

        <!-- Total Order -->
        <div class="flex flex-col items-center justify-center rounded-lg border border-[#7ba8c2]/50 bg-white py-4 px-2 text-center shadow-sm">
          <div class="text-lg font-semibold text-[#1a5b82]">{{ profile.orders_count || 0 }}</div>
          <div class="mt-1 text-sm font-bold text-slate-800">Total Order</div>
        </div>

        <!-- Total Spent -->
        <div class="flex flex-col items-center justify-center rounded-lg border border-[#7ba8c2]/50 bg-white py-4 px-2 text-center shadow-sm">
          <div class="text-lg font-semibold text-[#1a5b82]">৳{{ profile.total_spend || 0 }}</div>
          <div class="mt-1 text-sm font-bold text-slate-800">Total Spent</div>
        </div>

        <!-- Support PIN -->
        <div class="flex flex-col items-center justify-center rounded-lg border border-[#7ba8c2]/50 bg-white py-4 px-2 text-center shadow-sm">
          <div class="text-lg font-semibold text-[#1a5b82]">{{ profile.support_pin || 25 }}</div>
          <div class="mt-1 text-sm font-bold text-slate-800">Support PIN</div>
        </div>
      </div>

      <!-- User Information Box -->
      <div class="mt-5 overflow-hidden rounded-lg border border-[#7ba8c2]/40 bg-white shadow-sm">
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3 text-slate-900">
          <svg class="h-5 w-5 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-base font-bold text-slate-900">User Information</span>
        </div>
        <div class="space-y-2.5 px-4 py-4 text-sm font-bold text-slate-900">
          <p class="truncate">email : <span class="font-bold text-slate-900">{{ profile.email || '-' }}</span></p>
          <p>Phone : <span class="font-bold text-slate-900">{{ profile.phone || '' }}</span></p>
        </div>
      </div>
    </div>
  </section>
</template>