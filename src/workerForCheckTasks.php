<?php
// Скрипт который мог бы стать воркером, при подключение брокера сообщений
// Разбирает записи из tasks с типом проверки почты и собственно проверяет почту =)
// Можно было по аналогии с generateCheckTasks.php запустить бесконечный цикл пока не разберутся все задания
// Но добавил CHUNK_TASKS_FOR_CHECK_TASKS ограничение для гибкости, можно либо увеличивать количество разбираемых задач значением этой переменой
// Либо запускать несколько скриптов в фоновом режиме для параллельной работы

require_once("connection.php");
require_once("tasks.php");
require_once("emails.php");

echo "<pre> Запуск разбора задач на проверку валидности почты";
$connection = getConnection();
$connection->select_db(getenv("MYSQL_DATABASE"));

$tasks = getChunkTasks($connection);
if (count($tasks) < 1) {
    echo "<pre> В очереди нет задач на проверка валидности писем, которые можно было бы взять в работу";
    die();
}

handlerTasks($connection, $tasks);

echo "<pre> Конец чанка по разбору задач  на проверку валидности почты";

// Берем задачи типа TASK_TYPE_CHECK которые новые, либо завершались с ошибкой, но меньше TASK_MAX_RETRY раз
function getChunkTasks($connection) {
    $chunkSize = getenv("CHUNK_TASKS_FOR_CHECK_TASKS");
    $sql = "
        SELECT tasks.type, tasks.email, tasks.status, tasks.retry
        FROM tasks 
        WHERE tasks.type = '".TASK_TYPE_CHECK."' AND (tasks.status = ".TASK_STATUS_NEW." OR (tasks.status = ".TASK_STATUS_ERROR." AND tasks.retry < ".TASK_MAX_RETRY.")) 
        LIMIT $chunkSize
    ";

    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Помечаем задачи как в процессе, чтобы при запсуке параллельно еще одной копии скрипта, не брать одинаковые задачи
// Далее выполняем checkEmail и в зависмости от резльтат ставим соответсвующий статус задаче и делаем запись в emails
function handlerTasks($connection, $sqlData) {
    markedTaskAsInProgress($connection, $sqlData, TASK_TYPE_CHECK);

    $emails = [];
    foreach ($sqlData as $item) {
        $task = [
            'type' => $item['type'],
            'email' => $item['email'],
            'status' => $item['status'],
            'retry' => $item['retry']
        ];

        $email['email'] = $item['email'];

        try {
            $checkEmailResult = checkEmail($task['email']);
            $task['status'] = TASK_STATUS_SUCCESS;
            $email['checked'] = 1;
            $email['valid'] = $checkEmailResult;

        } catch (Exception $e) {
            $task['status'] = TASK_STATUS_ERROR;
            $task['retry'] = (int)$task['retry'] + 1;
            $email['checked'] = 0;
            $email['valid'] = 0;
        }

        updateTask($connection, $task);
        $emails[] = $email;
    }

    createEmails($connection, $emails);
}

/**
 * Заглушка для проверки писем
 *
 * @throws Exception
 */
function checkEmail($email): int
{
    echo "<pre>checkEmail: $email";
    sleep(rand(1,60));
    $throw = rand(0,1); //Делаем вид что произошла проблема с проверкой письма
    if ($throw) {
        throw new Exception('Что-то пошло не так проверить почту не удалось');
    }
    return rand(0,1); // 0 - ошибка, 1 - успешная проверка почты
}

