import { createError, defineEventHandler, readBody, setCookie } from 'h3';
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

  const token = Buffer.from(
    JSON.stringify({
      admin: true,
      id: admin.id,
      name: admin.name,
      email: admin.email,
      iat: Date.now(),
    })
  ).toString('base64');

  setCookie(event, 'admin_token', token, {
    httpOnly: false,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    path: '/',
    maxAge: 60 * 60 * 12,
  });

  return {
    success: true,
    admin: {
      id: admin.id,
      name: admin.name,
      email: admin.email
    }
  };
});