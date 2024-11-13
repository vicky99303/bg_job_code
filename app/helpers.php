<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('runBackgroundJob')) {
    function runBackgroundJob($class, $method, array $params = [], $attempts = 1)
    {
        $paramsJson = json_encode($params);

        $scriptPath = base_path('scripts/job_runner.php');
        $command = 'php "' . $scriptPath . "\" --class={$class} --method={$method} --params='" . addslashes($paramsJson) . "' --attempts={$attempts}";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B " . $command, "r"));
        } else {
            exec($command . " > /dev/null 2>&1 &");
        }

        Log::info("Dispatched background job", [
            'class' => $class,
            'method' => $method,
            'params' => $params,
            'attempts' => $attempts,
            'status' => 'dispatched',
        ]);
    }
}
