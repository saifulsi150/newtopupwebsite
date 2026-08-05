let progressTimer: ReturnType<typeof setInterval> | null = null;
let finishTimer: ReturnType<typeof setTimeout> | null = null;

export default defineNuxtPlugin(() => {
  const router = useRouter();
  const loading = useState<boolean>('route-loading-active', () => false);
  const progress = useState<number>('route-loading-progress', () => 0);

  const clearTimers = () => {
    if (progressTimer) {
      clearInterval(progressTimer);
      progressTimer = null;
    }
    if (finishTimer) {
      clearTimeout(finishTimer);
      finishTimer = null;
    }
  };

  const start = () => {
    clearTimers();
    loading.value = true;
    progress.value = 6;

    progressTimer = setInterval(() => {
      if (progress.value < 82) {
        progress.value += Math.max(1, Math.floor((90 - progress.value) / 12));
      }
    }, 90);
  };

  const finish = () => {
    clearTimers();
    progress.value = 100;
    finishTimer = setTimeout(() => {
      loading.value = false;
      progress.value = 0;
    }, 180);
  };

  router.beforeEach((to, from) => {
    if (to.fullPath !== from.fullPath) {
      start();
    }
  });

  router.afterEach(() => {
    finish();
  });

  router.onError(() => {
    finish();
  });
});
