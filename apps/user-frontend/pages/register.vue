<script setup lang="ts">
definePageMeta({
  middleware: 'guest'
});

const auth = useAuth();
const form = reactive({ name: '', phone: '', email: '', password: '', confirmPassword: '' });
const error = ref('');
const loading = ref(false);

async function submit() {
  if (form.password !== form.confirmPassword) {
    error.value = 'Passwords do not match';
    return;
  }
  loading.value = true;
  try {
    const res = await $fetch<any>('/api/register', { method: 'POST', body: form });
    if (res && res.uid) {
      auth.login(res);
      await navigateTo('/');
    } else {
      error.value = 'Invalid response from server';
    }
  } catch (e: any) {
    error.value = e?.data?.error || e?.data?.message || 'Registration failed';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="page-shell flex min-h-[80vh] items-center justify-center">
    <div class="card-panel w-full max-w-xl p-8 lg:p-10">
      <div class="text-center">
        <h1 class="text-4xl font-black text-[#17395c]">Register</h1>
      </div>

      <div class="mt-8 grid gap-4 md:grid-cols-2">
        <label class="block text-sm font-semibold text-slate-700 md:col-span-2">
          Name
          <input v-model="form.name" class="input-shell mt-2" />
        </label>
        <label class="block text-sm font-semibold text-slate-700">
          Phone
          <input v-model="form.phone" class="input-shell mt-2" />
        </label>
        <label class="block text-sm font-semibold text-slate-700">
          Email
          <input v-model="form.email" type="email" class="input-shell mt-2" />
        </label>
        <label class="block text-sm font-semibold text-slate-700">
          Password
          <input v-model="form.password" type="password" class="input-shell mt-2" />
        </label>
        <label class="block text-sm font-semibold text-slate-700">
          Confirm Password
          <input v-model="form.confirmPassword" type="password" class="input-shell mt-2" />
        </label>
      </div>
      <p v-if="error" class="mt-3 text-sm text-rose-400">{{ error }}</p>
      <button type="button" class="btn-primary mt-6 w-full py-3" :disabled="loading" @click="submit">
        {{ loading ? 'Creating account...' : 'Register' }}
      </button>
      <div class="mt-6 text-center text-sm text-slate-600">
        Already registered? <NuxtLink to="/login" class="font-semibold text-[#18823f]">Login</NuxtLink> Now
      </div>
    </div>
  </section>
</template>
