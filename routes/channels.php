<?php

use App\Broadcasting\RequestChatChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('request-chat.{serviceRequest}', RequestChatChannel::class);
