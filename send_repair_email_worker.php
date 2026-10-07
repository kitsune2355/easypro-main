<?php

date_default_timezone_set('Asia/Bangkok');
ignore_user_abort(true);
set_time_limit(0);

function workerLog($message)
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

    @file_put_contents(
        __DIR__ . '/repair_email_worker.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
}

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$repairId = isset($argv[1]) ? (int) $argv[1] : 0;

if ($repairId <= 0) {
    workerLog('Invalid repair_id');
    exit(1);
}

workerLog('Worker started. Repair ID: ' . $repairId);

sleep(30);

$emailUrl = 'https://happylandgroup.biz/es/send_repair_email.php';

$postData = http_build_query([
    'repair_id' => $repairId
]);

if (function_exists('curl_init')) {
    $ch = curl_init($emailUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        workerLog(
            'cURL failed. Repair ID: ' .
            $repairId .
            ' | Error: ' .
            $curlError
        );

        exit(1);
    }

    workerLog(
        'Email API completed. Repair ID: ' .
        $repairId .
        ' | HTTP: ' .
        $httpCode .
        ' | Response: ' .
        $response
    );

    exit($httpCode >= 200 && $httpCode < 300 ? 0 : 1);
}

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' =>
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n" .
            "Content-Length: " . strlen($postData) . "\r\n",
        'content' => $postData,
        'timeout' => 90,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents(
    $emailUrl,
    false,
    $context
);

if ($response === false) {
    workerLog('HTTP fallback failed. Repair ID: ' . $repairId);
    exit(1);
}

workerLog(
    'Email API completed with fallback. Repair ID: ' .
    $repairId .
    ' | Response: ' .
    $response
);

exit(0);