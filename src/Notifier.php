<?php
declare(strict_types=1);

namespace Island;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Notifier
{
    /**
     * @param string $username Slack username to notify
     * @param string $url Slack url webhook
     */
    public function notify (string $username, string $url): void
    {
        $SLACK_URL = getenv('SLACK_URL');
        if (!$SLACK_URL) {
            return;
        }

        $data = [
            'text' => sprintf(
                '%s wants to run a CI build for %s',
                $username,
                $url
            ),
        ];

        $client = new Client();
        $request = new Request(
            'POST',
            $SLACK_URL,
            ['Content-type' => 'application/json'],
            json_encode($data)
        );

        $promise = $client->sendAsync($request, ['timeout' => 10]);
        $promise->wait(false);
    }
}
