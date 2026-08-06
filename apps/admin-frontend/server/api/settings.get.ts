import { defineEventHandler } from 'h3';
import { readSettings } from '../utils/settings-store';

export default defineEventHandler(() => {
  return {
    success: true,
    settings: readSettings()
  };
});
