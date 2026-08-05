export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuth();

  if (!auth.isLoggedIn.value && to.path !== '/login') {
    return navigateTo({
      path: '/login',
      query: {
        required: '1',
        redirect: to.fullPath
      }
    });
  }
});
