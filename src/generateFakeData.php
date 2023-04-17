<?php
// Скрипт инициализирует БД и заполняет данными таблицу users
// Можно было вынести инициализацию БД отдельно и стартовать при запуске контейнера c mysql, но для удобства отладки оставил пока здесь

require_once("connection.php");

$connection = getConnection();
if (isExistDatabase($connection)) {
    truncateTables($connection);
} else {
    initDB($connection);
}

generateFakeData($connection);


function isExistDatabase($connection): bool
{
    $sql = 'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE "'.getenv("MYSQL_DATABASE").'";';
    $result = $connection->query($sql);
    $row = $result->fetch_assoc();

    return (bool)$row['COUNT(*)'];
}

function truncateTables($connection) {
    $connection->select_db(getenv("MYSQL_DATABASE"));
    $sql = "TRUNCATE TABLE `users`";
    $connection->query($sql);
    $sql = "TRUNCATE TABLE `emails`";
    $connection->query($sql);
    $sql = "TRUNCATE TABLE `tasks`";
    $connection->query($sql);
}

function initDB($connection) {
    $sql = "CREATE DATABASE IF NOT EXISTS `".getenv("MYSQL_DATABASE")."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    $connection->query($sql);
    $connection->select_db(getenv("MYSQL_DATABASE"));

    $sql = "CREATE TABLE IF NOT EXISTS `users` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `validts` timestamp NULL DEFAULT NULL,
  `confirmed` tinyint NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $connection->query($sql);

    $sql = "ALTER TABLE `users`
  ADD PRIMARY KEY (`email`),
  ADD KEY `users_validts_index` (`validts`),
  ADD KEY `users_confirmed_index` (`confirmed`);";
    $connection->query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `emails` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked` tinyint NULL DEFAULT 0,
  `valid` tinyint NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $connection->query($sql);
    $sql = "ALTER TABLE `emails`
  ADD PRIMARY KEY (`email`),
  ADD KEY `emails_checked_index` (`checked`),
  ADD KEY `emails_valid_index` (`valid`);";
    $connection->query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `tasks` (
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NULL DEFAULT 0,
  `retry` tinyint NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $connection->query($sql);
    $sql = "ALTER TABLE `tasks`
  ADD UNIQUE `emails_type_index` (`email`, `type`);";
    $connection->query($sql);
}

// Заполнение БД тестовыми данными, можно было делать мультивставки, но по скольку скрипт инициализирующий то его производительность не критична
function generateFakeData($connection)
{
    $users_count = getenv("COUNT_FAKE_USERS_FOR_GENERATE");
    for ($i = 1; $i <= $users_count; $i++) {
        $name = uniqid();
        $email = $name . "@gmail.com";
        $validTs = date("Y-m-d H:i:s", time() + rand(3600, 3600*24*10));
        $confirmed = rand(0,1);

        $sql = "INSERT INTO users (username, email, validts, confirmed) VALUES ('".mysqli_real_escape_string($connection, $name)."', '".mysqli_real_escape_string($connection, $email)."', '".mysqli_real_escape_string($connection, $validTs)."', ".intval($confirmed).");";
        mysqli_query($connection, $sql);

        if ($i % 10 == 0) echo "<pre>Создано:". $i ." ";
    }

    echo "<pre>Данные сгенерированы";
}