<?php

namespace App\Chatbot\Admin;

interface RestorationSink
{
    public function record(array $record): void;
}
