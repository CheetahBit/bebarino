<?php

namespace App\Http\Controllers;

use App\Jobs\CallbackHandle;
use App\Jobs\InlineHandle;
use App\Jobs\MessageHandle;
use App\Telegram\APIBot;
use App\Telegram\InlineBot;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use stdClass;

class BotController extends Controller
{

    public function __invoke(Request $request)
    {
        try {
            $update = json_decode($request->getContent());
            Log::alert(json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if (isset($update->message))
                dispatch(new MessageHandle($update->message));
            elseif (isset($update->callback_query))
                dispatch(new CallbackHandle($update->callback_query));
            elseif (isset($update->inline_query))
                dispatch(new InlineHandle($update->inline_query));
        } catch (ErrorException $th) {
            (new APIBot)->chat(130912163)->sendMessage()->text(plain: $th->getLine() . '  ' . $th->getMessage())->exec();
            Log::alert($th->getTraceAsString());
        }
        return response('ok', 200);
    }


    public function download($folder, $name)
    {
        $request = request();
        return dd(request()->headers['headers']);
        Log::alert('Request For Download : ' . print_r($request->header()));
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
    
        // return config('telegram')->keywords;
    }
}
