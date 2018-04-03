<?php
declare(strict_types=1);

namespace Island;

use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;

class Kaamelott
{
    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return StreamInterface
     */
    public function getQuote(): StreamInterface
    {
        $client = new Client();
        $res = $client->request('GET', 'https://kaamelott.chaudie.re/api/random');

        return $res->getBody();
    }
}
