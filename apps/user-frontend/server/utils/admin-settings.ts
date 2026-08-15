import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizePublicImageUrl } from './media';

const SETTINGS_PATH = join(process.cwd(), '..', 'admin-frontend', '.data', 'settings.json');

type SettingsMap = Record<string, any>;

function toFlag(value: unknown, fallback = 0) {
  return Number(value ?? fallback) === 1 ? 1 : 0;
}

function normalizeLink(input: unknown) {
  const raw = String(input || '').trim();
  if (!raw) return '';
  if (/^(javascript|data|vbscript):/i.test(raw)) return '';
  return raw;
}

import { useDb } from './db';

export async function readAdminSettingsRaw(): Promise<SettingsMap> {
  try {
    const db = useDb();
    const [rows] = await db.query(`SELECT name, payload FROM settings WHERE \`group\` = 'general'`);
    const settings: SettingsMap = {};
    for (const row of (rows as any[])) {
      try {
        const parsed = JSON.parse(row.payload);
        // Spatie LaravelSettings encodes strings with quotes, e.g., '"tast"'
        settings[row.name] = parsed;
      } catch {
        settings[row.name] = row.payload;
      }
    }
    return settings;
  } catch (err) {
    console.error('Failed to read settings from DB', err);
    return {};
  }
}

export async function buildHomeSettings() {
  const s = await readAdminSettingsRaw();

  const db = useDb();
  let dbSliders: any[] = [];
  try {
    const [rows] = await db.query(`SELECT * FROM sliders WHERE status = 1 ORDER BY order_column ASC`);
    dbSliders = Array.isArray(rows) ? rows : [];
  } catch (err) {
    console.error('Failed to query sliders', err);
  }

  const sliderItems = dbSliders.map((item: any) => ({
      title: '',
      image_url: normalizePublicImageUrl(item?.image_url),
      link_url: String(item?.url || '').trim(),
      enabled: true
    })).filter((item: any) => item.image_url);

  const topSupportButtons = [
    {
      key: 'telegram',
      enabled: toFlag(s.top_support_telegram_enabled, 1) === 1,
      label: String(s.top_support_telegram_label || 'Telegram').trim() || 'Telegram',
      sub: 'SUPPORT',
      url: normalizeLink(s.top_support_telegram_url || s.contact_telegram_url || s.global_group_url || '')
    },
    {
      key: 'group',
      enabled: toFlag(s.top_support_group_enabled, 1) === 1,
      label: String(s.top_support_group_label || 'Join Group').trim() || 'Join Group',
      sub: 'COMMUNITY',
      url: normalizeLink(s.top_support_group_url || s.global_group_url || '')
    },
    {
      key: 'whatsapp',
      enabled: toFlag(s.top_support_whatsapp_enabled, 1) === 1,
      label: String(s.top_support_whatsapp_label || 'WhatsApp').trim() || 'WhatsApp',
      sub: 'CHAT',
      url: normalizeLink(s.top_support_whatsapp_url || s.global_whatsapp_url || s.contact_whatsapp_url || '')
    }
  ].filter((item) => item.enabled && Boolean(item.url));

  const pagePopupItems = (Array.isArray(s.home_page_popup_items) ? s.home_page_popup_items : [])
    .map((item: any) => ({
      title: String(item?.title || '').trim(),
      imageUrl: normalizePublicImageUrl(item?.image_url),
      note: String(item?.note || '').trim(),
      buttonLabel: String(item?.button_label || 'Click Here').trim() || 'Click Here',
      buttonUrl: String(item?.button_url || '').trim(),
      closeLabel: 'CLOSE',
      closeImageUrl: '',
      enabled: Number(item?.status ?? 1) === 1
    }))
  const noticeEnabled = toFlag(s.enable_notice ?? s.notice_enabled, 1) === 1;

  return {
    notice: noticeEnabled ? String(s.notice_content || s.home_notice_text || '').trim() : '',
    notice_bg_color: String(s.notice_background_color || '').trim(),
    notice_text_color: String(s.notice_font_color || '').trim(),
    theme_color: String(s.theme_color || '').trim(),
    background_color: String(s.background_color || '').trim(),
    showSlider: true,
    sliderItems,
    showTopSupport: toFlag(s.top_support_enabled, 1) === 1,
    topSupportButtons,
    showCategories: true,
    showLatestOrders: toFlag(s.latest_orders_enabled, 1) === 1,
    detectPopupEnabled: toFlag(s.detect_popup_enabled, 0) === 1,
    pagePopupEnabled: toFlag(s.home_page_popup_enabled, 0) === 1,
    pagePopupLimitPerDay: Math.max(1, Number(s.home_page_popup_limit_per_day || 5)),
    pagePopupItems
  };
}

export async function buildContactSettings() {
  const s = await readAdminSettingsRaw();
  return {
    site_name: String(s.site_name || '').trim(),
    site_icon_url: normalizePublicImageUrl(s.favicon || s.site_icon_url),
    logo_primary_url: normalizePublicImageUrl(s.logo || s.logo_primary_url),
    logo_secondary_url: normalizePublicImageUrl(s.logo || s.logo_secondary_url),
    theme_color: String(s.theme_color || '').trim(),
    background_color: String(s.background_color || '').trim(),
    navigation_background_color: String(s.navigation_background_color || '').trim(),
    notice_background_color: String(s.notice_background_color || '').trim(),
    notice_font_color: String(s.notice_font_color || '').trim(),
    footer_color: String(s.footer_color || '').trim(),
    footer_font_color: String(s.footer_font_color || '').trim(),
    show_whatsapp: toFlag(s.contact_whatsapp_enabled, 1) === 1,
    show_telegram: toFlag(s.contact_telegram_enabled, 1) === 1,
    support_center_whatsapp_url: String(s.global_whatsapp_url || s.contact_whatsapp_url || '').trim(),
    support_center_group_url: String(s.global_group_url || s.contact_telegram_url || '').trim(),
    stay_connected_message: String(s.stay_connected_message || '').trim(),
    social_facebook_url: String(s.social_facebook_url || '').trim(),
    social_instagram_url: String(s.social_instagram_url || '').trim(),
    social_youtube_url: String(s.social_youtube_url || '').trim(),
    social_email: String(s.social_email || '').trim(),
    pgw_app_enabled: toFlag(s.pgw_app_enabled, 1),
    pgw_force_install_enabled: toFlag(s.pgw_force_install_enabled, 0)
  };
}
