import { mkdirSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

type SettingsShape = Record<string, string | number | boolean | null | Array<Record<string, unknown>>>;

const STORAGE_PATH = join(process.cwd(), '.data', 'settings.json');

const DEFAULT_SETTINGS: SettingsShape = {
  home_notice_text: '',
  slider_enabled: 1,
  category_enabled: 1,
  top_support_enabled: 1,
  top_support_telegram_enabled: 1,
  top_support_group_enabled: 1,
  top_support_whatsapp_enabled: 1,
  latest_orders_enabled: 1,
  top_support_telegram_url: '',
  top_support_group_url: '',
  top_support_whatsapp_url: '',
  top_support_telegram_label: 'Telegram',
  top_support_group_label: 'Join Group',
  top_support_whatsapp_label: 'WhatsApp',
  contact_whatsapp_enabled: 1,
  contact_telegram_enabled: 1,
  contact_email_enabled: 1,
  contact_phone_enabled: 1,
  detect_popup_enabled: 0,
  stay_connected_message: '',
  global_whatsapp_url: '',
  global_group_url: '',
  social_facebook_url: '',
  social_instagram_url: '',
  social_youtube_url: '',
  social_email: '',
  site_name: '',
  site_icon_url: '',
  logo_primary_url: '',
  logo_secondary_url: '',
  theme_color: '',
  pgw_app_enabled: 1,
  pgw_force_install_enabled: 0,
  slider_items: [],
  home_page_popup_enabled: 0,
  home_page_popup_limit_per_day: 5,
  home_page_popup_items: [],
  contact_whatsapp_url: '',
  contact_telegram_url: '',
  contact_email: '',
  contact_phone: '',
  auto_api_name: '',
  auto_api_url: '',
  auto_api_secret_key: '',
  auto_api_items: []
};

function ensureStorageDir() {
  mkdirSync(dirname(STORAGE_PATH), { recursive: true });
}

export function readSettings(): SettingsShape {
  ensureStorageDir();
  if (!existsSync(STORAGE_PATH)) {
    writeFileSync(STORAGE_PATH, JSON.stringify(DEFAULT_SETTINGS, null, 2));
    return { ...DEFAULT_SETTINGS };
  }

  try {
    const parsed = JSON.parse(readFileSync(STORAGE_PATH, 'utf8')) as SettingsShape;
    return { ...DEFAULT_SETTINGS, ...parsed };
  } catch {
    writeFileSync(STORAGE_PATH, JSON.stringify(DEFAULT_SETTINGS, null, 2));
    return { ...DEFAULT_SETTINGS };
  }
}

export function saveSettings(input: Record<string, unknown>) {
  const current = readSettings();
  const merged: SettingsShape = { ...current };

  for (const [key, value] of Object.entries(input)) {
    if (!(key in DEFAULT_SETTINGS)) continue;
    if (typeof DEFAULT_SETTINGS[key] === 'number') {
      merged[key] = Number(value ?? 0);
      continue;
    }
    if (Array.isArray(DEFAULT_SETTINGS[key])) {
      merged[key] = Array.isArray(value) ? (value as Array<Record<string, unknown>>) : [];
      continue;
    }
    if (typeof DEFAULT_SETTINGS[key] === 'boolean') {
      merged[key] = Boolean(value);
      continue;
    }
    merged[key] = value == null ? '' : String(value);
  }

  ensureStorageDir();
  writeFileSync(STORAGE_PATH, JSON.stringify(merged, null, 2));
  return merged;
}
