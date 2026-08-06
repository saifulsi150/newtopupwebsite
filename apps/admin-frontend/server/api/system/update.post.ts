import { createError, defineEventHandler } from 'h3';
import { execSync } from 'node:child_process';
import { resolve } from 'node:path';

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event);

  // Resolve project root (two levels up from apps/admin-frontend)
  const projectRoot = resolve(process.cwd(), '../..');
  const servicesDir = resolve(process.cwd(), '../../services');
  const isWin = process.platform === 'win32';

  const logs: string[] = [];

  // Step 1: Git pull
  try {
    const out = execSync(`git pull origin main`, {
      cwd: projectRoot,
      encoding: 'utf8',
      timeout: 60_000,
    });
    logs.push('Git: ' + out.trim());
  } catch (e: any) {
    // Non-fatal — already up to date returns exit 0, so a throw means a real issue
    logs.push('Git note: ' + String(e?.stdout || e?.message || 'unknown'));
  }

  // Step 2: Database migration
  try {
    const migrateOut = execSync(`php artisan migrate --force`, {
      cwd: servicesDir,
      encoding: 'utf8',
      timeout: 120_000,
    });
    logs.push('Migrate: ' + migrateOut.trim());
  } catch (e: any) {
    throw createError({
      statusCode: 500,
      statusMessage: 'Migration failed: ' + String(e?.stdout || e?.message),
    });
  }

  // Step 3: Clear caches
  const cacheCmds = ['config:clear', 'route:clear', 'cache:clear', 'config:cache', 'route:cache'];
  for (const cmd of cacheCmds) {
    try {
      execSync(`php artisan ${cmd}`, { cwd: servicesDir, encoding: 'utf8', timeout: 30_000 });
      logs.push(cmd + ': OK');
    } catch (e: any) {
      logs.push(cmd + ' note: ' + String(e?.message || ''));
    }
  }

  return {
    success: true,
    message: 'System updated successfully!',
    logs,
  };
});
