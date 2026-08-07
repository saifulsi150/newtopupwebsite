import { createError, defineEventHandler, getQuery } from 'h3';
import { getLocalSystemJob, toPublicSystemJob } from '../../utils/system-jobs';

export default defineEventHandler(async (event) => {
  const query = getQuery(event);
  const jobId = String(query.jobId || '').trim();
  const config = useRuntimeConfig(event);
  const deployAgentUrl = String(config.deployAgentUrl || '').trim();
  const deployWebhookToken = String(config.deployWebhookToken || '').trim();

  if (!jobId) {
    throw createError({ statusCode: 422, statusMessage: 'jobId is required.' });
  }

  if (!deployAgentUrl || !deployWebhookToken) {
    const localJob = getLocalSystemJob(jobId);
    if (!localJob) {
      throw createError({ statusCode: 404, statusMessage: 'Local update job not found.' });
    }
    return toPublicSystemJob(localJob);
  }

  const url = `${deployAgentUrl.replace(/\/$/, '')}/deploy/${encodeURIComponent(jobId)}`;

  try {
    return await $fetch<{
      success: boolean;
      id: string;
      status: 'running' | 'completed' | 'failed';
      startedAt: string;
      endedAt?: string;
      logs: string[];
    }>(url, {
      method: 'GET',
      headers: {
        'x-deploy-token': deployWebhookToken,
      },
      timeout: 10_000,
    });
  } catch (error: any) {
    const statusCode = Number(error?.statusCode || error?.response?.status || 500);
    if (statusCode === 401 || statusCode === 403) {
      throw createError({
        statusCode,
        statusMessage: 'Deploy agent token mismatch. Sync DEPLOY_WEBHOOK_TOKEN between admin-frontend and deploy-agent.',
      });
    }

    throw createError({
      statusCode,
      statusMessage: String(error?.data?.message || error?.statusMessage || 'Unable to read deployment status.'),
    });
  }
});
