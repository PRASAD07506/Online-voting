<?php
date_default_timezone_set("Asia/Kolkata");

$localConfig = __DIR__ . "/dp.php";
$hostingConfig = __DIR__ . "/dp.hosting.php";
$serverName = strtolower($_SERVER["SERVER_NAME"] ?? "");
$isCli = (php_sapi_name() === "cli");
$isLocalRequest = $isCli || in_array($serverName, ["localhost", "127.0.0.1", "::1"], true);

if ($isLocalRequest && file_exists($localConfig)) {
    require_once $localConfig;
} elseif (!$isLocalRequest && file_exists($hostingConfig)) {
    require_once $hostingConfig;
} elseif (file_exists($localConfig)) {
    require_once $localConfig;
} elseif (file_exists($hostingConfig)) {
    require_once $hostingConfig;
} else {
    throw new RuntimeException("No database configuration file found in config/.");
}
?>
