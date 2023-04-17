<?php
// Скрипт создает задачи(записи в таблице tasks) которые мы потом будем разбирать чтобы проверять на валидность почту пользователей
// Можно было подключить брокер сообщений например RabbitMQ и отправлять сообщения туда
// Но в рамках тестового задания показалось избыточным, это скорее следующий шаг для масштабирования и оптимизации сервиса
// Также этот скрипт стоило повесить на cron, но для прототипа в рамках тестового и для удобства отладки будем запускать скрипт руками =)

require_once("connection.php");
require_once("tasks.php");

echo "<pre>Запуск скрипта для формирования списка задач на проверку emails";

$connection = getConnection();
$connection->select_db(getenv("MYSQL_DATABASE"));

// Задаем размер чанка, перебирать будем чанками чтобы не падать по памяти, также можно было на генераторе сделать перебор записей
$chunkSize = getenv("CHUNK_USERS_FOR_CHECK_TASKS");
$chunkNum = 0;
while (true) {
    $users = getUsersToCheck($connection, $chunkSize);
    $chunkNum++;
    if (count($users) < 1) {
        echo "<pre>Нет подходящих users для проверки писем";
        break;
    }

    echo "<pre> Получаем chunk#" . $chunkNum . ", по $chunkSize шт.";
    createTasks($connection, $users, TASK_TYPE_CHECK);
}

echo "<pre>Cкрипт для формирования списка задач на проверку emails закончил работу";

// Сейчас мы отбираем только те email которые не подтверждены и никогда не проверялись ранее, а также на них нет заведенных задач в таблице tasks.
// В дальнейшем можно добавить поля с датами изменения почты у пользователя и датой проверки почты
// И если пользователь изменит почту, то проверим новую почту, но пока для простоты считаем что пользователь не может менять почту
function getUsersToCheck($connection, $chunkSize) {
    $sql = "
        SELECT users.email, users.confirmed, emails.checked, emails.valid
        FROM users
        LEFT JOIN emails ON emails.email = users.email 
        LEFT JOIN tasks ON tasks.email = users.email AND tasks.type = '".TASK_TYPE_CHECK."'
        WHERE (users.confirmed=0 AND (emails.checked=0 OR emails.checked IS NULL))
            AND tasks.status IS NULL 
        LIMIT " . intval($chunkSize) . ";";

    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}
