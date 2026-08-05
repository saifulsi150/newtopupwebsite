const fs = require('fs');
const dotenv = require('dotenv');
dotenv.config();
if (fs.existsSync('.env.local')) {
  const local = dotenv.parse(fs.readFileSync('.env.local'));
  for (const k in local) process.env[k] = local[k];
}
const c = require('./app/controllers/websiteWebhookController');
(async () => {
  try {
    await c.sendResultToWebsite('7', 'Completed');
    console.log('CALLBACK_SENT');
    process.exit(0);
  } catch (e) {
    console.error('ERR', e.message);
    process.exit(1);
  }
})();
