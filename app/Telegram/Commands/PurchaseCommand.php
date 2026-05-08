<?php

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Nutgram;
use Exception;
use App\Models\Sold;
use App\Models\User;
use App\Models\Books;
use Illuminate\Support\Str;

class PurchaseCommand extends Command
{
    protected string $command = 'web_app_data';
    protected ?string $description = 'Veb ilovadan ma’lumot olish';

    public function handle(Nutgram $bot): void
    {
        if (!isset($bot->message()->web_app_data)) {
            $bot->sendMessage("⚠️ Web ilova orqali hech qanday ma'lumot yuborilmagan.");
            return;
        }

        $data = $bot->message()->web_app_data->data;

        try {
            $decodedData = json_decode($data, true);

            if (!$decodedData) {
                throw new Exception("❌ JSON formatida xato bor yoki bo‘sh ma’lumot keldi.");
            }

            $userId = $bot->userId();
            $user = User::where('telegram_id', $userId)->first();

            if (!$user) {
                $bot->sendMessage("❌ Foydalanuvchi topilmadi! Iltimos, avval ro‘yxatdan o‘ting.");
                return;
            }

            $items = [];
            $totalSum = 0;

            foreach ($decodedData['products'] as $cartItem) {
                $book = Books::where('id', $cartItem['id'])->first();
                $itemTotal = $book['price'] * $cartItem['quantitiy'];
                $totalSum += $itemTotal;

                $items[] = [
                    "name" => $book['name'],
                    "author" => $book['author'],
                    "item_price" => $book['price'],
                    "quantity_price" => $itemTotal,
                    "item_id" => $book['id'],
                    "count_item" => $cartItem['quantitiy'],
                    "gift_name" => !empty($cartItem['gift']) ? $cartItem['gift']['name'] : "Tasodifiy sovg'a",
                    "gift_id" => !empty($cartItem['gift']) ? $cartItem['gift']['id'] : "1",
                ];
            }
            
            $locationData = [
                "region" => $decodedData['location']['selectedRegion'],
                "city" => $decodedData['location']['selectedDistrict'],
                "streetHome" => $decodedData['location']['address'],
                "contact_name" => $decodedData['location']['fullName'],
                "phone_number" => $decodedData['location']['phone']
            ];

            $deliveryPrice = ($totalSum >= 100000) ? 0 : 20000;
            $finalPrice = $totalSum + $deliveryPrice;

            $purchase = new Sold();
            $purchase->user_id = $user->id;
            $purchase->qr = Str::random(30);
            $purchase->items = $items;
            $purchase->amount = $finalPrice;
            $purchase->address = [$locationData];
            $purchase->deliveryType = $decodedData['location']['selectedRegion'] == "Toshkent shahri" ? "delivery" : "postal";
            $purchase->paymentStatus = $decodedData['location']['payment'] == 'naqd' ? '0' : '1';
            $purchase->deliveryPrice = $deliveryPrice;
            $purchase->save();

            $message = "🛒 Buyurtmangiz qabul qilindi!\n\n";
            $message .= "🆔 ID: `".$purchase->id."`\n";
            foreach ($items as $item) {
                $message .= "📖 *" . $item['name'] . "*\n";
                $message .= "✍️ " . $item['author'] . "\n";
                $message .= "💰 Narxi: " . number_format($item['item_price'], 0, ',', ' ') . " so‘m\n";
                $message .= "📦 Miqdor: " . $item['count_item'] . "\n";
                $message .= "🎁 Sovg'a: " . $item['gift_name'] . "\n";
                $message .= "🛍️ Jami: " . number_format($item['quantity_price'], 0, ',', ' ') . " so‘m\n\n";
            }

            $message .= "---------------------\n";
            $message .= "📍 *Yetkazish:* " . $purchase->deliveryType . " orqali\n";
            $message .= "🌍 Viloyat: `" . $locationData['region'] . "`\n";
            $message .= "🏙️ Shahar: `" . $locationData['city'] . "`\n";
            $message .= "🏠 Ko‘cha va uy: `" . $locationData['streetHome'] . "`\n";
            $message .= "👤 Qabul qiluvchi: `" . $locationData['contact_name'] . "`\n";
            $message .= "📞 Telefon: `" . $locationData['phone_number'] . "`\n";
            $message .= "--------------------\n";
            $message .= "🚚 Yetkazib berish: " . number_format($deliveryPrice, 0, ',', ' ') . " so‘m\n";
            $message .= "💳 Umumiy summa: *" . number_format($finalPrice, 0, ',', ' ') . " so‘m*";

            $inlineKeyboard = InlineKeyboardMarkup::make()
                ->addRow(
                    InlineKeyboardButton::make('💳 To\'lov qilish', url: 'https://bilim24.uz/payment/'.$purchase->id)
                );

            if ($locationData['region'] === 'Toshkent shahri') {
                $bot->sendMessage("📍 Iltimos, o'z joylashuvingizni yuboring!", reply_markup: ReplyKeyboardMarkup::make(resize_keyboard: true)
                    ->addRow(KeyboardButton::make("📍 Lokatsiya yuborish", request_location: true))
                );
                return;
            }

            $groupChatId = "-1002441559767"; 
            
            $messageGroup = "💸 To'lov: *❌ Qilinmadi*\n\n";
            $messageGroup .= "🆔 ID: `".$purchase->id."`\n";
            foreach ($items as $item) {
                $messageGroup .= "📖 *" . $item['name'] . "*\n";
                $messageGroup .= "✍️ " . $item['author'] . "\n";
                $messageGroup .= "💰 Narxi: " . number_format($item['item_price'], 0, ',', ' ') . " so‘m\n";
                $messageGroup .= "📦 Miqdor: " . $item['count_item'] . "\n";
                $messageGroup .= "🎁 Sovg'a: " . $item['gift_name'] . "\n";
                $messageGroup .= "🛍️ Jami: " . number_format($item['quantity_price'], 0, ',', ' ') . " so‘m\n\n";
            }

            // $messageGroup .= "---------------------\n";
            // $messageGroup .= "📍 *Yetkazish:* " . $purchase->deliveryType . " orqali\n";
            // $messageGroup .= "🌍 Viloyat: `" . $locationData['region'] . "`\n";
            // $messageGroup .= "🏙️ Shahar: `" . $locationData['city'] . "`\n";
            // $messageGroup .= "🏠 Ko‘cha va uy: `" . $locationData['streetHome'] . "`\n";
            // $messageGroup .= "👤 Qabul qiluvchi: `" . $locationData['contact_name'] . "`\n";
            // $messageGroup .= "📞 Telefon: `" . $locationData['phone_number'] . "`\n";
            // $messageGroup .= "--------------------\n";
            $messageGroup .= "🚚 Yetkazib berish: " . number_format($deliveryPrice, 0, ',', ' ') . " so‘m\n";
            $messageGroup .= "💳 Umumiy summa: *" . number_format($finalPrice, 0, ',', ' ') . " so‘m*";
            $groupMessageId = $bot->sendMessage(
                chat_id: $groupChatId,
                text: $messageGroup,
                parse_mode: 'markdown'
            )->message_id;
            $purchase->telegram_message_id = $groupMessageId;
            $purchase->save();
            $bot->sendMessage(
                text: $message,
                parse_mode: 'markdown',
                reply_markup: $inlineKeyboard
            );

        } catch (Exception $e) {
            $bot->sendMessage("❌ Xato yuz berdi: " . $e->getMessage());
        }
    }
}
