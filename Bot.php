<?php
/*
* VKApi-Essential
*/
$confirmation_token = 'CONFIRMATION_TOKEN';
$token = 'TOKEN';
if (!isset($_REQUEST)) {
    return;
}
/*
 * BOT-Params
 */
$version = "WildBot v1.1.1";
$send_message = true;
/*
 * RCON-Data
 */
require_once('rcon.php');
$host = 'HOST'; // Server host name or IP
$port = 'PORT';                      // Port rcon is listening on
$password = 'PASSWORD'; // rcon.password setting set in server.properties
$timeout = 3;                    // How long to timeout.
$rcon = null;
//Other
$vkLowMessage = "";
function rconCommand ($command) {
    global $rcon, $host, $port, $password, $timeout;
    if (is_null($rcon)) {
        $rcon = new Rcon($host, $port, $password, $timeout);
    }
    if ($rcon->connect())
    {
        $rcon->sendCommand($command);
        return true;
    } else {
        return false;
    }
}

/*
 * Внутренние данные самого бота
*/
$message = "Hello World!";
$data = json_decode(file_get_contents('php://input'));
$user_id = $data->object->user_id;
switch ($data->type) {
    case 'confirmation':
        echo $confirmation_token;
        $send_message = false;
        break;
    case 'message_new':
        $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&v=5.0"));
        $user_name = $user_info->response[0]->first_name;

        $prefix = "Привет, ".$user_name."\n";
        $suffix = "\nС уважением, WildBot";

        //Текст Сообщения
        $message_body = $data->object->body;
        $vkLowMessage = strtolower($message_body);
        switch (true) {
            case (isCommand("/online")):
                $message = file_get_contents("http://wild-cubes.ga/wildcubes/vk/bot/commandOnline.php");
                break;
            case (isCommand("/version")):
                $message = "Версия бота: ".$version;
                break;
            case (isCommand("/subs")):
                $subs1 = file_get_contents("http://wild-cubes.ga/wildcubes/vk/bot/youtube/getSubsNum.php?id=UCDd-0cZBE7T_ixJ5e_9K5Kw");
                $subs2 = file_get_contents("http://wild-cubes.ga/wildcubes/vk/bot/youtube/getSubsNum.php?id=UCKo35vuG_lkEEX8HNIz2DZw");
                $message = "Подписчиков у PROgrammer_JARvis'а: ".$subs1.".\nПодписчиков у JuProJu: ".$subs2.".";
                break;
            case (isCommand("/help")):
                $helpers = explode(";",file_get_contents("http://wild-cubes.ga/wildcubes/vk/bot/helpers.list"));
                $helper = $helpers[array_rand($helpers)];
                $request_params = array(
                    'message' => "Пользователю [id".($user_info->response[0]->id)."|".$user_name."] требуется помощь:\n".substr($message_body, 6),
                    'user_id' => $helper,
                    'access_token' => $token
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);

                $prefix = "";
                $message = "Ваша просьба о помощи была отправлена агенту тех. поддержки.";
                $suffix = "";
                break;
            case (isCommand("/commands")):
                $prefix = "";
                $message = file_get_contents("http://wild-cubes.ga/wildcubes/vk/bot/help.txt");
                $suffix = "";
                break;
            default:
                $send_message = false;
                break;
        }
        sendMessage();
        echo('ok');
        break;
    case 'group_leave':
        $message = "Спасибо, что был с нами! Надеюсь, что ещё увидимся :D Пока!";
        sendMessage();
        echo('ok');
        break;
}
function sendMessage () {
    global $send_message, $prefix, $message, $suffix, $user_id, $token;
    if ($send_message) {
        /*
         * Ответ
         */
        $request_params = array(
            'message' => $prefix.$message.$suffix,
            'user_id' => $user_id,
            'access_token' => $token
        );
        $get_params = http_build_query($request_params);
        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    }
}
function isCommand ($command) {
    global $vkLowMessage;
    return (str_starts_with($vkLowMessage, $command));
}
//Thanks http://theoryapp.com/string-startswith-and-endswith-in-php/
function str_starts_with($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}
function str_ends_with($haystack, $needle)
{
    return strrpos($haystack, $needle) + strlen($needle) === strlen($haystack);
}
