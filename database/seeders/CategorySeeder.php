<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * お問い合わせの種類を固定で5件登録する
     */
    public function run(): void
    {
        $contents = [
            '商品のお届けについて',
            '商品の交換について',
            '商品トラブル',
            'ショップへのお問い合わせ',
            'その他',
        ];

        foreach ($contents as $content) {
            Category::query()->create(['content' => $content]);
        }
    }
}
