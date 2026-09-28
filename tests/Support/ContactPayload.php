<?php

namespace Tests\Support;

use App\Models\Category;
use App\Models\Tag;

/**
 * お問い合わせの正しい入力値を作る（テスト用）
 */
trait ContactPayload
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function contactPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '千駄ヶ谷マンション101',
            'category_id' => Category::factory()->create()->id,
            'detail' => '商品の配送日について',
            'tag_ids' => Tag::factory()->count(2)->create()->pluck('id')->all(),
        ], $overrides);
    }
}
