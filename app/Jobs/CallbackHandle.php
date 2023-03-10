<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CallbackHandle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $callback)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = explode('.', $this->callback->data);
        $this->callback->data = $data[2] ?? '';

        $this->callback->type = "callback";
        $class = new ("App\Telegram\\" . $data[0] . "Bot")($this->callback);
        $class->{$data[1]}();
    }

    public function failed(Throwable $exception)
    {
        Log::error($exception->getTraceAsString());
    }
}
