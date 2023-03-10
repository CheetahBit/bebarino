<?php

namespace App\Telegram;

class AccountBot extends ParentBot
{

    public function index()
    {
        $this->clear();
        
        $this->api->sendMessage()->text('accountInfo')->keyboard()->rowKeys(function ($m) {
            $m->key('contactInfo');
            $m->key('identityInfo');
        })->rowKeys(function ($m) {
            $m->key('bankInfo');
            $m->key('backward');
        })->exec();
    }

    public function show()
    {
        $this->clear();
        dd($this);
        if ($this->type == 'message')
            $this->api->updateButton()->messageId($this->messageId - 1)->exec();

        $key = $this->cache->key ?? array_search($this->data, (array) $this->config->keywords);
        $args = $this->user->account;

        $this->api->sendMessage()->text($key, $args)->inlineKeyboard()->rowButtons(function ($m) use ($key) {
            $m->button('edit', 'data', 'Account.edit.' . $key);
        })->exec();
    }

    public function edit()
    {
        $key = $this->data;
        $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($key) {
            $m->button('backward', 'data', 'Account.backward.' . $key);
        })->messageId($this->messageId)->exec();

        $this->putCache('key', $key);

        $flow = new FlowBot($this->update);
        $flow->start($this->data, 'update');
    }

    public function update()
    {
        $this->user->account()->update((array)$this->result->data);

        $this->api->sendMessage()->text('saveSuccessfully')->keyboard()->rowKeys(function ($m) {
            $m->key('contactInfo');
            $m->key('identityInfo');
        })->rowKeys(function ($m) {
            $m->key('bankInfo');
            $m->key('backward');
        })->exec();

        $this->show();
    }

    public function backward()
    {
        $this->api->updateButton()->messageId($this->messageId)->exec();

        $this->api->sendMessage()->text('cancelEdit')->keyboard()->rowKeys(function ($m) {
            $m->key('contactInfo');
            $m->key('identityInfo');
        })->rowKeys(function ($m) {
            $m->key('bankInfo');
            $m->key('backward');
        })->exec();

        $this->cache->key = $this->data;
    }
}
