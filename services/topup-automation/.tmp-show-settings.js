const fs = require('fs');
const dotenv = require('dotenv');
dotenv.config();
if (fs.existsSync('.env.local')) {
  const local = dotenv.parse(fs.readFileSync('.env.local'));
  for (const k in local) process.env[k] = local[k];
}
const db = require('./app/config/database');
(async () => {
  try {
    const [rows] = await db.pool.execute('SELECT settings FROM system_settings WHERE id=1');
    if (!rows.length) {
      console.log('NO_SETTINGS');
      process.exit(0);
    }
    const s = JSON.parse(rows[0].settings || '{}');
    console.log('website_api_url=' + (s.website_api_url || ''));
    console.log('website_api_key=' + (s.website_api_key || ''));
    const res = Array.isArray(s.resellers) ? s.resellers : [];
    console.log('reseller_count=' + res.length);
    for (let i = 0; i < res.length; i++) {
      const r = res[i] || {};
      console.log('reseller_' + i + '_name=' + (r.name || ''));
      console.log('reseller_' + i + '_api_url=' + (r.api_url || ''));
      console.log('reseller_' + i + '_api_key=' + (r.api_key || ''));
    }
    process.exit(0);
  } catch (e) {
    console.error('ERR', e.message);
    process.exit(1);
  }
})();
