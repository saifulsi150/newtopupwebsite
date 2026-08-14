<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemUpdateController extends Controller
{
    public function runUpdate(Request $request)
    {
        // Validate secret token from request header
        $expectedToken = config('app.update_secret', env('SYSTEM_UPDATE_SECRET', ''));
        $providedToken = $request->header('X-Update-Token', '');

        if ($expectedToken && !hash_equals($expectedToken, $providedToken)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $logs = [];

        try {
            // Step 1: Pull latest code from GitHub
            $projectRoot = base_path('..');
            $gitOutput = shell_exec("git -C " . escapeshellarg($projectRoot) . " pull origin main 2>&1");
            $logs[] = 'Git: ' . trim((string) $gitOutput);
            Log::info('[SystemUpdate] Git pull: ' . $gitOutput);
        } catch (\Throwable $e) {
            $logs[] = 'Git pull note: ' . $e->getMessage();
            Log::warning('[SystemUpdate] Git pull warning: ' . $e->getMessage());
        }

        try {
            // Step 2: Run new migrations
            Artisan::call('migrate', ['--force' => true]);
            $logs[] = 'Migrate: ' . trim(Artisan::output());
        } catch (\Throwable $e) {
            Log::error('[SystemUpdate] Migration failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Migration failed: ' . $e->getMessage(),
                'logs'    => $logs,
            ], 500);
        }

        // Step 3: Clear and rebuild caches
        foreach (['config:clear', 'route:clear', 'cache:clear', 'config:cache', 'route:cache'] as $cmd) {
            try {
                Artisan::call($cmd);
                $logs[] = $cmd . ': OK';
            } catch (\Throwable $e) {
                $logs[] = $cmd . ': ' . $e->getMessage();
            }
        }

        Log::info('[SystemUpdate] Completed successfully.');

        return response()->json([
            'status'  => 'success',
            'message' => 'System updated successfully!',
            'logs'    => $logs,
        ]);
    }
}
