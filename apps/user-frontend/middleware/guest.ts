export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuth();

  if (auth.isLoggedIn.value) {
    return navigateTo('/');
  }
});