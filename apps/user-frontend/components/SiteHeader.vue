<template>
  <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/90 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 lg:px-8">
      <NuxtLink to="/" class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/15 text-lg font-black text-emerald-400">
          R
        </div>
        <div>
          <div class="text-lg font-black tracking-wide text-white">RG BAZZER</div>
          <div class="text-[11px] uppercase tracking-[0.3em] text-slate-400">Game Topup Hub</div>
        </div>
      </NuxtLink>

      <nav class="hidden items-center gap-6 md:flex">
        <a href="#games" class="nav-link">Games</a>
        <a href="#why-us" class="nav-link">Why Us</a>
        <a href="#support" class="nav-link">Support</a>
      </nav>

      <div class="flex items-center gap-3">
        <NuxtLink to="/products" class="hidden rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-emerald-400/40 hover:bg-emerald-500/10 sm:inline-flex">
          Browse Topup
        </NuxtLink>
        <a href="#games" class="btn-primary text-sm">
          Buy Now
        </a>
      </div>
    </div>
  </header>
</template>
