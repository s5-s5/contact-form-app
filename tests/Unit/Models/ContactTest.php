<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * お問い合わせのリレーションと表示用の値
 */
class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->for($category)->create();

        $this->assertTrue($contact->category->is($category));
    }

    public function test_contact_can_sync_multiple_tags(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $contact->tags()->sync($tags->pluck('id'));
        $this->assertCount(3, $contact->fresh()->tags);

        $contact->tags()->sync([$tags->first()->id]);
        $this->assertSame([$tags->first()->id], $contact->fresh()->tags->pluck('id')->all());
    }

    public function test_gender_label_and_full_name(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 2,
        ]);

        $this->assertSame('女性', $contact->gender_label);
        $this->assertSame('山田 太郎', $contact->full_name);
    }
}
