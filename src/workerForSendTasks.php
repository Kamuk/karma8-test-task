<?php
// Скрипт который мог бы стать воркером, при подключение брокера сообщений
// Разбирает записи из tasks с типом отправки почты и собственно отправляет почту =)
// Можно было по аналогии с generateSendTasks.php запустить бесконечный цикл пока не разберутся все задания
// Но добавил CHUNK_TASKS_FOR_SEND_TASKS ограничение для гибкости, можно либо увеличивать количество разбираемых задач значением этой переменой
// Либо запускать несколько скриптов в фоновом режиме для параллельной работы

require_once("connection.php");
require_once("tasks.php");

echo "<pre> Запуск разбора задач на отправку писем";
$connection = getConnection();
$connection->select_db(getenv("MYSQL_DATABASE"));

$tasks = getChunkTasks($connection);
if (count($tasks) < 1) {
    echo "<pre> В очереди нет задач на отправку писем, которые можно было бы взять в работу";
    die();
}

handlerTasks($connection, $tasks);

echo "<pre> Конец чанка по разбору задач на отправку писем";

// Берем задачи типа TASK_TYPE_SEND которые новые, либо завершались с ошибкой, но меньше TASK_MAX_RETRY раз
function getChunkTasks($connection) {
    $chunkSize = getenv("CHUNK_TASKS_FOR_SEND_TASKS");
    $sql = "
        SELECT tasks.type, tasks.email, tasks.status, tasks.retry, users.username 
        FROM tasks 
        LEFT JOIN users ON tasks.email = users.email
        WHERE tasks.type = '".TASK_TYPE_SEND."' AND (tasks.status = ".TASK_STATUS_NEW." OR (tasks.status = ".TASK_STATUS_ERROR." AND tasks.retry < ".TASK_MAX_RETRY.")) 
        LIMIT $chunkSize
    ";

    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Помечаем задачи как в процессе, чтобы при запсуке параллельно еще одной копии скрипта, не брать одинаковые задачи
// Далее выполняем sendEmail и в зависмости от результата ставим соответсвующий статус задаче
function handlerTasks($connection, $tasks) {
    markedTaskAsInProgress($connection, $tasks, TASK_TYPE_SEND);

    $subject = "Your subscription is expiring soon";
    $email_from = "karma8Test@gmail.com";
    foreach ($tasks as $task) {
        $body = $task['username'] . ", your subscription is expiring soon";
        $sendEmailResult = sendEmail($task['email'], $email_from, $task['email'], $subject, $body);
        if ($sendEmailResult) {
            $task['status'] = TASK_STATUS_SUCCESS;
        } else {
            $task['status'] = TASK_STATUS_ERROR;
            $task['retry'] = (int)$task['retry'] + 1;
        }
        unset($task['username']);
        updateTask($connection, $task);
    }
}


function sendEmail($email, $from, $to, $subj, $body): int
{
    echo "<pre>sendEmail: $email";
    sleep(rand(1,10));
    return rand(0,1); // 0 - ошибка, 1 - успешная отправка
}

