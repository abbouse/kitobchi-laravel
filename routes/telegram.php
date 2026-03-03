<?php

use SergiX44\Nutgram\Nutgram;
use App\Telegram\Commands\StartCommand;
use App\Telegram\Commands\PurchaseCommand;
use App\Telegram\Commands\GetContactCommand;
use App\Telegram\Commands\GetLocationCommand;
use App\Telegram\Commands\CashbackCommand;
use App\Telegram\Commands\MyPurchasesCommand;
use SergiX44\Nutgram\Telegram\Properties\MessageType;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/

$bot->onCommand('start', StartCommand::class);
$bot->onText('📦 Xaridlarim', MyPurchasesCommand::class);
$bot->onText('💸 Keshbek', CashbackCommand::class);
$bot->onMessageType(MessageType::WEB_APP_DATA, PurchaseCommand::class);
$bot->onMessageType(MessageType::CONTACT, GetContactCommand::class);
$bot->onMessageType(MessageType::LOCATION, GetLocationCommand::class);
