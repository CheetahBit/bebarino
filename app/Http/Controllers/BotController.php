<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Telegram\APIBot;
use App\Telegram\InlineBot;
use Carbon\Carbon;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            (new APIBot)->chat(130912163)->sendMessage()->text(plain: $th->getLine() . '  ' . $th->getMessage())->exec();
            Log::alert($th->getTraceAsString());
        }
        return response('ok', 200);
    }


    public function messageHandler($message)
    {
        $config = (object) config('telegram');
        $message->cache = json_decode(Cache::store('database')->get($message->from->id, '{}'));
        //Log::alert(json_encode($message->cache));
        $action = new stdClass;
        if (isset($message->text)) {
            $text = $message->text;
            $text = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $text);
            if (str_contains($text, 'package-')) $text = "requestPackage";
            else if (str_contains($text, 'trip-')) $text = "requestTrip";
            $text = array_search($message->text, (array) $config->keywords) ?: $text;
            if (array_key_exists($text, (array) $config->actions))
                $action = $config->actions->{$text};
        }
        if (!isset($action->class)) $action = $message->cache->action;

        $class = new ("App\Telegram\\" . $action->class . "Bot")();
        $class->{$action->method}($message);
    }

    public function callbackHandler($callback)
    {
        $callback->cache = json_decode(Cache::store('database')->get($callback->from->id, '{}'));
        $data = explode('.', $callback->data);
        $callback->data = $data[2] ?? '';

        $class = new ("App\Telegram\\" . $data[0] . "Bot")();
        $class->{$data[1]}($callback);
    }

    public function inlineHandler($inline)
    {
        if ($inline->chat_type = "sender") {
            $inline->cache = json_decode(Cache::store('database')->get($inline->from->id, '{}'));
            $inlineBot = new InlineBot();
            $inlineBot->handle($inline);
        }
    }

    public function download($folder, $name)
    {
        return Storage::download($folder . '/' . $name, $name . '.jpg');
    }

    public function reset()
    {
        $token = config('telegram')->token;
        Http::get('https://api.telegram.org/bot' . $token . '/deleteWebhook');
        $response = Http::get('https://api.telegram.org/bot' . $token . '/getUpdates');
        try {
            $offset = end(json_decode($response)->result)->update_id + 1;
            Http::get('https://api.telegram.org/bot' . $token . '/getUpdates?offset=' . $offset);
        } catch (\Throwable $th) {
            //throw $th;
        }

        Http::get('https://api.telegram.org/bot' . $token . '/setwebhook?url=https://bot.cheetahbit.org/api/bot');

        $trips = Trip::where('messageId', '<>', null)->where('date', '>=', Carbon::today()->format('Y/m/d'))
            ->groupBy('date')->orderBy('date', 'asc');

    
        return response(json_encode($trips->get(), JSON_PRETTY_PRINT));
        // return response(Carbon::now()->format('Y/m/d'));
    }
}
