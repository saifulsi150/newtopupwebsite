import { createError, defineEventHandler, readBody } from 'h3';
import { checkAdminPassword, createAdminPasswordHash, loadAdminAuth, saveAdminAuth } from '../../utils/admin-auth';

export default defineEventHandler(async (event) => {
  const body = await readBody<{ name?: string; email?: string; current_password?: string; new_password?: string }>(event);
  const admin = loadAdminAuth();
  const name = String(body?.name || '').trim();
  const email = String(body?.email || '').trim().toLowerCase();
  const currentPassword = String(body?.current_password || '');
  const newPassword = String(body?.new_password || '');

  if (!name || !email) {
    throw createError({ statusCode: 422, statusMessage: 'Name and email are required.' });
  }

  let passwordHash = admin.passwordHash;
  if (newPassword) {
    if (!currentPassword || !checkAdminPassword(currentPassword, admin.passwordHash)) {
      throw createError({ statusCode: 401, statusMessage: 'Current password is incorrect.' });
    }
    if (newPassword.length < 6) {
      throw createError({ statusCode: 422, statusMessage: 'New password must be at least 6 characters.' });
    }
    passwordHash = createAdminPasswordHash(newPassword);
  }

  const saved = saveAdminAuth({
    id: admin.id,
    name,
    email,
    passwordHash
  });

  return {
    success: true,
    admin: {
      id: saved.id,
      name: saved.name,
      email: saved.email
    }
  };
});