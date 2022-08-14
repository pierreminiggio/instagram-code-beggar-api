<?php

namespace App;

use Exception;

class App
{

    public function __construct(
        private string $token,
        private string $bot,
        private string $channelId
    )
    {
    }

    public function run(
        string $path,
        ?string $queryParameters,
        ?string $authHeader
    ): void
    {
        if ($path === '/') {
            http_response_code(404);

            return;
        }

        if (! $authHeader || $authHeader !== 'Bearer ' . $this->token) {
            http_response_code(401);

            return;
        }
      
        $username = substr($path, 1);

        $message = 'Gimme code for ' . $username . '.';

        $bot = $this->bot;
        $chatId = $this->channelId;

        $sendMessageCurl = curl_init();
        curl_setopt_array($sendMessageCurl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/sendMessage?chat_id=' . $chatId . '&text=' . $message
        ]);
        $sendMessageCurlResponse = curl_exec($sendMessageCurl);
        $httpCode = curl_getinfo($sendMessageCurl)['http_code'];
        curl_close($sendMessageCurl);

        if ($httpCode !== 200) {
            throw new Exception('Send message request failed with code ' . $httpCode . ' : ' . $sendMessageCurlResponse);
        }

        if ($sendMessageCurlResponse === false) {
            throw new Exception('No body for send message request');
        }

        $sendMessageCurlJsonResponse = json_decode($sendMessageCurlResponse, true);

        if (! $sendMessageCurlJsonResponse) {
            throw new Exception('Bad JSON for send message request : ' . $sendMessageCurlResponse);
        }

        if (empty($sendMessageCurlJsonResponse['ok'])) {
            throw new Exception('Send message request not ok : ' . $sendMessageCurlResponse);
        }

        if (! isset($sendMessageCurlJsonResponse['result'])) {
            throw new Exception('Send message request missing result key : ' . $sendMessageCurlResponse);
        }

        $fetchedMessage = $sendMessageCurlJsonResponse['result'];

        if (! isset($fetchedMessage['message_id'])) {
            throw new Exception('Send message request missing result->message_id key : ' . $sendMessageCurlResponse);
        }

        $messageId = $fetchedMessage['message_id'];

        if (! $messageId) {
            throw new Exception('Send message request has empty result->message_id value : ' . $sendMessageCurlResponse);
        }

        var_dump($messageId);
    }
}
