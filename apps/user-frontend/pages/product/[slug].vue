<script setup lang="ts">
const route = useRoute();
const slug = computed(() => String(route.params.slug || ''));
const playerId = ref('');
const paymentMethod = ref('wallet');
const selectedPackageId = ref<number | null>(null);
const verifying = ref(false);
const verifyMessage = ref('');
const orderMessage = ref('');
const orderLoading = ref(false);
const auth = useAuth();

const { data: productData, pending: productPending } = await useFetch(() => `/api/products/${slug.value}`, { server: false });
const { data: packageData } = await useFetch(() => `/api/products/${slug.value}/packages`, { server: false });
const product = computed(() => productData.value?.product || null);
const packages = computed(() => packageData.value?.packages || []);

watch(packages, (list) => {
  if (list.length && !selectedPackageId.value) {
    selectedPackageId.value = list[0].id;
  }
}, { immediate: true });

const selectedPackage = computed(() => packages.value.find((item: any) => item.id === selectedPackageId.value) || null);
const requiresPlayerId = computed(() => Boolean(product.value?.requires_player_id));

async function verifyPlayer() {
  verifying.value = true;
  verifyMessage.value = '';
  try {
    const res = await $fetch(`/api/products/${slug.value}/verify-player`, { method: 'POST', body: { player_id: playerId.value } });
    verifyMessage.value = res.valid ? `Verified as ${res.nickname}` : res.message || 'Could not verify';
  } finally {
    verifying.value = false;
  }
}

async function placeOrder() {
  if (requiresPlayerId.value && !playerId.value) {
    orderMessage.value = 'Please enter a valid player ID';
    return;
  }
  orderLoading.value = true;
  orderMessage.value = '';
  try {
    const res = await $fetch<any>('/api/orders', {
      method: 'POST',
      body: {
        package_id: selectedPackageId.value,
        package_title: String(selectedPackage.value?.name || ''),
        amount: Number(selectedPackage.value?.price || 0),
        player_id: playerId.value,
        payment_method: paymentMethod.value
      }
    });
    if (res?.order?.redirect_url) {
      if (paymentMethod.value === 'instant') {
        // UddoktaPay — external redirect
        window.location.href = res.order.redirect_url;
      } else {
        await navigateTo(res.order.redirect_url);
      }
    } else {
      orderMessage.value = res.success ? 'Order placed successfully.' : 'Could not place order';
    }
  } catch (e: any) {
    orderMessage.value = e?.data?.message || 'Order failed';
  } finally {
    orderLoading.value = false;
  }
}
</script>

<template>
  <section class="page-shell">
    <div v-if="productPending" class="card-panel p-10 text-center text-slate-600">Loading product...</div>
    <div v-else-if="product" class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
      <div class="space-y-6">
        <div class="card-panel overflow-hidden">
          <img :src="product.image_url" :alt="product.name" class="h-56 w-full object-cover" />
          <div class="p-6">
            <div class="text-sm uppercase tracking-[0.3em] text-[#18823f]">Product</div>
            <h1 class="mt-3 text-3xl font-black text-slate-900">{{ product.name }}</h1>
            <p class="mt-3 text-slate-600">{{ product.description }}</p>
          </div>
        </div>

        <div class="card-panel p-6">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#18823f] text-sm font-bold text-white">1</div>
            <h2 class="text-xl font-black text-slate-900">Select Recharge</h2>
          </div>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <button v-for="item in packages" :key="item.id" type="button" class="rounded-2xl border px-4 py-4 text-left transition" :class="selectedPackageId === item.id ? 'border-emerald-400 bg-emerald-500/10' : 'border-slate-200 bg-white'" @click="selectedPackageId = item.id">
              <div class="text-sm text-slate-600">{{ item.name }}</div>
              <div class="mt-2 text-xl font-black text-[#18823f]">৳{{ item.price }}</div>
            </button>
          </div>
        </div>

        <div class="card-panel p-6">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#18823f] text-sm font-bold text-white">2</div>
            <h2 class="text-xl font-black text-slate-900">Account Info</h2>
          </div>
          <div v-if="requiresPlayerId" class="mt-4 space-y-3">
            <input v-model="playerId" class="input-shell" placeholder="Enter your player ID" />
            <button type="button" class="btn-primary px-4 py-2" @click="verifyPlayer">{{ verifying ? 'Checking...' : 'Check Player ID' }}</button>
            <p v-if="verifyMessage" class="text-sm text-[#18823f]">{{ verifyMessage }}</p>
          </div>
          <p v-else class="mt-4 text-sm text-slate-600">This product does not need a player ID.</p>
        </div>
      </div>

      <div class="card-panel p-6 shadow-[0_20px_80px_rgba(0,0,0,0.35)]">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#18823f] text-sm font-bold text-white">3</div>
          <h2 class="text-xl font-black text-slate-900">Select one option</h2>
        </div>
        <div class="mt-4 grid gap-3">
          <button type="button" class="rounded-2xl border p-4 text-left transition flex items-center gap-3" :class="paymentMethod === 'wallet' ? 'border-emerald-400 bg-emerald-500/10' : 'border-slate-200 bg-white'" @click="paymentMethod = 'wallet'">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </span>
            <div>
              <div class="font-bold text-sm text-slate-800">Wallet Pay</div>
              <div class="text-xs text-slate-500 mt-0.5">Pay from your account balance</div>
            </div>
          </button>

          <button type="button" class="rounded-2xl border p-4 text-left transition" :class="paymentMethod === 'instant' ? 'border-emerald-400 bg-emerald-500/10' : 'border-slate-200 bg-white'" @click="paymentMethod = 'instant'">
            <div class="flex items-center gap-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-full bg-pink-100 text-pink-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </span>
              <div>
                <div class="font-bold text-sm text-slate-800">Instant Pay</div>
                <div class="text-xs text-slate-500 mt-0.5">bKash / Nagad / Rocket</div>
              </div>
            </div>
            <div class="mt-3 flex items-center gap-2 flex-wrap">
              <span class="inline-flex items-center gap-1 rounded-full bg-pink-50 border border-pink-200 px-2.5 py-1 text-[11px] font-bold text-pink-700">
                <span class="h-2 w-2 rounded-full bg-pink-500 inline-block"></span>bKash
              </span>
              <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 border border-orange-200 px-2.5 py-1 text-[11px] font-bold text-orange-700">
                <span class="h-2 w-2 rounded-full bg-orange-500 inline-block"></span>Nagad
              </span>
              <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 border border-purple-200 px-2.5 py-1 text-[11px] font-bold text-purple-700">
                <span class="h-2 w-2 rounded-full bg-purple-500 inline-block"></span>Rocket
              </span>
              <span class="text-[10px] text-slate-400 ml-auto">Powered by UddoktaPay</span>
            </div>
          </button>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
          <div class="flex items-center justify-between text-sm text-slate-600">
            <span>Selected Package</span>
            <span>{{ selectedPackage?.name || 'Choose one' }}</span>
          </div>
          <div class="mt-3 flex items-center justify-between text-sm text-slate-600">
            <span>Total</span>
            <span class="text-2xl font-black text-[#18823f]">৳{{ selectedPackage?.price || 0 }}</span>
          </div>
        </div>

        <button type="button" class="btn-primary mt-6 w-full py-3 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed" :disabled="orderLoading" @click="placeOrder">
          <svg v-if="orderLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          {{ orderLoading ? (paymentMethod === 'instant' ? 'Redirecting to payment...' : 'Processing...') : 'Buy Now' }}
        </button>
        <p v-if="orderMessage" class="mt-3 text-sm text-[#18823f]">{{ orderMessage }}</p>

        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-4">
          <div class="text-sm font-semibold uppercase tracking-[0.3em] text-[#18823f]">Rules & Conditions</div>
          <div class="mt-3 text-sm text-slate-600" v-html="product?.rules_text || ''" />
        </div>
      </div>
    </div>
  </section>
</template>
