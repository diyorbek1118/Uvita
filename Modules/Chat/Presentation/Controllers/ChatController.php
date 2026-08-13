<?php

declare(strict_types=1);

namespace Modules\Chat\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Chat\Infrastructure\Persistence\Models\ConversationModel;
use Modules\Chat\Infrastructure\Persistence\Models\MessageModel;
use Modules\Chat\Presentation\Resources\ConversationResource;
use Modules\Chat\Presentation\Resources\MessageResource;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;

final class ChatController extends Controller
{
    /** Mening suhbatlarim ro'yxati */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $conversations = ConversationModel::with(['listing', 'buyer', 'seller', 'messages'])
            ->where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        return ConversationResource::collection($conversations)->response();
    }

    /** Yangi suhbat ochish yoki mavjudini qaytarish */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
        ]);

        $listing = ListingModel::findOrFail((int) $validated['listing_id']);
        $buyerId = auth()->id();

        if ($listing->seller_id === $buyerId) {
            abort(422, "O'z e'loningiz bilan suhbat ocholmaysiz.");
        }

        $conversation = ConversationModel::firstOrCreate(
            [
                'listing_id' => $listing->id,
                'buyer_id'   => $buyerId,
                'seller_id'  => $listing->seller_id,
            ],
            ['last_message_at' => now()]
        );

        return ConversationResource::make($conversation->load(['listing', 'buyer', 'seller']))
            ->response()
            ->setStatusCode(201);
    }

    /** Bitta suhbat (boshqa foydalanuvchi, mahsulot, oxirgi xabar) */
    public function show(int $id): JsonResponse
    {
        $conversation = $this->findMine($id);

        return ConversationResource::make($conversation)->response();
    }

    /** Suhbatdagi xabarlar */
    public function messages(int $id): JsonResponse
    {
        $conversation = $this->findMine($id);

        // Kirgan foydalanuvchiga tegishli xabarlarni o'qilgan qilamiz
        MessageModel::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = MessageModel::with('sender')
            ->where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->paginate((int) request()->input('per_page', 50));

        return MessageResource::collection($messages)->response();
    }

    /** Xabar yuborish */
    public function sendMessage(int $id, Request $request): JsonResponse
    {
        $conversation = $this->findMine($id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = MessageModel::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'body'            => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return MessageResource::make($message->load('sender'))
            ->response()
            ->setStatusCode(201);
    }

    private function findMine(int $id): ConversationModel
    {
        $conversation = ConversationModel::with(['listing', 'buyer', 'seller', 'messages'])->find($id);

        if ($conversation === null) {
            abort(404, 'Suhbat topilmadi.');
        }

        if ($conversation->buyer_id !== auth()->id() && $conversation->seller_id !== auth()->id()) {
            abort(403, 'Bu suhbat sizga tegishli emas.');
        }

        return $conversation;
    }
}
