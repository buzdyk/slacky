<?php namespace Slacky\Libs;

use GuzzleHttp\Client;

class Slacker {

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var String
     */
    protected $channel;

    public function __construct($channel_id)
    {
        $this->client  = new Client(['base_url' => 'https://slack.com']);
        $this->token   = getenv('SLACK_TOKEN');
        $this->channel = $channel_id;
    }

    public function postMessage($message, $username = '2ch Bot')
    {
        $params = $this->params([
            'text'      => $message,
            'username'  => $username,
            'as_user'   => 'false',
            'unfurl_links' => 'true',
            'unfurl_media' => 'true',
            'icon_url'     => 'https://2ch.hk/favicon.ico',
        ]);

        return $this->client->get('/api/chat.postMessage?' . $params);
    }

    public function messages()
    {
        $params = $this->params();
        $response = $this->client->get('/api/channels.history?' . $params);

        return json_decode($response->getBody());
    }

    public function info()
    {
        $params = $this->params();
        $response = $this->client->get('/api/channels.info?' . $params);
        $result = json_decode($response->getBody());

        return $result;
    }

    protected function params(array $additional = [])
    {
        $params = array_merge([
            'token' => $this->token,
            'channel' => $this->channel
        ], $additional);

        return http_build_query($params);
    }
}