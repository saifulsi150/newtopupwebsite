const ADMIN_ASSET_BASE = (
  process.env.NUXT_PUBLIC_ADMIN_ASSET_BASE ||
  `https://${process.env.ADMIN_DOMAIN || 'admin.ffuid.shop'}`
).replace(/\/+$/, '');

export function normalizePublicImageUrl(input: unknown): string {
  const raw = String(input || '').trim();
  if (!raw) return '';
  if (raw.includes('..')) return '';
  if (/^\/?(products|banners|logos|icons|uploads)\/[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp|gif)$/i.test(raw) === false && /^https?:\/\//i.test(raw) === false) {
    return '';
  }
  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.startsWith('/')) return `${ADMIN_ASSET_BASE}${raw}`;
  return `${ADMIN_ASSET_BASE}/${raw}`;
}
