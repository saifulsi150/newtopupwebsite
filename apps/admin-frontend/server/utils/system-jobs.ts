import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { randomUUID } from 'node:crypto';

type JobStatus = 'running' | 'completed' | 'failed';

type SystemJob = {
  id: string;
  action: 'deploy' | 'start-app';
  status: JobStatus;
  startedAt: string;
  endedAt?: string;
  logs: string[];
};

const localJobs = new Map<string, SystemJob>();
const maxLogs = 1500;
const maxRunMs = 20 * 60 * 1000;
let runningLocalJobId = '';

function appendLog(job: SystemJob, chunk: unknown) {
  const lines = String(chunk || '')
    .replace(/\r/g, '')
    .split('\n')
    .filter(Boolean);

  for (const line of lines) {
    job.logs.push(line);
  }

  if (job.logs.length > maxLogs) {
    job.logs = job.logs.slice(-maxLogs);
  }
}

function getRepoRoot() {
  return resolve(process.cwd(), '..', '..');
}

function buildLocalUpdateCommand() {
  if (process.platform === 'win32') {
    return [
      'Write-Host "[local-update] Pulling latest code..."',
      'git pull origin main',
      'Write-Host "[local-update] Running Laravel migrations..."',
      'Set-Location services',
      'php artisan migrate --force',
      'Write-Host "[local-update] Clearing and rebuilding caches..."',
      'php artisan optimize:clear',
      'php artisan config:cache',
      'php artisan route:cache',
      'php artisan view:cache'
    ].join('; ');
  }

  return [
    'echo "[local-update] Pulling latest code..."',
    'git pull origin main',
    'echo "[local-update] Running Laravel migrations..."',
    'cd services',
    'php artisan migrate --force',
    'echo "[local-update] Clearing and rebuilding caches..."',
    'php artisan optimize:clear',
    'php artisan config:cache',
    'php artisan route:cache',
    'php artisan view:cache'
  ].join(' && ');
}

function buildLocalStartCommand() {
  if (process.platform === 'win32') {
    return [
      'Write-Host "[local-start] Starting full app stack..."',
      '& ".\\Start App.bat"'
    ].join('; ');
  }

  return [
    'echo "[local-start] Starting full app stack..."',
    'sh ./deploy.sh'
  ].join(' && ');
}

function startLocalJob(action: 'deploy' | 'start-app', command: string): SystemJob {
  if (runningLocalJobId) {
    const running = localJobs.get(runningLocalJobId);
    if (running && running.status === 'running') {
      return running;
    }
    runningLocalJobId = '';
  }

  const id = randomUUID();
  const job: SystemJob = {
    id,
    action,
    status: 'running',
    startedAt: new Date().toISOString(),
    logs: [`${action} job started (local fallback).`]
  };
  localJobs.set(id, job);
  runningLocalJobId = id;

  const cwd = getRepoRoot();
  const child = process.platform === 'win32'
    ? spawn('powershell', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', command], { cwd, env: process.env })
    : spawn('sh', ['-lc', command], { cwd, env: process.env });

  const timer = setTimeout(() => {
    appendLog(job, `[local-job] Timeout after ${maxRunMs}ms.`);
    try {
      child.kill('SIGTERM');
    } catch {
      // ignore
    }
  }, maxRunMs);

  child.stdout.on('data', (d) => appendLog(job, d));
  child.stderr.on('data', (d) => appendLog(job, d));

  child.on('close', (code) => {
    clearTimeout(timer);
    job.endedAt = new Date().toISOString();
    if (Number(code || 0) === 0) {
      job.status = 'completed';
      appendLog(job, `[local-job] ${action} finished successfully.`);
    } else {
      job.status = 'failed';
      appendLog(job, `[local-job] ${action} failed with exit code ${code}.`);
    }
    if (runningLocalJobId === job.id) {
      runningLocalJobId = '';
    }
  });

  child.on('error', (err) => {
    clearTimeout(timer);
    job.endedAt = new Date().toISOString();
    job.status = 'failed';
    appendLog(job, `[local-job] Failed to start process: ${err.message}`);
    if (runningLocalJobId === job.id) {
      runningLocalJobId = '';
    }
  });

  return job;
}

export function startLocalSystemUpdateJob() {
  return startLocalJob('deploy', buildLocalUpdateCommand());
}

export function startLocalStartAppJob() {
  return startLocalJob('start-app', buildLocalStartCommand());
}

export function getLocalSystemJob(jobId: string) {
  return localJobs.get(jobId);
}

export function toPublicSystemJob(job: SystemJob) {
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