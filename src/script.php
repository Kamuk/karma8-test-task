<?php
// Иммитация работы крона для входной точки script.php

$hour = (int)date("h");
$minute = (int)date("m");

// Запуск два раза в день
if ($hour == 0 || $hour == 12) {
    echo "<pre>Запуск скрипта генерирующего задачи для проверки валидности почты";
    exec("php generateCheckTasks.php");

    echo "<pre>Запуск скрипта генерирующего задачи для отправки почты о просроченной подписке";
    exec("php generateSendTasks.php");
}

// Запуск каждые полчаса
if ($minute == 0 || $minute == 30)
{
    echo "<pre>Запуск скрипта иммитирующего воркер, разбирает задачи для проверки валидности почты";
    exec("php workerForCheckTasks.php");

    echo "<pre>Запуск скрипта иммитирующего воркер, разбирает задачи для отправки почты о просроченной подписке";
    exec("php workerForSendTasks.php");
}