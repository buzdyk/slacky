<?php namespace Slacky\Jobs;

use Slacky\Libs\Slacker;
use Slacky\Libs\Dvacher;

class CrosspostThread implements Runnable {

    /**
     * @var Dvacher
     */
    protected $dvacher;

    /**
     * @var Slacker
     */
    protected $generalCh;

    /**
     * @var Slacker
     */
    protected $threadCh;

    public function __construct()
    {
        $this->generalCh = new Slacker(getenv('SLACK_GENERAL'));
        $this->threadCh  = new Slacker(getenv('SLACK_THREAD'));
        $this->dvacher    = new Dvacher();
    }

    public function run()
    {
        $threadId = $this->threadId();
        $lastCrossposted = $this->lastCrossposted();
        $posts = $this->dvacher->getPosts($threadId);

        $cnt = 0;
        foreach ($posts as $post) {
            if ($post->num <= $lastCrossposted) {
                continue;
            }

            $message = $this->getMessage($post);
            try {
                $this->threadCh->postMessage($message, $post->num);
            } catch (\Exception $e) {
                /** @todo find a way to send large messages */
            }

            $cnt++;
        }

        return "Crossposted $cnt new post(s)\n";
    }

    protected function lastCrossposted()
    {
        $id = null;
        $messages = $this->threadCh->messages()->messages;

        foreach ($messages as $message) {
            preg_match('/^([0-9]{5,7})$/', $message->username, $matches);
            if ($matches) {
                $id = $matches[0];
                break;
            }
        }

        if (!$id) {
            throw new \Exception('Can\'t find latest post ID');
        }

        return $id;
    }

    protected function getMessage($post)
    {
        $message = "";
        if (isset($post->files)) {
            foreach ($post->files as $file) {
                $message .= "https://2ch.hk/wrk/{$file->path} \n";
            }
        }

        $message .= html_entity_decode(
            strip_tags(
                str_replace(["<br>", ">>"], ["\n", ""], $post->comment)
            )
        );

        return $message;
    }

    protected function threadId()
    {
        $topic = $this->generalCh->info()->channel->topic->value;
        preg_match('/([0-9]{4,})/', $topic, $matches);

        if (!$matches) {
            throw new \Exception('Can\'t find thread ID. Please add thread url to the general channel topic.');
        }

        return $matches[0];
    }
}