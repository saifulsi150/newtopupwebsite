export default defineNuxtPlugin(() => {
  const routesToPrime = ["/", "/speed", "/status"];

  const warmRoute = (route: string) => {
    preloadRouteComponents(route);
    prefetchComponents(route);
  };

  const prime = () => {
    for (const route of routesToPrime) {
      warmRoute(route);
    }
  };

  const anchorIntentHandler = (event: Event) => {
    const target = event.target as HTMLElement | null;
    if (!target) {
      return;
    }

    const anchor = target.closest("a[href]") as HTMLAnchorElement | null;
    if (!anchor) {
      return;
    }

    const href = anchor.getAttribute("href");
    if (!href || !href.startsWith("/")) {
      return;
    }

    warmRoute(href);
  };

  if (typeof window !== "undefined" && "requestIdleCallback" in window) {
    window.requestIdleCallback(prime, { timeout: 1200 });
  } else {
    setTimeout(prime, 300);
  }

  window.addEventListener("pointerover", anchorIntentHandler, { passive: true });
  window.addEventListener("touchstart", anchorIntentHandler, { passive: true });
});
