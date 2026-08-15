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
  <div class="login-wrapper">
    <div class="login-box">
      <h1 class="login-heading">Login</h1>

      <div class="login-form-area">
        <!-- Google Login -->
        <a v-if="googleAuthUrl" :href="googleAuthUrl" class="google-btn">
          <svg class="google-g" viewBox="0 0 24 24">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          <span>Login with Google</span>
        </a>

        <!-- Divider -->
        <div class="form-divider">
          <span>Or sign in with credentials</span>
        </div>

        <!-- Email -->
        <div class="input-group">
          <label for="email" class="input-label">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="Email"
            class="form-control"
            autocomplete="email"
            @keyup.enter="submit"
          />
        </div>

        <!-- Password -->
        <div class="input-group">
          <label for="password" class="input-label">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="Password"
            class="form-control"
            autocomplete="current-password"
            @keyup.enter="submit"
          />
        </div>

        <div v-if="error" class="error-text">
          {{ error }}
        </div>

        <!-- Login Button -->
        <button
          type="button"
          class="submit-btn"
          :disabled="loading"
          @click="submit"
        >
          {{ loading ? 'Signing in...' : 'Login' }}
        </button>

        <!-- Register Link -->
        <div class="bottom-switch">
          New user to RG BAZZER?
          <NuxtLink to="/register" class="switch-link">Register Now</NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-wrapper {
  min-height: 75vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
}

.login-box {
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 36px 30px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.login-heading {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 24px;
}

.login-form-area {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.google-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  height: 44px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #ffffff;
  color: #0f172a;
  font-size: 13.5px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.15s;
}

.google-btn:hover {
  background: #f8fafc;
}

.google-g {
  width: 18px;
  height: 18px;
}

.form-divider {
  text-align: center;
  position: relative;
  margin: 6px 0;
}

.form-divider::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  width: 100%;
  height: 1px;
  background: #e2e8f0;
  z-index: 1;
}

.form-divider span {
  position: relative;
  background: #ffffff;
  padding: 0 12px;
  color: #64748b;
  font-size: 12px;
  font-weight: 500;
  z-index: 2;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.input-label {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
}

.form-control {
  width: 100%;
  height: 44px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0 14px;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  background: #ffffff;
  transition: border-color 0.15s;
}

.form-control:focus {
  border-color: #0a6b2a;
}

.form-control::placeholder {
  color: #94a3b8;
}

.error-text {
  font-size: 12.5px;
  color: #e11d48;
  font-weight: 600;
}

.submit-btn {
  width: 100%;
  height: 44px;
  background: #0a6b2a;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 4px;
  transition: opacity 0.15s;
}

.submit-btn:hover {
  opacity: 0.92;
}

.submit-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.bottom-switch {
  text-align: center;
  font-size: 12.5px;
  color: #334155;
  margin-top: 6px;
}

.switch-link {
  color: #0a6b2a;
  font-weight: 700;
  text-decoration: none;
  margin-left: 4px;
}

.switch-link:hover {
  text-decoration: underline;
}
</style>
