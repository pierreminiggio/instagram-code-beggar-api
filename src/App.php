<?php

namespace App;

use DateTime;
use DateTimeImmutable;
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

        $dateBeforeSendingMessage = new DateTimeImmutable();

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

        // Wait for response

        $waitTimeSeconds = 600;
        set_time_limit($waitTimeSeconds + 60);

        for ($i = 0; $i <= $waitTimeSeconds; $i++) {
            sleep(1);

            $updatesCurl = curl_init();
            curl_setopt_array($updatesCurl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/getupdates?offset=-1'
            ]);

            $updatesCurlResponse = curl_exec($updatesCurl);
            $httpCode = curl_getinfo($updatesCurl)['http_code'];
            curl_close($updatesCurl);
            
            if ($httpCode !== 200) {
                throw new Exception('getUpdates request failed with code ' . $httpCode . ' : ' . $updatesCurlResponse);
            }

            if ($updatesCurlResponse === false) {
                throw new Exception('No body for getUpdates request');
            }
            
            $updatesCurlJsonResponse = json_decode($updatesCurlResponse, true);
            
            if (! $updatesCurlJsonResponse) {
                throw new Exception('Bad JSON for getUpdates request : ' . $updatesCurlResponse);
            }
            
            if (empty($updatesCurlJsonResponse['ok'])) {
                throw new Exception('getUpdates request not ok : ' . $updatesCurlResponse);
            }
                
            if (! isset($updatesCurlJsonResponse['result'])) {
                throw new Exception('getUpdates request missing result key : ' . $updatesCurlResponse);
            }
            
            $fetchedUpdates = $updatesCurlJsonResponse['result'];
            
            foreach ($fetchedUpdates as $fetchedUpdate) {
                if (
                    ! isset(
                        $fetchedUpdate['update_id'],
                        $fetchedUpdate['message']
                    )
                ) {
                    continue;
                }
                    
                $messageData = $fetchedUpdate['message'];

                if (! isset($messageData['date'])) {
                    return;
                }

                $messageDate = new DateTime();
                $messageDate->setTimestamp($messageData['date']);

                if ($messageDate < $dateBeforeSendingMessage) {
                    continue;
                }

                if (! isset($messageData['text'])) {
                    return;
                }

                $text = $messageData['text'];

                http_response_code(200);
                echo $text;
                
                return;
            }
        }
    }
}
