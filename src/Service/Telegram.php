<?php

declare(strict_types = 1);

namespace Deralsem\Tgbotapiwrapper\Service;

class Telegram
{
    /**
     * @var string
     */
    private $apiUrl;
    /**
     * @var string
     */
    private $requestResult;
    /**
     * @var string
     */
    private $error;

    public function __construct(string $botToken)
    {
        $this->apiUrl = "https://api.telegram.org/bot$botToken/";
    }

    public function sendMessage(int $chatId, string $text): ?string
    {
        return $this->apiRequest("sendMessage", array(
            'chat_id' => $chatId,
            "text" => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ));
    }

    public function editMessage(int $chatId, int $msgId, string $text): ?string
    {
        return $this->apiRequest("editFMessageText", array(
            'chat_id' => $chatId,
            'message_id' => $msgId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ));
    }

    public function sendMessageKbrd(int $chatId, string $text, array $markup): ?string
    {
        return $this->apiRequest("sendMessage", array(
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $markup,
            'parse_mode' => 'HTML',
        ));
    }

    public function editMessageKbrd(int $chatId, int $msgId, string $text, array $markup): ?string
    {
        return $this->apiRequest("editMessageText", array(
            'chat_id' => $chatId,
            'message_id' => $msgId,
            'text' => $text,
            'reply_markup' => $markup,
            'parse_mode' => 'HTML'
        ));
    }

    public function answerClb(int $clbId, array $text, ?bool $alert = null): ?string
    {
        return $this->apiRequest("answerCallbackQuery", array(
            'callback_query_id' => $clbId,
            'text' => $text,
            'show_alert' => $alert
        ));
    }

    public function hookInfo(): ?string
    {
        return $this->apiRequest('getWebhookInfo');
    }

    public function hookSet(string $webhookUrl): string
    {
        return $this->apiRequest('setWebhook', array('url' => $webhookUrl));
    }

    private function apiRequest(string $method, ?array $parameters = []): ?string
    {
        foreach ($parameters as $key => &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }

        $url = $this->apiUrl . $method . '?' . http_build_query($parameters);
        $handle = curl_init($url);
        if (is_resource($handle)) {
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_STDERR, fopen('php://stderr', 'w'));
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        }
        return $this->execCurlRequest($handle);
    }

    private function execCurlRequest($handle): ?string
    {
        /**
         * @var string|null
         */
        $curlResponse = curl_exec($handle);
        /**
         * @var string|null
         */
        $error = curl_error($handle);
        $errno = curl_errno($handle);
        if ($error !== '') {
            $this->error = "cURL returned error $errno: $error";
            curl_close($handle);
            return $this->error;
        }

            $responseDecode = json_decode($curlResponse, true);
            //$response = '';
            //$data = $responseDecode;
            //$responseDecode['ok'] && is_array($responseDecode['result'])? $data = $responseDecode['result'] : false ;
            $response = print_r($responseDecode, true);
            /*            foreach ( $data as $key => $val) {
                            if (is_array($val)) {
                                foreach ($val as $key1 => $val1) {
                                    (is_numeric($val1) && (int)$val1 == $val1 && $val1 > 999999999) ?
                                        $value = date('M/D H:i:s', $val1) :
                                        $value = $val1;
                                    $response .= "$key1 => $value" . PHP_EOL;
                                }
                            }
                            else {
                                (is_numeric($val) && (int)$val == $val && $val > 999999999) ?
                                    $value = date('M/D H:i:s', $val) :
                                    $value = $val;
                                $response .= "$key => $value" . PHP_EOL;
                            }
                        }*/
            $httpCode = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
            curl_close($handle);
            switch (true) {
                case $httpCode >= 500:
                    echo $this->error = "cURL request has failed with error $httpCode: $response\nsleeping for 10 seconds";
                    sleep(10);
                    break;
                case ($httpCode == 401):
                    echo $this->error = "Invalid access token provided $httpCode:\n$response";
                    break;
                case ($httpCode != 200):
                    echo $this->error = "cURL request has failed with error $httpCode:\n$response";
                    break;
                default:
                    $this->requestResult = "cURL request was successful:\n" . $response;
            }
        !isset($this->error)? $res = $this->requestResult : $res = null;
        return $res;
    }
}
