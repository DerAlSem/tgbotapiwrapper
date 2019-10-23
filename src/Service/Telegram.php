<?php

namespace Deralsem\Tgbotapiwrapper\Service;

class Telegram
{
    
    private $apiUrl;
    private $error;

    public function __construct($botToken)
    {
        $this->apiUrl = "https://api.telegram.org/bot$botToken/";
    }

    public function sendMessage($chatId, $text)
    {
        $this->apiRequest("sendMessage", array(
            'chat_id' => $chatId,
            "text" => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ));
    }

    public function editMessage($chatId, $msgId, $text)
    {
        $this->apiRequest("editFMessageText", array(
                'chat_id' => $chatId,
                'message_id' => $msgId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ));
    }

    public function sendMessageKbrd($chatId, $text, $markup)
    {
        $this->apiRequest("sendMessage", array(
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => $markup,
                'parse_mode' => 'HTML',
            ));
    }

    public function editMessageKbrd($chatId, $msgId, $text, $markup)
    {
        $this->apiRequest("editMessageText", array(
                'chat_id' => $chatId,
                'message_id' => $msgId,
                'text' => $text,
                'reply_markup' => $markup,
                'parse_mode' => 'HTML'
            ));
    }

    public function answerClb($clbId, $text, ?bool $alert = null)
    {
        $this->apiRequest("answerCallbackQuery", array(
                'callback_query_id' => $clbId,
                'text' => $text,
                'show_alert' => $alert
            ));
    }

    public function hookInfo()
    {
        return $this->apiRequest('getWebhookInfo', array());
    }

    public function hookSet($webhookUrl)
    {
        return $this->apiRequest('setWebhook', array('url' => $webhookUrl));
    }

    private function apiRequest($method, $parameters = [])
    {
        if (!is_string($method)) {
            $this->error = "Method name must be a string\n";
            return false;
        }
        if (!is_array($parameters)) {
            $this->error = "Parameters must be an array\n";
            return false;
        }
        /*        foreach ($parameters as $key => &$val) {
                    // encoding to JSON array parameters, for example reply_markup
                    if (!is_numeric($val) && !is_string($val)) {
                        $val = json_encode($val);
                    }
                }*/
        $url = $this->apiUrl . $method . '?' . http_build_query($parameters);
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_STDERR, fopen('php://stderr', 'w'));
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        return $this->execCurlRequest($handle);
    }

    private function execCurlRequest($handle)
    {
        $response = curl_exec($handle);
        $error = curl_error($handle);
        if ($error) {
            $this->error = "**3.1**.CURL Error: $error";
        }
        if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            $this->error = "Curl returned error $errno: $error\n";
            curl_close($handle);
            return false;
        }
        $httpCode = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);
        switch (true) {
            case $httpCode >= 500:
                sleep(10);
                return false;
                break;
            case ($httpCode == 401):
                $this->error = 'Invalid access token provided';
                return false;
                break;
            case ($httpCode != 200):
                $response = json_decode($response, true);
                $this->error = "Request has failed with error {$response['error_code']}: {$response['description']}\n";
                break;
            default:
                $response = json_decode($response, true);
                if (isset($response['description'])) {
                    $this->error = "Request was successfull: {$response['description']}\n";
                }
                $response = $response['result'];
        }
        return $response;
    }
}
