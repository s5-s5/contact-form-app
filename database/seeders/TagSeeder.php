<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * タグの候補を固定で5件登録する
     */
    public function run(): void
    {
        $names = [
            '質問',
            '要望',
            '不具合報告',
            'ご意見',
            'その他',
        ];

        foreach ($names as $name) {
            Tag::query()->create(['name' => $name]);
        }
    }
}
