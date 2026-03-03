<?php

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;
use SergiX44\Nutgram\Nutgram;
use Exception;
use App\Models\Sold;
use App\Models\User;
use App\Models\Books;
use Illuminate\Support\Str;

class GetLocationCommand extends Command
{
    protected string $command = 'location';
    protected ?string $description = 'Foydalanuvchi lokatsiyasini qabul qilish';

    public function handle(Nutgram $bot): void
    {
        $userId = $bot->userId();
        $user = User::where('telegram_id', $userId)->first();

        if (!$user) {
            $bot->sendMessage("❌ Foydalanuvchi topilmadi! Iltimos, avval ro‘yxatdan o‘ting.");
            return;
        }

        $groupChatId = -1002441559767;

        if ($bot->message()->location) {
            $purchase = Sold::where('user_id', $user->id)
                ->where('status', 'A')
                ->whereIn('paymentStatus', ['0', '1'])
                ->where('telegram_message_id', null)
                ->latest()
                ->first();

            if (!$purchase) {
                $bot->sendMessage("❌ Sizda faol buyurtma mavjud emas.");
                return;
            }
            $message = "🛒 *Buyurtma qabul qilindi*\n";
            $message .= "🆔 ID: `".$purchase->id."`\n\n";
            foreach ($purchase->items as $item) {
                $message .= "📖 *" . $item['name'] . "*\n";
                $message .= "✍️ " . $item['author'] . "\n";
                $message .= "💰 Narxi: " . number_format($item['item_price'], 0, ',', ' ') . " so‘m\n";
                $message .= "📦 Miqdor: " . $item['count_item'] . "\n";
                $message .= "🎁 Sovg'a: " . $item['gift_name'] . "\n";
                $message .= "🛍️ Jami: " . number_format($item['quantity_price'], 0, ',', ' ') . " so‘m\n\n";
            }
            $message .= "---------------------\n";
            $message .= "👤 Qabul qiluvchi: `" . $purchase->address[0]['contact_name'] . "`\n";
            $message .= "📞 Telefon: `" . $purchase->address[0]['phone_number'] . "`\n";
            $message .= "---------------------\n";
            $message .= "🚚 Yetkazib berish: " . number_format($purchase->deliveryPrice, 0, ',', ' ') . " so‘m\n";
            $message .= "   Yetkazish usuli: " . $purchase->deliveryType . " orqali\n";
            $message .= "💳 Umumiy summa: *" . number_format($purchase->amount, 0, ',', ' ') . " so‘m*";
            $purchase->paymentStatus == 0 ? $message .= "   To'lov turi: Yetkazilganida to'lanadi" : null;

            $purchase->paymentStatus == 0 ? $bot->sendMessage(
                text: $message,
                parse_mode: 'markdown',
            ) : $bot->sendMessage(
                text: $message,
                parse_mode: 'markdown',
                reply_markup: InlineKeyboardMarkup::make()
                    ->addRow(
                        InlineKeyboardButton::make('💳 To‘lov qilish', url: 'https://bilim24.uz/payment/' . $purchase->id)
                    )
            );
            
            
            
            $forwardedMessage = $bot->forwardMessage($groupChatId, $bot->chatId(), $bot->messageId());
            $messageGroup = $purchase->paymentStatus == 1 ? "💸 To'lov: *❌ Qilinmadi*\n" : "💸 To'lov: *⚠️Yetkazilganida qilinadi*\n";
            $messageGroup .= "🆔 ID: `".$purchase->id."`\n\n";
            foreach ($purchase->items as $item) { 
                $messageGroup .= "📖 *" . $item['name'] . "*\n";
                $messageGroup .= "✍️ " . $item['author'] . "\n";
                $messageGroup .= "💰 Narxi: " . number_format($item['item_price'], 0, ',', ' ') . " so‘m\n";
                $messageGroup .= "📦 Miqdor: " . $item['count_item'] . "\n";
                $messageGroup .= "🎁 Sovg'a: " . $item['gift_name'] . "\n";
                $messageGroup .= "🛍️ Jami: " . number_format($item['quantity_price'], 0, ',', ' ') . " so‘m\n\n";
            }
            
            $messageGroup .= "🚚 Yetkazib berish: " . number_format($purchase->deliveryPrice, 0, ',', ' ') . " so‘m\n";
            $message .= "   Yetkazish usuli: " . $purchase->deliveryType . " orqali\n";
            $messageGroup .= "💳 Umumiy summa: *" . number_format($purchase->amount, 0, ',', ' ') . " so‘m*";
            
            $groupMessage = $bot->sendMessage(
                chat_id: $groupChatId,
                text: $messageGroup,
                parse_mode: 'markdown',
                reply_to_message_id: $forwardedMessage->message_id
            );

            $purchase->telegram_message_id = $groupMessage->message_id;
            $purchase->save();


            $mess = $purchase->paymentStatus == 1 ? "⏱️ {$purchase->id}-sonli buyurtmangizga 10 daqiqa ichida to'lov qilmasangiz avtomatik tarzda bekor qilinadi." : "Buyurtma qabul qilindi, siz bilan operatorlar bog'lanishadi.";
            $bot->sendMessage(
                text: $mess,
                reply_markup: ReplyKeyboardMarkup::make(
                    resize_keyboard: true
                )
                ->addRow(
                    KeyboardButton::make(
                        text: '🛒 Xaridlarni boshlash',
                        web_app: new WebAppInfo(
                            url: 'https://bookwormappbot.vercel.app/?phone_number=' . $user->phone_number . '&token=' . $user->remember_token
                        )
                    )
                )
                ->addRow(
                    KeyboardButton::make(text: '📦 Xaridlarim'),
                    KeyboardButton::make(text: '💸 Keshbek')
                )
            );

        } else {
            $bot->sendMessage("⚠️ Iltimos, lokatsiyani yuboring!", reply_markup: ReplyKeyboardMarkup::make(resize_keyboard: true)
                ->addRow(KeyboardButton::make("📍 Lokatsiya yuborish", request_location: true))
            );
        }
    }
}