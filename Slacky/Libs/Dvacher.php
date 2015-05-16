<?php namespace Slacky\Libs;

use GuzzleHttp\Client;

class Dvacher {

    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_url' => 'https://2ch.hk']);
    }

    public function getPosts($threadId)
    {
        $result = json_decode($this->client->get("/wrk/res/{$threadId}.json")->getBody());

        if ($result) {
            return $result->threads[0]->posts;
        }

        return [];
    }

}