<?php
// Simple Health Check Script
$logPath = __DIR__ . '/../storage/logs/bot_check.log';
$message = date('Y-m-d H:i:s') . " - Health Check: Server is running and PHP is executed successfully.\n";

// Try to write to log
if (file_put_contents($logPath, $message, FILE_APPEND)) {
    echo "<h1>✅ OK: Server Running!</h1><p>Log written to storage/logs/bot_check.log</p>";
} else {
    echo "<h1>⚠️ WARNING: Server Running but cannot write to logs!</h1><p>Check permissions.</p>";
}
echo "<p>PHP Version: " . phpversion() . "</p>";
