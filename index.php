<?php

$TG_API = "";   //ТОКЕН БОТА
$CHAT_VALID = array(    //РАЗРЕШЁННЫЕ ПОЛЬЗОВАТЕЛИ
    "",
);


$CAM_DIRECTORY = __DIR__ . "/cam";  //ПУТЬ ДО ПАПКИ С КАМЕРАМИ

$TIMEOUT = 10;  //ВРЕМЯ ОЖИДАНИЯ СЕССИИ. Минимум на 5 сек меньше чем частота CRON

//Объявляем функции
//отправка только текста
function tg_txt($txt, $TG_CHAT_ID)
{
    global $TG_API;
    $arrayQuery = array(
        'chat_id' => $TG_CHAT_ID,
        'text' => $txt,
    );
    $ch = curl_init('https://api.telegram.org/bot' . $TG_API . '/sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
}

//Клавиатура
function tg_keyboard($txt, $keyboard, $TG_CHAT_ID)
{
    global $TG_API;
    //Обрабатываем клаву
    $keyboard = json_encode(
        array(
            'keyboard' => $keyboard,
            'one_time_keyboard' => FALSE,
            'resize_keyboard' => TRUE,
        )
    );
    $arrayQuery = array(
        'chat_id' => $TG_CHAT_ID,
        'text' => $txt,
        'reply_markup' => $keyboard,
    );
    $ch = curl_init('https://api.telegram.org/bot' . $TG_API . '/sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
}

//отправка фото/видео с подписью
function tg_singlemedia($txt, $media, $keyboard, $TG_CHAT_ID)
{
    //Обрабатываем клаву
    $keyboard = json_encode(
        array(
            'keyboard' => $keyboard,
            'one_time_keyboard' => FALSE,
            'resize_keyboard' => TRUE,
        )
    );

    global $TG_API;
    $arrayQuery = array(
        'chat_id' => $TG_CHAT_ID,
        'caption' => $txt,
        "photo" => curl_file_create($media['path'], "image/jpg", $media['media']),
        'reply_markup' => $keyboard,
    );
    $ch = curl_init('https://api.telegram.org/bot' . $TG_API . '/sendPhoto');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
}

//Байты в читаемые
function byte_to_sizeinfo($byte)
{
    if ($byte >= 1024 * 1024 * 1024) {
        //GB
        $rez = $byte / 1024 / 1024 / 1024;
        $sizemetr = " ГБайт";
    } elseif ($byte < 1024 * 1024 * 1024 and $byte >= 1024 * 1024) {
        //MB
        $rez = $byte / 1024 / 1024;
        $sizemetr = " МБайт";
    } elseif ($byte < 1024 * 1024 and $byte >= 1024) {
        //KB
        $rez = $byte / 1024;
        $sizemetr = " КБайт";
    } elseif ($byte < 1024) {
        //B
        $rez = $byte;
        $sizemetr = " Байт";
    } else {
        $rez = "0";
        $sizemetr = "";
    }

    return round($rez, 2) . $sizemetr;
}

//Получаем смещение
if (file_exists(__DIR__ . '/last_mess.txt')) {
    $offset = file_get_contents(__DIR__ . '/last_mess.txt') + 1;
} else {
    $offset = 0;
}

//Запрашиваем уведомление от ТГ
$tg = file_get_contents('https://api.telegram.org/bot'.$TG_API.'/getUpdates?timeout='.$TIMEOUT.'&offset=' . $offset);
$tg = json_decode($tg);
//Отсекаем пустой ответ
if (count($tg->result) == 0) {
    echo "Ok";
    die;
}
//Формируем ответ
foreach ($tg->result as $value) {
    //Логика
    $update_id = $value->update_id;
    $chat_id = $value->message->chat->id;
    $text = $value->message->text;
    if (in_array($chat_id, $CHAT_VALID)) {
        //Чат разрешён
        //Описываем клавиатуру
        $keyboard = array(
            array(
                array(
                    'text' => 'Фото с камер',
                    'callback_data' => '/cam',
                ),
                array(
                    'text' => 'Состояние сервера',
                    'callback_data' => '/serverinfo',
                ),
            ),
        );
        //Выбираем событие
        switch ($text) {
            case "/cam":
            case "Фото с камер":
            case "Текущее состояние камер":
                $sendmedia = array();
                $dir_file = scandir($CAM_DIRECTORY);
                unset($dir_file[0]);
                unset($dir_file[1]);
                $dir_file = array_values($dir_file);
                foreach ($dir_file as $value) {
                    $sendmedia = array(
                        'path' => $CAM_DIRECTORY . '/' . $value,
                        'media' => $value,
                    );
                    tg_singlemedia($value, $sendmedia, $keyboard, $chat_id);
                }
                break;
            case "/serverinfo":
            case "Состояние сервера":
                //Формируем инфу по серверу
                //Место на диске
                $disk_free_space = disk_free_space($CAM_DIRECTORY);
                $disk_total_space = disk_total_space($CAM_DIRECTORY);
                $disk_utility = round(100 - ($disk_free_space / $disk_total_space * 100), 2) . " %";
                $disk_free_space = byte_to_sizeinfo($disk_free_space);
                $disk_total_space = byte_to_sizeinfo($disk_total_space);
                //Сводим в сообщение
                $txt = "Информация по диску:\nВсего - {$disk_total_space}\nСвободно - {$disk_free_space}\nИспользование - {$disk_utility}";
                tg_keyboard($txt, $keyboard, $chat_id);
                break;
            default:
                tg_keyboard("Приветствую тебя", $keyboard, $chat_id);
                break;
        }
        //Сохраняем смещение
        file_put_contents(__DIR__ . '/last_mess.txt', $update_id);
    } else {
        //Чат запрещён
        tg_txt("Извините, Вы не авторизованы. ID - {$chat_id}", $chat_id);
        //Сохраняем смещение
        file_put_contents(__DIR__ . '/last_mess.txt', $update_id);
    }
}
echo "Ok";



