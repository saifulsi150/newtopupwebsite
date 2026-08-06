import { createError, defineEventHandler } from 'h3';
import { startLocalStartAppJob, toPublicSystemJob } from '../../utils/system-jobs';

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event);
  const deployAgentUrl = String(config.deployAgentUrl || '').trim();
  const deployWebhookToken = String(config.deployWebhookToken || '').trim();

  if (!deployAgentUrl || !deployWebhookToken) {
    const localJob = startLocalStartAppJob();
    const result = toPublicSystemJob(localJob);
    return {
      success: true,
      message: 'Deploy agent config missing. Running local Start App fallback.',
      jobId: result.jobId,
      status: result.status,
      logs: result.logs,
    };
  }

  const url = `${deployAgentUrl.replace(/\/$/, '')}/start-app`;

  try {
    const result = await $fetch<{
      success: boolean;
      message: string;
      jobId: string;
      status: string;
      logs: string[];
    }>(url, {
      method: 'POST',
      headers: {
        'x-deploy-token': deployWebhookToken,
      },
      timeout: 15_000,
    });

    return {
      success: true,
      message: result.message || 'Start App job started.',
      jobId: result.jobId,
      status: result.status,
      logs: result.logs || ['Start App job started.'],
    };
  } catch (error: any) {
    throw createError({
      statusCode: Number(error?.statusCode || error?.response?.status || 500),
      statusMessage: String(error?.data?.message || error?.statusMessage || 'Unable to start app.'),
    });
  }
});
