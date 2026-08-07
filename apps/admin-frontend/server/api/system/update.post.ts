import { createError, defineEventHandler } from 'h3';
import { startLocalSystemUpdateJob, toPublicSystemJob } from '../../utils/system-jobs';

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event);
  const deployAgentUrl = String(config.deployAgentUrl || '').trim();
  const deployWebhookToken = String(config.deployWebhookToken || '').trim();

  if (!deployAgentUrl || !deployWebhookToken) {
    const localJob = startLocalSystemUpdateJob();
    const result = toPublicSystemJob(localJob);
    return {
      success: true,
      message: 'Deploy agent config missing. Running local update fallback.',
      jobId: result.jobId,
      status: result.status,
      logs: result.logs,
    };
  }

  const url = `${deployAgentUrl.replace(/\/$/, '')}/deploy`;

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
      message: result.message || 'Deployment started.',
      jobId: result.jobId,
      status: result.status,
      logs: result.logs || ['Deployment started.'],
    };
  } catch (error: any) {
    const statusCode = Number(error?.statusCode || error?.response?.status || 500);
    const statusMessage = String(error?.data?.message || error?.statusMessage || 'Unable to start deployment.');

    if (statusCode === 401 || statusCode === 403) {
      throw createError({
        statusCode,
        statusMessage: 'Deploy agent token mismatch. Sync DEPLOY_WEBHOOK_TOKEN between admin-frontend and deploy-agent.',
      });
    }

    const localJob = startLocalSystemUpdateJob();
    const localResult = toPublicSystemJob(localJob);
    return {
      success: true,
      message: `Deploy agent unreachable (${statusMessage}). Running local update fallback.`,
      jobId: localResult.jobId,
      status: localResult.status,
      logs: [
        `[fallback] Deploy agent request failed: ${statusMessage}`,
        ...(localResult.logs || []),
      ],
    };
  }
});
