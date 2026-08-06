import { createError, defineEventHandler, readMultipartFormData } from 'h3';
import { mkdirSync, writeFileSync } from 'node:fs';
import { extname, join } from 'node:path';

export default defineEventHandler(async (event) => {
  const parts = await readMultipartFormData(event);
  if (!parts || parts.length === 0) {
    throw createError({ statusCode: 400, statusMessage: 'No form data received.' });
  }

  const folderPart = parts.find((part) => part.name === 'folder');
  const filePart = parts.find((part) => part.name === 'file' && part.filename && part.data);

  if (!filePart || !filePart.filename || !filePart.data) {
    throw createError({ statusCode: 400, statusMessage: 'No file uploaded.' });
  }

  const rawFolder = String(folderPart?.data?.toString('utf8') || 'logos').toLowerCase();
  const allowedFolders = new Set(['icons', 'logos', 'banners', 'products']);
  const folder = allowedFolders.has(rawFolder) ? rawFolder : 'logos';
  const extension = extname(filePart.filename).toLowerCase();
  const safeExt = extension && extension.length <= 5 ? extension : '.png';
  const fileName = `${Date.now()}-${Math.random().toString(36).slice(2, 10)}${safeExt}`;

  const targetDir = join(process.cwd(), 'public', 'uploads', folder);
  mkdirSync(targetDir, { recursive: true });

  const targetPath = join(targetDir, fileName);
  writeFileSync(targetPath, filePart.data);

  return {
    success: true,
    url: `/uploads/${folder}/${fileName}`
  };
});
