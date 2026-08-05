export default defineNuxtRouteMiddleware((to) => {
  if (to.path === '/login') return;
  const auth = useAdminAuth();
  if (!auth.isLoggedIn.value) {
    return navigateTo('/login');
  }
});
