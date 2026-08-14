import { defineEventHandler } from 'h3';
import { buildHomeSettings } from '../../utils/admin-settings';

export default defineEventHandler(async () => {
  return {
    success: true,
    home: await buildHomeSettings()
  };
});
