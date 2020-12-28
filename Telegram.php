<?php


class Telegram {
    const URL_BASE = 'https://api.telegram.org/bot';

    private $token;
    private $url;

    public function __construct($token) {
        $this->token = $token;
        $this->url = self::URL_BASE . $token . '/';
    }

    // https://core.telegram.org/bots/api#getupdates
    public function getUpdates($offset) {
        return $this->request('getUpdates', array('offset' => $offset));
    }

    // https://core.telegram.org/bots/api#sendmessage
    public function sendMessage($chatId, $text) {
        return $this->request('sendMessage', array('chat_id' => $chatId, 'text' => $text));
    }

    // https://core.telegram.org/bots/api#getme
    public function getMe(){
        return $this->request("getMe");
    }

    // https://core.telegram.org/bots/api#deletemessage
    public function deleteMessage($chatId, $messageId) {
        return $this->request("deleteMessage", ["chat_id"=> $chatId, "message_id"=>$messageId]);
    }

    // https://core.telegram.org/bots/api#senddocument
    public function sendDocument($chatId, $pathToFile){
        return $this->request('sendDocument', ["chat_id" => $chatId, "document" => $pathToFile]);
    }

    // https://core.telegram.org/bots/api#sendphoto
    public function sendPhoto($chatId, $pathToFile){

        if(filter_var($pathToFile, FILTER_VALIDATE_URL) === FALSE)
           return $this->request('sendPhoto', ["chat_id" => $chatId, "photo" => new CURLFile(realpath($pathToFile))]);

        return $this->request('sendPhoto', ["chat_id" => $chatId, "photo" => $pathToFile]);
    }

    // https://core.telegram.org/bots/api#answercallbackquery
    public function answerCallbackQuery($callbackQueryId, $data = []) {
        $data = array_merge(['callback_query_id' => $callbackQueryId], $data);
        return $this->request('answerCallbackQuery', $data);
    }

    private function request($tgMethod, $params = array()) {
        $url = $this->url . $tgMethod;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        $data = json_decode($response, true);

        if(!empty($data) && $data["ok"]){
            return $data['result'];
        }
        
        return $data;
    }
}