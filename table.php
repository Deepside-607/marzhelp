<?php
include 'config.php';

$botConn = new mysqli($botDbHost, $botDbUser, $botDbPass, $botDbName);
if ($botConn->connect_error) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Bot DB connection failed: " . $botConn->connect_error . "\n", FILE_APPEND);
    exit(1); 
}
$botConn->set_charset("utf8");

$marzbanConn = new mysqli($vpnDbHost, $vpnDbUser, $vpnDbPass, $vpnDbName);
if ($marzbanConn->connect_error) {
    file_put_contents('logs.txt', date('Y-m-d H:i:s') . " - VPN DB connection failed: " . $marzbanConn->connect_error . "\n", FILE_APPEND);
    exit;
}
$marzbanConn->set_charset("utf8");

function checkAndCreateTablesAndColumns($botConn) {
    $hasCriticalError = false;

    $tableAdminSettings = "CREATE TABLE IF NOT EXISTS `admin_settings` (
        `admin_id` int NOT NULL,
        `total_traffic` bigint DEFAULT NULL,
        `used_traffic` bigint DEFAULT 0,
        `expiry_date` date DEFAULT NULL,
        `status` JSON, 
        `user_limit` bigint DEFAULT NULL,
        `hashed_password_before` varchar(255) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `last_expiry_notification` timestamp NULL DEFAULT NULL,
        `last_traffic_notification` int DEFAULT NULL,
        `last_traffic_notify` int DEFAULT NULL,
        `calculate_volume` varchar(50) DEFAULT 'used_traffic',
        PRIMARY KEY (`admin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
    
    if (!$botConn->query($tableAdminSettings)) {
        echo "Critical error creating `admin_settings`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

    $tableUserStates = "CREATE TABLE IF NOT EXISTS `user_states` (
        `user_id` bigint NOT NULL,
        `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        `state` varchar(50) DEFAULT NULL,
        `admin_id` int DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `data` text,
        `message_id` int DEFAULT NULL,
        `template_index` int DEFAULT 0,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if (!$botConn->query($tableUserStates)) {
        echo "Critical error creating `user_states`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

    $tableUserTemporaries = "CREATE TABLE IF NOT EXISTS `user_temporaries` (
        `user_id` BIGINT NOT NULL,
        `user_key` varchar(50) NOT NULL,
        `value` text,
        PRIMARY KEY (`user_id`, `user_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if (!$botConn->query($tableUserTemporaries)) {
        echo "Critical error creating `user_temporaries`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

    $tableAdminUsage = "CREATE TABLE IF NOT EXISTS `admin_usage` (
        `id` bigint NOT NULL AUTO_INCREMENT,
        `admin_id` int NOT NULL,
        `used_traffic_gb` decimal(10,2) NOT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if (!$botConn->query($tableAdminUsage)) {
        echo "Critical error creating `admin_usage`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

    $columnsAdminSettings = [
        'hashed_password_before' => "ALTER TABLE `admin_settings` ADD `hashed_password_before` varchar(255) DEFAULT NULL;",
        'last_expiry_notification' => "ALTER TABLE `admin_settings` ADD `last_expiry_notification` timestamp NULL DEFAULT NULL;",
        'last_traffic_notification' => "ALTER TABLE `admin_settings` ADD `last_traffic_notification` int DEFAULT NULL;",
        'last_traffic_notify' => "ALTER TABLE `admin_settings` ADD `last_traffic_notify` int DEFAULT NULL;",
        'used_traffic' => "ALTER TABLE `admin_settings` ADD `used_traffic` bigint DEFAULT 0;",
        'calculate_volume' => "ALTER TABLE `admin_settings` ADD `calculate_volume` VARCHAR(50) DEFAULT 'used_traffic';"
    ];
    $hasCriticalError = $hasCriticalError || checkAndAddColumns($botConn, 'admin_settings', $columnsAdminSettings);

    $columnsUserStates = [
        'username' => "ALTER TABLE `user_states` ADD `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;",
        'lang' => "ALTER TABLE `user_states` ADD `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;",
        'state' => "ALTER TABLE `user_states` ADD `state` varchar(50) DEFAULT NULL;",
        'admin_id' => "ALTER TABLE `user_states` ADD `admin_id` int DEFAULT NULL;",
        'updated_at' => "ALTER TABLE `user_states` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;",
        'data' => "ALTER TABLE `user_states` ADD `data` text;",
        'message_id' => "ALTER TABLE `user_states` ADD `message_id` int DEFAULT NULL;",
        'template_index' => "ALTER TABLE `user_states` ADD COLUMN `template_index` INT DEFAULT 0 AFTER `message_id`;"
    ];
    $hasCriticalError = $hasCriticalError || checkAndAddColumns($botConn, 'user_states', $columnsUserStates);

    $columnsUserTemporaries = [
        'value' => "ALTER TABLE `user_temporaries` ADD `value` text;"
    ];
    $hasCriticalError = $hasCriticalError || checkAndAddColumns($botConn, 'user_temporaries', $columnsUserTemporaries);

    $columnStatusType = $botConn->query("SHOW COLUMNS FROM `admin_settings` LIKE 'status'")->fetch_assoc();
    if ($columnStatusType && strpos($columnStatusType['Type'], 'json') === false) {
        $cleanupQuery = "UPDATE `admin_settings` SET `status` = NULL WHERE `status` IS NOT NULL AND JSON_VALID(`status`) = 0;";
        if ($botConn->query($cleanupQuery) === TRUE) {
            echo "Invalid status values cleaned up.\n";
        } else {
            echo "Error cleaning up invalid status values: " . $botConn->error . "\n";
            $hasCriticalError = true;
        }
    
        $alterStatusQuery = "ALTER TABLE `admin_settings` MODIFY `status` JSON;";
        if ($botConn->query($alterStatusQuery) === TRUE) {
            echo "Column 'status' in 'admin_settings' modified to JSON successfully.\n";
    
            $defaultStatus = '{"data": "active", "time": "active", "users": "active"}';
            $updateExistingQuery = "UPDATE `admin_settings` SET `status` = '$defaultStatus' WHERE `status` IS NULL;";
            if ($botConn->query($updateExistingQuery) === TRUE) {
                echo "Existing records updated with default status.\n";
            } else {
                echo "Error updating existing records: " . $botConn->error . "\n";
                $hasCriticalError = true;
            }
    
            $triggerQuery = "
                DROP TRIGGER IF EXISTS set_default_status;
                CREATE TRIGGER set_default_status
                BEFORE INSERT ON admin_settings
                FOR EACH ROW
                BEGIN
                    IF NEW.status IS NULL THEN
                        SET NEW.status = '{\"data\": \"active\", \"time\": \"active\", \"users\": \"active\"}';
                    END IF;
                END;
            ";
            if ($botConn->multi_query($triggerQuery)) {
                echo "Trigger 'set_default_status' created successfully.\n";
                do {
                    if ($result = $botConn->store_result()) {
                        $result->free();
                    }
                } while ($botConn->next_result());
            } else {
                echo "Error creating trigger 'set_default_status': " . $botConn->error . "\n";
                $hasCriticalError = true;
            }
        } else {
            echo "Error modifying 'status' column in 'admin_settings': " . $botConn->error . "\n";
            $hasCriticalError = true;
        }
    }

    return $hasCriticalError;
}

function checkAndAddColumns($botConn, $tableName, $columns) {
    $hasCriticalError = false;

    foreach ($columns as $columnName => $alterQuery) {
        $result = $botConn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        if ($result->num_rows == 0) {
            if ($botConn->query($alterQuery) === TRUE) {
                echo "Column '$columnName' added to table '$tableName'.\n";
            } else {
                echo "Error adding column '$columnName' to table '$tableName': " . $botConn->error . "\n";
                $hasCriticalError = true;
            }
        }
    }

    return $hasCriticalError;
}

function setupCronJob($scriptPath) {
    $cronJob = "* * * * * /usr/bin/php $scriptPath";
    $oldCronJob = "* * * * * /usr/bin/php /var/www/html/marzhelp/cron.php";

    $currentCronJobs = shell_exec('crontab -l 2>/dev/null') ?: ''; 

    $cronLines = explode(PHP_EOL, trim($currentCronJobs));
    $newCronLines = [];

    foreach ($cronLines as $line) {
        if (trim($line) !== $oldCronJob) {
            $newCronLines[] = $line;
        }
    }

    if (!in_array($cronJob, $newCronLines)) {
        $newCronLines[] = $cronJob;
    }

    $newCronJobs = implode(PHP_EOL, $newCronLines) . PHP_EOL;

    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}

$hasCriticalError = checkAndCreateTablesAndColumns($botConn);

$createMarzhelpLimits = "
CREATE TABLE IF NOT EXISTS `marzhelp_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('exclude','dedicated') COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_id` int NOT NULL,
  `inbound_tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_limit` (`type`,`admin_id`,`inbound_tag`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `marzhelp_limits_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!$marzbanConn->query($createMarzhelpLimits)) {
    echo "Error creating table `marzhelp_limits`: " . $marzbanConn->error . "\n";
    $hasCriticalError = true;
}

$alterUserTemporaries = "ALTER TABLE `user_temporaries` MODIFY `user_id` BIGINT NOT NULL;";
if (!$botConn->query($alterUserTemporaries)) {
    echo "Error modifying `user_temporaries.user_id`: " . $botConn->error . "\n";
    $hasCriticalError = true;
}

$alterAdminSettings = "ALTER TABLE `admin_settings` MODIFY `calculate_volume` VARCHAR(50) DEFAULT 'used_traffic';";
if (!$botConn->query($alterAdminSettings)) {
    echo "Error modifying `admin_settings.calculate_volume`: " . $botConn->error . "\n";
    $hasCriticalError = true;
}

$scriptPath = "/var/www/html/marzhelp/crons/cron.php";
setupCronJob($scriptPath);

$marzbanConn->close();
$botConn->close();

exit($hasCriticalError ? 1 : 0);
?>