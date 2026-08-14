import { defineEventHandler } from 'h3';
import { buildContactSettings } from '../../utils/admin-settings';

export default defineEventHandler(async () => {
  return {
    success: true,
    contact: await buildContactSettings()
  };
});
