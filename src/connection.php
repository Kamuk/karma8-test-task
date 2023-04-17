<?php
// Устанавливает соединение с БД mysql используя переменные окружения из .env файла

// Проверяем на всякий случай что установлены переменные окружения, если нет устанавливаем
if (!isExistEnvironmentVariable()) {
    setEnvironmentVariables();
}

function isExistEnvironmentVariable(): bool|array|string
{
    return getenv("MYSQL_DATABASE");
}

function setEnvironmentVariables()
{
    $env = parse_ini_file('.env');
    foreach ($env as $varName => $varVal)
    {
        putenv("$varName=$varVal");
    }
}

function getConnection()
{
    try {
        $connection = mysqli_connect('mysql', getenv("MYSQL_USER"), getenv("MYSQL_PASSWORD"));
    } catch (Exception) {
        $error = mysqli_connect_errno() ? "Error connection database: " . mysqli_connect_error() : "Unknown error database connection";
        exit($error);
    }

    return $connection;
}