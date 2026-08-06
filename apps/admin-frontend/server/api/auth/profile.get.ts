import { defineEventHandler } from 'h3';
import { loadAdminAuth } from '../../utils/admin-auth';

export default defineEventHandler(() => {
  const admin = loadAdminAuth();
  return {
    profile: {
      id: admin.id,
      name: admin.name,
      email: admin.email
    }
  };
});