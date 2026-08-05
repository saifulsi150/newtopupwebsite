export default <any>{
  scrollBehavior(to: { path?: string }, from: { path?: string }, savedPosition: { left: number; top: number } | null) {
    if (savedPosition) {
      return savedPosition;
    }

    // New page route should start from the top.
    if (to?.path !== from?.path) {
      return {
        left: 0,
        top: 0
      };
    }

    // Same page interactions should keep the current scroll position.
    return false;
  }
};