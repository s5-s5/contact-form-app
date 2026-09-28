<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * タグのリレーション
 */
class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_contacts_through_pivot_table(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(2)->create();

        $tag->contacts()->attach($contacts->pluck('id'));

        $this->assertCount(2, $tag->contacts);
        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }
}
