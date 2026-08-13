<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Controllers;

use App\Shared\Services\Upload\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\Rating\Infrastructure\Persistence\Models\RatingModel;
use Modules\User\Infrastructure\Persistence\Models\User;
use Modules\User\Presentation\Requests\UpdateProfileRequest;
use Modules\User\Presentation\Resources\UserResource;

final class UserController extends Controller
{
    public function profile(): JsonResponse
    {
        $user = User::withAvg('ratingsReceived as ratings_avg_stars', 'stars')
            ->withCount(['ratingsReceived as ratings_count', 'sales as deals_count'])
            ->findOrFail(auth()->id());

        return UserResource::make($user)->response();
    }

    /** Boshqa foydalanuvchining ochiq profili (sotuvchi/xaridor) */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = User::withAvg('ratingsReceived as ratings_avg_stars', 'stars')
            ->withCount(['ratingsReceived as ratings_count', 'sales as deals_count'])
            ->findOrFail($id);

        $data = UserResource::make($user)->resolve();

        // Faol e'lonlari
        $listings = ListingModel::with('category')
            ->where('seller_id', $id)
            ->where('status', 'active')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($l) => [
                'id'       => $l->id,
                'title'    => $l->title,
                'price'    => $l->price,
                'quantity' => $l->quantity,
                'unit'     => $l->unit,
                'images'   => \App\Shared\Services\Upload\ImageUrlNormalizer::normalizeArray($l->images),
                'region'   => $l->region,
                'category' => $l->category?->name,
            ]);

        // Sharhlar
        $ratings = RatingModel::with('rater')
            ->where('rated_id', $id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'        => $r->id,
                'stars'     => $r->stars,
                'comment'   => $r->comment,
                'created_at'=> $r->created_at?->toISOString(),
                'rater'     => ['id' => $r->rater->id, 'name' => $r->rater->name],
            ]);

        return response()->json([
            'user'     => $data,
            'listings' => $listings,
            'ratings'  => $ratings,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Avatar fayl yuklangan bo'lsa — public diskka saqlaymiz.
        // Aniq `avatar: null` yuborilsa — mavjud avatarni o'chiramiz.
        // Aks holda mavjud avatar o'zgarishsiz qoladi.
        $avatar = $user->avatar;
        if ($request->hasFile('avatar')) {
            $avatar = app(ImageUploadService::class)->store($request->file('avatar'), 'avatars');
        } elseif ($request->has('avatar') && $request->input('avatar') === null) {
            $avatar = null;
        }

        // Faqat yuborilgan maydonlarni yangilaymiz — har bir bo'lim individual saqlanadi
        $data = [];
        foreach (['name', 'surname', 'region', 'district', 'address', 'lat', 'lng'] as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);
                $data[$field] = ($value === '' || $value === null) ? null : $value;
            }
        }
        $data['avatar'] = $avatar;

        $user->update($data);

        return UserResource::make($user)
            ->additional(['message' => 'Profil yangilandi'])
            ->response();
    }
}
