<script setup lang="ts">
definePageMeta({
  middleware: 'guest'
});

const auth = useAuth();
const route = useRoute();
const config = useRuntimeConfig();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const requiredNotice = computed(() => route.query.required === '1');

const googleAuthUrl = computed(() => {
  const clientId = String(config.public.googleClientId || '').trim();
  const redirectUri = String(config.public.googleRedirectUri || '').trim();
  if (!clientId || !redirectUri) return '';
  const url = new URL('https://accounts.google.com/o/oauth2/v2/auth');
  url.searchParams.set('client_id', clientId);
  url.searchParams.set('redirect_uri', redirectUri);
  url.searchParams.set('response_type', 'code');
  url.searchParams.set('scope', 'openid email profile');
  url.searchParams.set('prompt', 'select_account');
  url.searchParams.set('access_type', 'offline');
  return url.toString();
});

async function submit() {
  if (!email.value.trim() || !password.value) {
    error.value = 'Email and password are required';
    return;
  }

  loading.value = true;
  error.value = '';
  try {
    const res = await $fetch<any>('/api/login', {
      method: 'POST',
      body: { email: email.value, password: password.value },
      timeout: 6000
    });

    if (res && res.uid) {
      auth.login(res);
      await navigateTo('/account');
    } else {
      error.value = 'Invalid response from server';
    }
  } catch (e: any) {
    error.value = e?.data?.error || e?.data?.message || 'Login failed. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="page-shell flex min-h-[80vh] items-center justify-center">
    <div class="card-panel w-full max-w-xl p-8 lg:p-10">
      <div class="text-center">
        <h1 class="text-4xl font-black text-[#17395c]">Login</h1>
      </div>

      <div class="mt-8 space-y-4">
        <div v-if="requiredNotice" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
          এই পেজ দেখতে আগে লগইন করত
        </div>
        <a
          v-if="googleAuthUrl"
          :href="googleAuthUrl"
          class="btn-secondary flex w-full items-center justify-center py-3 no-underline"
        >
          <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Login with Google
        </a>
        <div v-else class="flex w-full items-center justify-center rounded-lg border border-slate-300 py-3 text-sm text-slate-400">
          Google login not configured
        </div>
        <div class="flex items-center gap-3 text-sm text-slate-500">
          <span class="h-px flex-1 bg-slate-300" />
          <span>Or sign in with credentials</span>
          <span class="h-px flex-1 bg-slate-300" />
        </div>
        <label class="block text-sm font-semibold text-slate-700">
          Email
          <input v-model="email" type="email" class="input-shell mt-2" />
        </label>
        <label class="block text-sm font-semibold text-slate-700">
          Password
          <input v-model="password" type="password" class="input-shell mt-2" />
        </label>
        <p v-if="error" class="text-sm text-rose-400">{{ error }}</p>
        <button type="button" class="btn-primary w-full py-3" :disabled="loading" @click="submit">
          {{ loading ? 'Please wait...' : 'Login' }}
        </button>
      </div>

      <div class="mt-6 text-center text-sm text-slate-600">
        New user <NuxtLink to="/register" class="font-semibold text-[#18823f]">Register</NuxtLink> Now
      </div>
    </div>
  </section>
</template>
