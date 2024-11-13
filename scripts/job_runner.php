<?php
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check if running from the command line
if (php_sapi_name() !== 'cli') {
	echo "This script can only be run from the command line.";
	exit(1);
}

// Fetch command-line options
$options = getopt('', ['class:', 'method:', 'params::', 'attempts::']);
$className = $options['class'] ?? null;
$method = $options['method'] ?? null;
$params = isset($options['params']) ? json_decode($options['params'], true) : [];
$attempts = isset($options['attempts']) ? (int)$options['attempts'] : 1;

$maxAttempts = 3; // Define max retries if needed

if (!$className || !$method) {
	echo "Please provide a class name and method.\n";
	exit(1);
}

$approvedClasses = ['App\\Jobs\\Bgjob']; // List of allowed classes

if (!in_array($className, $approvedClasses)) {
	echo "Unauthorized class: $className\n";
	exit(1);
}

try {
	if (!class_exists($className)) {
		throw new Exception("Class $className does not exist.");
	}

	$instance = new $className();

	if (!method_exists($instance, $method)) {
		throw new Exception("Method $method does not exist in class $className.");
	}

	$result = call_user_func_array([$instance, $method], $params);

	Log::channel('background_jobs')->info("Job executed successfully", [
		'class' => $className,
		'method' => $method,
		'params' => $params,
		'result' => $result,
		'status' => 'success',
	]);

	echo "Job executed successfully.\n";

} catch (Exception $e) {
	if ($attempts < $maxAttempts) {
		$attempts++;
		exec("php " . __FILE__ . " --class=$className --method=$method --params='" . json_encode($params) . "' --attempts=$attempts > /dev/null 2>&1 &");
	}

	Log::channel('background_jobs_errors')->error("Job execution failed", [
		'class' => $className,
		'method' => $method,
		'params' => $params,
		'error' => $e->getMessage(),
		'attempts' => $attempts,
		'status' => 'failure',
	]);

	echo "Job execution failed: " . $e->getMessage() . "\n";
}
