<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const props = defineProps({
  settings: { type: Object, required: true },
  auth: { type: Object, default: () => ({ user: null }) },
});

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  form.post('/signin');
};
</script>

<template>
  <Head title="Login" />
  <SiteLayout :settings="settings" :auth="auth" :compact="true">
    <div class="login py-6">
      <div class="secondary-section">
        <div class="login-form mx-auto bg-white rounded-2xl shadow-lg border border-slate-200 p-4 md:p-6 max-w-md">
          <h1 class="text-2xl font-bold mb-4">Login</h1>
          <a href="/auth/redirect" class="block text-center bg-white border border-slate-300 rounded-md px-4 py-3 mb-4">Login with Google</a>
          <div class="flex justify-between items-center pt-2 pb-4">
            <hr class="w-1/5 px-2">
            <h1 class="text-gray-500 w-3/5 font-primary px-2 text-sm text-center">Or sign in with credentials</h1>
            <hr class="w-1/5 px-2">
          </div>

          <form @submit.prevent="submit">
            <div class="my-2">
              <label class="font-primary font-normal">Email</label>
              <input v-model="form.email" type="email" placeholder="Email" class="form-input relative block w-full border-0 rounded-md text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 ring-1 ring-inset focus:ring-2 focus:ring-black-900">
              <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
            </div>

            <div class="my-2">
              <label class="font-primary font-normal">Password</label>
              <input v-model="form.password" type="password" autocomplete="off" placeholder="Password" class="form-input relative block w-full border-0 rounded-md text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 ring-1 ring-inset focus:ring-2 focus:ring-black-900">
              <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
              <div v-if="form.errors.credential" class="text-red-600 text-sm mt-1">{{ form.errors.credential }}</div>
            </div>

            <button type="submit" class="w-full justify-center bg-pink-500 hover:bg-pink-600 text-white rounded-md py-3 font-semibold mt-4" :disabled="form.processing">
              {{ form.processing ? 'Loading...' : 'Login' }}
            </button>
          </form>

          <div class="mt-4 text-center text-sm">
            <Link href="/forget-password" class="text-pink-500">Forget Password?</Link>
          </div>
          <div class="mt-2 text-center text-sm">
            New user to {{ settings.site_name }} ? <a href="/register" class="text-pink-500">Register</a> Now
          </div>
        </div>
      </div>
    </div>
  </SiteLayout>
</template>
