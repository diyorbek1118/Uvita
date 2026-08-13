<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Infrastructure\Persistence\Models\ConversationModel;
use Modules\Chat\Infrastructure\Persistence\Models\MessageModel;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\User\Infrastructure\Persistence\Models\User;

/**
 * Demo suhbatlar va xabarlar.
 */
class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $listings = ListingModel::where('status', 'active')->with('seller')->get();
        $buyers   = User::pluck('id')->all();

        if ($listings->isEmpty() || empty($buyers)) {
            return;
        }

        $created = 0;

        foreach ($listings->take(5) as $listing) {
            $buyer = null;
            foreach ($buyers as $candidate) {
                if ($candidate !== $listing->seller_id) {
                    $buyer = $candidate;
                    break;
                }
            }
            if ($buyer === null) {
                continue;
            }

            $conversation = ConversationModel::create([
                'listing_id'      => $listing->id,
                'buyer_id'        => $buyer,
                'seller_id'       => $listing->seller_id,
                'last_message_at' => now()->subMinutes(rand(5, 200)),
            ]);

            $messages = [
                ['sender' => $buyer, 'body' => "Assalomu alaykum! {$listing->title} qolganmi?"],
                ['sender' => $listing->seller_id, 'body' => 'Vaalaykum assalom! Ha, bor. Bugun uzilgan, yangi.'],
                ['sender' => $buyer, 'body' => 'Narxini kelishsak bo\'ladimi? Ko\'proq olaman.'],
            ];

            foreach ($messages as $i => $msg) {
                MessageModel::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => $msg['sender'],
                    'body'            => $msg['body'],
                    'read_at'         => $i < 2 ? now() : null,
                    'created_at'      => now()->subMinutes(30 - $i * 10),
                ]);
            }

            $created++;
        }

        $this->command->info("✅ Suhbatlar: {$created} ta.");
    }
}
