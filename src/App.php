<?php

namespace App;

use DateTime;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class App
{

    public function __construct(
        private DatabaseFetcher $fetcher,
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
      
      
    }
}
