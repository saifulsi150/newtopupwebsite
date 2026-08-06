import { defineEventHandler } from 'h3';
import { buildHomeSettings } from '../../utils/admin-settings';

export default defineEventHandler(() => {
  return {
    success: true,
    home: buildHomeSettings()
  };
});
