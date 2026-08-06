import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { randomUUID } from 'node:crypto';

const port = Number(process.env.PORT || 8099);
const token = String(process.env.DEPLOY_WEBHOOK_TOKEN || '').trim();
const deployScriptPath = String(process.env.DEPLOY_SCRIPT_PATH || '/workspace/scripts/host-deploy.sh');
const maxRunMs = Number(process.env.DEPLOY_MAX_MS || 20 * 60 * 1000);

/** @type {Map<string, {id: string, status: 'running'|'completed'|'failed', startedAt: string, endedAt?: string, logs: string[]}>} */
const jobs = new Map();
let runningJobId = null;

function json(res, status, payload) {
  res.writeHead(status, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(payload));
}

function appendLog(job, chunk) {
  const lines = String(chunk || '')
    .replace(/\r/g, '')
    .split('\n')
    .filter(Boolean);
  for (const line of lines) {
    job.logs.push(line);
  }
  if (job.logs.length > 1500) {
    job.logs = job.logs.slice(-1500);
  }
}

function startDeployJob() {
  const id = randomUUID();
  const job = {
    id,
    status: 'running',
    startedAt: new Date().toISOString(),
    logs: ['Deployment job started.'],
  };
  jobs.set(id, job);
  runningJobId = id;

  const child = spawn('sh', [deployScriptPath], {
    cwd: '/workspace',
    env: process.env,
  });

  const timeout = setTimeout(() => {
    appendLog(job, `[deploy-agent] Timeout after ${maxRunMs}ms.`);
    child.kill('SIGTERM');
  }, maxRunMs);

  child.stdout.on('data', (d) => appendLog(job, d));
  child.stderr.on('data', (d) => appendLog(job, d));

  child.on('close', (code) => {
    clearTimeout(timeout);
    job.endedAt = new Date().toISOString();
    if (code === 0) {
      job.status = 'completed';
      appendLog(job, '[deploy-agent] Deployment finished successfully.');
    } else {
      job.status = 'failed';
      appendLog(job, `[deploy-agent] Deployment failed with exit code ${code}.`);
    }
    runningJobId = null;
  });

  child.on('error', (err) => {
    clearTimeout(timeout);
    job.status = 'failed';
    job.endedAt = new Date().toISOString();
    appendLog(job, `[deploy-agent] Failed to start deployment process: ${err.message}`);
    runningJobId = null;
  });

  return job;
}

const server = createServer((req, res) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);

  if (req.method === 'GET' && url.pathname === '/health') {
    return json(res, 200, { ok: true, runningJobId });
  }

  const authToken = String(req.headers['x-deploy-token'] || '').trim();
  if (!token || authToken !== token) {
    return json(res, 401, { success: false, message: 'Unauthorized deploy request.' });
  }

  if (req.method === 'POST' && url.pathname === '/deploy') {
    if (runningJobId) {
      return json(res, 409, {
        success: false,
        message: 'Another deployment is already running.',
        jobId: runningJobId,
      });
    }

    const job = startDeployJob();
    return json(res, 202, {
      success: true,
      message: 'Deployment started.',
      jobId: job.id,
      status: job.status,
      logs: job.logs,
    });
  }

  if (req.method === 'GET' && url.pathname.startsWith('/deploy/')) {
    const jobId = url.pathname.slice('/deploy/'.length);
    const job = jobs.get(jobId);
    if (!job) {
      return json(res, 404, { success: false, message: 'Deployment job not found.' });
    }
    return json(res, 200, { success: true, ...job });
  }

  return json(res, 404, { success: false, message: 'Not found.' });
});

server.listen(port, '0.0.0.0', () => {
  console.log(`[deploy-agent] Listening on port ${port}`);
});
