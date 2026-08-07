import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { existsSync } from 'node:fs';

const port = Number(process.env.PORT || 8099);
const token = String(process.env.DEPLOY_WEBHOOK_TOKEN || '').trim();
const deployScriptPath = String(process.env.DEPLOY_SCRIPT_PATH || '/workspace/scripts/host-deploy.sh');
const startServicesScriptPath = String(process.env.START_SERVICES_SCRIPT_PATH || '/workspace/scripts/start-services.sh');
const startUserFrontendScriptPath = String(process.env.START_USER_FRONTEND_SCRIPT_PATH || '/workspace/scripts/start-user-frontend.sh');
const startAdminFrontendScriptPath = String(process.env.START_ADMIN_FRONTEND_SCRIPT_PATH || '/workspace/scripts/start-admin-frontend.sh');
const maxRunMs = Number(process.env.DEPLOY_MAX_MS || 20 * 60 * 1000);

/** @type {Map<string, {id: string, action: 'deploy'|'start-app', status: 'running'|'completed'|'failed', startedAt: string, endedAt?: string, logs: string[]}>} */
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

function getPublicJob(job) {
  return {
    success: true,
    jobId: job.id,
    action: job.action,
    status: job.status,
    startedAt: job.startedAt,
    endedAt: job.endedAt,
    logs: job.logs,
  };
}

function assertScriptExists(scriptPath, label) {
  if (!existsSync(scriptPath)) {
    throw new Error(`${label} script was not found at: ${scriptPath}`);
  }
}

function startJob(action, command) {
  const id = randomUUID();
  const job = {
    id,
    action,
    status: 'running',
    startedAt: new Date().toISOString(),
    logs: [`${action} job started.`],
  };
  jobs.set(id, job);
  runningJobId = id;

  const child = spawn('sh', ['-c', command], {
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
      appendLog(job, `[deploy-agent] ${action} finished successfully.`);
    } else {
      job.status = 'failed';
      appendLog(job, `[deploy-agent] ${action} failed with exit code ${code}.`);
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
    return json(res, 200, {
      ok: true,
      runningJobId,
      tokenConfigured: Boolean(token),
      scripts: {
        deploy: deployScriptPath,
        startServices: startServicesScriptPath,
        startUserFrontend: startUserFrontendScriptPath,
        startAdminFrontend: startAdminFrontendScriptPath,
      },
    });
  }

  const authToken = String(req.headers['x-deploy-token'] || '').trim();
  if (!token || authToken !== token) {
    return json(res, 401, {
      success: false,
      message: 'Unauthorized deploy request. Deploy token is missing or does not match DEPLOY_WEBHOOK_TOKEN.',
    });
  }

  if (req.method === 'POST' && (url.pathname === '/deploy' || url.pathname === '/start-app')) {
    if (runningJobId) {
      const runningJob = jobs.get(runningJobId);
      if (!runningJob) {
        runningJobId = null;
      } else {
        return json(res, 202, {
          ...getPublicJob(runningJob),
          message: 'Another job is already running. Returning current running job.',
        });
      }
    }

    if (url.pathname === '/deploy') {
      try {
        assertScriptExists(deployScriptPath, 'Deploy');
      } catch (error) {
        return json(res, 500, { success: false, message: String(error.message || error) });
      }

      const job = startJob('deploy', `sh ${deployScriptPath}`);
      return json(res, 202, {
        ...getPublicJob(job),
        message: 'Deployment started.',
      });
    }

    try {
      assertScriptExists(startServicesScriptPath, 'Start services');
      assertScriptExists(startUserFrontendScriptPath, 'Start user frontend');
      assertScriptExists(startAdminFrontendScriptPath, 'Start admin frontend');
    } catch (error) {
      return json(res, 500, { success: false, message: String(error.message || error) });
    }

    const command = `sh ${startServicesScriptPath} && sh ${startUserFrontendScriptPath} && sh ${startAdminFrontendScriptPath}`;
    const job = startJob('start-app', command);
    return json(res, 202, {
      ...getPublicJob(job),
      message: 'Start App job started.',
    });
  }

  if (req.method === 'GET' && url.pathname.startsWith('/deploy/')) {
    const jobId = url.pathname.slice('/deploy/'.length);
    const job = jobs.get(jobId);
    if (!job) {
      return json(res, 404, { success: false, message: 'Deployment job not found.' });
    }
    return json(res, 200, getPublicJob(job));
  }

  return json(res, 404, { success: false, message: 'Not found.' });
});

server.listen(port, '0.0.0.0', () => {
  console.log(`[deploy-agent] Listening on port ${port}`);
});
