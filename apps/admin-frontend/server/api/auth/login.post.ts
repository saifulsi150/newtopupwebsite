import { createError, defineEventHandler, readBody } from 'h3';
import { checkAdminPassword, loadAdminAuth } from '../../utils/admin-auth';

export default defineEventHandler(async (event) => {
  const body = await readBody<{ email?: string; password?: string }>(event);
  const email = String(body?.email || '').trim().toLowerCase();
  const password = String(body?.password || '');

  if (!email || !password) {
    throw createError({ statusCode: 422, statusMessage: 'Email and password are required.' });
  }

  const admin = loadAdminAuth();
  if (email !== admin.email || !checkAdminPassword(password, admin.passwordHash)) {
    throw createError({ statusCode: 401, statusMessage: 'Invalid admin credentials.' });
  }

  return {
    success: true,
    admin: {
      id: admin.id,
      name: admin.name,
      email: admin.email
    }
  };
});