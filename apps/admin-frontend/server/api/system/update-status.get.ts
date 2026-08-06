import { createError, defineEventHandler, getQuery } from 'h3';

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
    throw createError({ statusCode: 500, statusMessage: 'Deploy agent config is missing.' });
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
    throw createError({
      statusCode: Number(error?.statusCode || error?.response?.status || 500),
      statusMessage: String(error?.data?.message || error?.statusMessage || 'Unable to read deployment status.'),
    });
  }
});
