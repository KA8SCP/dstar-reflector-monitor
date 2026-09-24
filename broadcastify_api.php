<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__.'/broadcastify.php';
try {
    echo json_encode(broadcastify_status(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['ok'=>false,'configured'=>false,'feed_id'=>BROADCASTIFY_FEED_ID,'error'=>'REF049C audio status temporarily unavailable.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
}
