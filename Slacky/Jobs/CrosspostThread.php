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
    protected $slacker;

    public function __construct()
    {
        $this->general_ch = new Slacker(getenv('SLACK_GENERAL'));
        $this->thread_ch  = new Slacker(getenv('SLACK_THREAD'));
        $this->dvacher    = new Dvacher();
    }

    public function run()
    {
        $topic = $this->general_ch->info()->channel->topic->value;
        preg_match('/([0-9]{4,})/', $topic, $matches);
        $thread_id = $matches[0];

        $last_crossposted = $this->last_crossposted();
        $posts = $this->dvacher->getPosts($thread_id);

        $cnt = 0;
        foreach ($posts as $post) {
            if ($post->num <= $last_crossposted) {
                continue;
            }

            $message = $this->getMessage($post);
            $this->thread_ch->postMessage($message);
            $last_crossposted = $this->last_crossposted($post->num);

            $cnt++;
        }

        return "Crossposted $cnt new post(s)\n";
    }

    protected function last_crossposted($id = null)
    {
        $filename = __DIR__ . '/../../storage';
        if ($id) {
            file_put_contents($filename, (int) $id);
        } else {
            $id = (int) file_get_contents($filename);
        }

        return $id;
    }

    protected function getMessage($post)
    {
        $message = "*{$post->num}*\n";
        if (isset($post->files)) {
            array_map(function($file) use (&$message) {
                $message .= "https://2ch.hk/wrk/{$file->path} \n";
            }, $post->files);
        }

        $message .= html_entity_decode(
            strip_tags(
                str_replace(["<br>", ">>"], ["\n", ""], $post->comment)
            )
        );

        return $message;
    }
}