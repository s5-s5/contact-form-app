<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * ダミーのお問い合わせを20件登録し、それぞれにタグを1〜3件ランダムに付ける
     */
    public function run(): void
    {
        $tagIds = Tag::query()->pluck('id');

        Contact::factory()
            ->count(20)
            ->create()
            ->each(function (Contact $contact) use ($tagIds) {
                $contact->tags()->attach($tagIds->random(random_int(1, 3))->all());
            });
    }
}
