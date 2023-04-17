<?php
// Скрипт создает задачи(записи в таблице tasks) которые мы потом будем разбирать чтобы отправить на почту пользователю, напоминание об истечении срока подписки
// Можно было подключить брокер сообщений например RabbitMQ и отправлять сообщения туда
// Но в рамках тестового задания показалось избыточным, это скорее следующий шаг для масштабирования и оптимизации сервиса
// Также этот скрипт стоило повесить на cron, но для прототипа в рамках тестового и для удобства отладки будем запускать скрипт руками =)


require_once("connection.php");
require_once("tasks.php");

echo "<pre>Запуск скрипта для формирования списка задач на отправку emails";

$connection = getConnection();
$connection->select_db(getenv("MYSQL_DATABASE"));

$chunkSize = getenv("CHUNK_USERS_FOR_SEND_TASKS");
$chunkNum = 0;
while (true) {
    $users = getUsersToSend($connection, $chunkSize);
    $chunkNum++;
    if (count($users) < 1) {
        echo "<pre> Нет подходящих users для отправки писем";
        break;
    }

    echo "<pre> Ставим задачи для chunk#" . $chunkNum . ", по $chunkSize шт.";
    createTasks($connection, $users, TASK_TYPE_SEND);
}

echo "<pre>Cкрипт для формирования списка задач на отправку emails закончил работу";

// Сейчас мы отбираем только те email которые подтверждены или у них валидная почта
// и которые истекают меньше чем через 3 дня (DAYS_BEFORE_EXPIRED_SUBSCRIPTION) и на них еще не заведены задачи
function getUsersToSend($connection, $chunkSize) {
    $daysBeforeExpiredSubscription = getenv("DAYS_BEFORE_EXPIRED_SUBSCRIPTION");
    $sql = "
        SELECT users.email, users.confirmed, emails.checked, emails.valid, tasks.status
        FROM users
        LEFT JOIN emails ON emails.email = users.email
        LEFT JOIN tasks ON tasks.email = users.email AND tasks.type = '".TASK_TYPE_SEND."'
        WHERE 
              (users.confirmed=1 OR emails.valid=1) 
            AND tasks.status IS NULL 
            AND (users.validts >= NOW() and users.validts < NOW() + INTERVAL $daysBeforeExpiredSubscription DAY) 
        LIMIT " . intval($chunkSize) . ";";

    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}