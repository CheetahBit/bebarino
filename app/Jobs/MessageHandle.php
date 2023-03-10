<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use stdClass;
use Throwable;

class MessageHandle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $config = config('telegram');
        $action = new stdClass;

        if (isset($this->message->text)) {
            $text = $this->message->text;
            $text = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $text);
            if (str_contains($text, '#P'))
                $text = "requestPackage";
            else if (str_contains($text, '#T'))
                $text = "requestTrip";
            $text = array_search($this->message->text, (array) $config->keywords) ?: $text;
            if (array_key_exists($text, (array) $config->actions))
                $action = $config->actions->{$text};
        }

        if (!isset($action->class)) {
            $userId = $this->message->from->id;
            $cache  = json_decode(Cache::store('database')->get($userId, '{}'));
            $action = $config->actions->{$cache->action};
        }

        $this->message->type = "message";
        $class = new ("App\Telegram\\" . $action->class . "Bot")($this->message);
        $class->{$action->method}();
    }


    public function failed(Throwable $exception)
    {
        Log::error('Error '.dd($exception));
    }
}
