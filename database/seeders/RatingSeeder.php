<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Deal\Infrastructure\Persistence\Models\DealModel;
use Modules\Rating\Infrastructure\Persistence\Models\RatingModel;

/**
 * Yakunlangan bitimlar uchun demo baholashlar.
 */
class RatingSeeder extends Seeder
{
    public function run(): void
    {
        $completed = DealModel::where('status', 'completed')->get();

        if ($completed->isEmpty()) {
            $this->command->info('✅ Baholashlar: 0 ta (yakunlangan bitim yo\'q).');
            return;
        }

        $comments = [
            'Juda sifatli mahsulot, kelishilgan vaqtda yetkazib berdi. Rahmat!',
            'Mahsulot zo\'r chiqdi, narxi ham adolatli. Yana murojaat qilaman.',
            'Xushmuomala sotuvchi, mahsulot yangi. Tavsiya qilaman.',
            'Yaxshi bitim bo\'ldi, aloqa tez o\'rnatildi.',
        ];

        $created = 0;

        foreach ($completed as $deal) {
            // Sotuvchi xaridorni baholaydi, xaridor sotuvchini
            $pairs = [
                ['rater' => $deal->seller_id, 'rated' => $deal->buyer_id],
                ['rater' => $deal->buyer_id,  'rated' => $deal->seller_id],
            ];

            foreach ($pairs as $pair) {
                if (RatingModel::where('deal_id', $deal->id)->where('rater_id', $pair['rater'])->exists()) {
                    continue;
                }

                RatingModel::create([
                    'deal_id'  => $deal->id,
                    'rater_id' => $pair['rater'],
                    'rated_id' => $pair['rated'],
                    'stars'    => rand(4, 5),
                    'comment'  => $comments[array_rand($comments)],
                ]);
                $created++;
            }
        }

        $this->command->info("✅ Baholashlar: {$created} ta.");
    }
}
