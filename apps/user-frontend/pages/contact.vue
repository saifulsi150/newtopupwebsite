<script setup lang="ts">
const { data } = await useFetch('/api/settings/contact', { server: false });
const contact = computed(() => data.value?.contact || {});
const visibleCards = computed(() => ([
  {
    key: 'whatsapp',
    enabled: Boolean(contact.value?.show_whatsapp),
    href: contact.value?.whatsapp,
    title: 'WhatsApp',
    desc: 'Chat with our support team'
  },
  {
    key: 'telegram',
    enabled: Boolean(contact.value?.show_telegram),
    href: contact.value?.telegram,
    title: 'Telegram',
    desc: 'Join the official support channel'
  },
  {
    key: 'email',
    enabled: Boolean(contact.value?.show_email),
    href: `mailto:${contact.value?.email || ''}`,
    title: 'Email',
    desc: contact.value?.email || 'Email support'
  },
  {
    key: 'phone',
    enabled: Boolean(contact.value?.show_phone),
    href: `tel:${contact.value?.phone || ''}`,
    title: 'Phone',
    desc: contact.value?.phone || 'Call us anytime'
  }
].filter((item) => item.enabled)));
</script>

<template>
  <section class="page-shell">
    <div class="card-panel p-8 lg:p-10">
      <div class="section-title">Contact Us</div>
      <h1 class="mt-3 text-3xl font-black text-slate-900">Get support anytime</h1>
      <div class="mt-8 grid gap-4 md:grid-cols-2">
        <a
          v-for="item in visibleCards"
          :key="item.key"
          :href="item.href"
          :target="item.key === 'whatsapp' || item.key === 'telegram' ? '_blank' : null"
          :rel="item.key === 'whatsapp' || item.key === 'telegram' ? 'noopener' : null"
          class="rounded-[24px] border border-slate-200 bg-white p-6 transition hover:border-emerald-400/40"
        >
          <div class="text-lg font-black text-slate-900">{{ item.title }}</div>
          <div class="mt-2 text-sm text-slate-600">{{ item.desc }}</div>
        </a>
      </div>
    </div>
  </section>
</template>
