<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use stdClass;

class APIBot
{

    public $data;
    public $keys;
    public $buttons;
    public $media;
    public $config;

    public function __construct()
    {
        $this->data = new stdClass;
        $this->config = config('telegram');
    }

    public function getCache($id)
    {
        return json_decode(Cache::store('database')->get($id, '{}'));
    }

    public function deleteCache($id)
    {
        return Cache::store('database')->delete($id);
    }

    public function setCache($id, $data)
    {
        Cache::store('database')->put($id, json_encode($data));
    }

    public function putCache($id, $key, $value)
    {
        $cache = $this->getCache($id);
        $cache->{$key} = $value;
        $this->setCache($id, $cache);
    }

    public function chat($id)
    {
        $this->data = new stdClass;
        $this->data->chat_id = $id;
        return $this;
    }

    public function sendMessage()
    {
        $this->data->method = "sendMessage";
        $this->data->parse_mode = "HTML";
        return $this;
    }

    public function updateMessage()
    {
        $this->data->method = 'editMessageText';
        return $this;
    }

    public function updateButton()
    {
        $this->data->method = 'editMessageReplyMarkup';
        return $this;
    }

    public function deleteMessage()
    {
        $this->data->method = 'editMessageText';
        return $this;
    }

    public function messageId($message_id)
    {
        $this->data->message_id = $message_id;
        return $this;
    }

    public function text($key = null, $args = [], $plain = '')
    {
        $text = '';
        if (isset($key)) {
            $text = $this->config->messages->{$key};
            preg_match_all('/(?<=:)[a-zA-Z0-9_]*/', $text, $keywords);
            $keywords = array_filter($keywords[0], fn ($item) => strlen($item) > 0);
            foreach ($keywords as $value) $text = str_replace(':' . $value, ($args[$value] ?? ''), $text);
        }
        $this->data->text = $text . $plain;
        return $this;
    }

    public function removeKeyboard()
    {
        $temp = new stdClass;
        $temp->remove_keyboard = true;
        $this->data->reply_markup = $temp;
    }

    public function keyboard()
    {
        if (!isset($this->data->reply_markup->keyboard)) {
            $temp = new stdClass;
            $temp->keyboard = [];
            $temp->one_time_keyboard = true;
            $temp->resize_keyboard = true;
            $this->data->reply_markup = $temp;
        }
        return $this;
    }

    public function rowKeys($callback)
    {
        $this->keys = [];
        $callback($this);
        if (count($this->keys) > 0) $this->data->reply_markup->keyboard[] = $this->keys;
        return $this;
    }

    public function key($title, $key = null, $value = null)
    {
        $keywords = $this->config->keywords;
        $text = $keywords->{$title} ?: $title;
        $temp = new stdClass;
        $temp->text = $text;
        if (isset($key)) $temp->{$key} = $value;
        $this->keys[] = $temp;
    }

    public function inlineKeyboard()
    {
        if (!isset($this->data->reply_markup->inline_keyboard)) {
            $temp = new stdClass;
            $temp->inline_keyboard = [];
            $this->data->reply_markup = $temp;
        }
        return $this;
    }

    public function rowButtons($callback)
    {
        $this->buttons = [];
        $callback($this);
        if (count($this->buttons) > 0) $this->data->reply_markup->inline_keyboard[] = $this->buttons;
        return $this;
    }

    public function button($title, $type, $value)
    {
        $type = str_replace(["data", "query"], ["callback_data", "switch_inline_query_current_chat"], $type);
        $button = new stdClass;
        $button->text = $this->config->keywords->{$title} ?: $title;
        $button->{$type} = $value;
        $this->buttons[] = $button;
        return $this;
    }

    public function showAlert($id, $popup = false)
    {
        $temp = new stdClass;
        $temp->method = 'answerCallbackQuery';
        $temp->callback_query_id = $id;
        $temp->show_alert = $popup;
        $this->data = $temp;
        return $this;
    }

    public function sendMediaGroup()
    {
        $this->data = 'sendMediaGroup';
        return $this;
    }

    public function media($callback)
    {
        $this->media = [];
        $callback($this);
        $this->data->media[] = $this->keys;
        return $this;
    }

    public function photo($path)
    {
        $temp = new stdClass;
        $temp->type = 'photo';
        $temp->media = Request::getHttpHost() . "/api/files/" . $path;
        $this->media[] = $temp;
        return $this;
    }

    public function reply($message_id)
    {
        $this->data->reply_to_message_id = $message_id;
        return $this;
    }

    public function download($file, $folder)
    {
        $token = config('telegram')->token;

        $this->data->method = 'getFile';
        $this->data->file_id = $file->file_id;

        $result = $this->exec();
        $file_path = $result->file_path;

        $response = Http::connectTimeout(1000)->timeout(1000)
            // ->withOptions(['proxy' => '192.168.48.164:10809'])
            ->get('https://api.telegram.org/file/bot' . $token . '/' . $file_path);

        Storage::put($folder . "/" . $file->file_unique_id, $response);
    }

    function exec()
    {
        $token = config('telegram')->token;
        Log::info(json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        Log::info(json_encode(Cache::store('database')->get($this->data->chat_id ?? ''), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $response = Http::connectTimeout(10)
            // ->withOptions(['proxy' => '192.168.48.164:10809'])
            ->withBody(json_encode($this->data), 'application/json')
            ->post('https://api.telegram.org/bot' . $token . '/');
        Log::info($response);
        //$this->result = json_decode($response, true)['result'];
        return json_decode($response)->result;
    }
}
