import { defineEventHandler, readBody } from 'h3';
import { saveSettings } from '../utils/settings-store';

export default defineEventHandler(async (event) => {
  const body = await readBody<Record<string, unknown>>(event);
  const settings = saveSettings(body || {});
  return {
    success: true,
    settings
  };
});
