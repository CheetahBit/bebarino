<?php

namespace App\Http\Controllers;

use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use stdClass;

class BotController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $update = json_decode($request->getContent());
            //exec('echo "" > ' . storage_path('logs/laravel.log'));
            Log::alert(json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if (isset($update->message)) $this->messageHandler($update->message);
            elseif (isset($update->callback_query)) $this->callbackHandler($update->callback_query);
            elseif (isset($update->inline_query)) $this->inlineHandler($update->inline_query);
        } catch (ErrorException $th) {
            Log::alert($th->getTraceAsString());
        }
        return response('ok');
    }


    public function messageHandler($message)
    {
        $config = (object) config('telegram');
        $message->cache = json_decode(Cache::store('database')->get($message->from->id, '{}'));

        $action = new stdClass;
        if (isset($message->text)) {
            $text = $message->text;
            $text = array_search($message->text, (array) $config->keywords) ?: $text;
            if (array_key_exists($text, (array) $config->actions))
                $action = $config->actions->{$text};
        }
        if (!isset($action)) $action = $message->cache->action;


        $class = new ("App\Telegram\\". $action->class . "Bot")();
        $class->{$action->method}($message);
    }

    public function callbackHandler($callback)
    {
        $callback->cache = json_decode(Cache::store('database')->get($callback->from->id, '{}'));
        $data = explode('.', $callback->data);
        $class = new ReflectionClass($data[0] . "Bot");
        $class->{$data[1]}($callback);
    }

    public function inlineHandler($inline)
    {
        if ($inline->chat_type = "sender") {
            $class = new ReflectionClass("InlineBot");
            $class->{'handle'}($inline);
        }
    }

    public function download($folder, $name)
    {
        return Storage::download($folder . '/' . $name, $name . '.jpg');
    }
}
