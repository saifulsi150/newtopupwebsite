import { defineEventHandler } from 'h3';
import { buildContactSettings } from '../../utils/admin-settings';

export default defineEventHandler(() => {
  return {
    success: true,
    contact: buildContactSettings()
  };
});
