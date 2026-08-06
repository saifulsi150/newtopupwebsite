import { defineEventHandler } from 'h3';

export default defineEventHandler(() => {
  return {
    success: true,
    message: 'Settings saved. Restart can be handled by your process manager in production.'
  };
});
