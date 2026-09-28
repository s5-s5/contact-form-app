<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContactPayload;
use Tests\TestCase;

/**
 * 公開 API（/api/v1/contacts）
 */
class ContactApiTest extends TestCase
{
    use ContactPayload;
    use RefreshDatabase;

    private const NOT_FOUND_JSON = ['error' => 'お問い合わせが見つかりませんでした。'];

    public function test_index_returns_contacts_with_pagination_meta(): void
    {
        Contact::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'category' => ['id', 'content'], 'first_name', 'last_name', 'gender', 'email',
                    'tel', 'address', 'building', 'detail', 'tags', 'created_at', 'updated_at',
                ]],
            ]);
    }

    public function test_index_can_change_page_size_and_page(): void
    {
        Contact::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=10&page=2');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_index_can_search_by_keyword_gender_category_and_date(): void
    {
        $category = Category::factory()->create();
        $target = Contact::factory()->for($category)->create([
            'first_name' => '田中',
            'gender' => 2,
            'created_at' => '2026-01-15 12:00:00',
        ]);
        Contact::factory()->for($category)->create([
            'first_name' => '田中',
            'gender' => 1,
            'created_at' => '2026-01-15 12:00:00',
        ]);
        Contact::factory()->create(['first_name' => '佐藤', 'gender' => 2]);

        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'keyword' => '田中',
            'gender' => 2,
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_index_returns_422_with_japanese_messages(): void
    {
        $this->getJson('/api/v1/contacts?gender=4')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gender' => '性別の値が不正です']);

        $this->getJson('/api/v1/contacts?category_id=9999')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id' => '選択されたカテゴリーが存在しません']);

        $this->getJson('/api/v1/contacts?per_page=101')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_index_returns_json_errors_even_without_accept_header(): void
    {
        $this->get('/api/v1/contacts?gender=4')
            ->assertStatus(422)
            ->assertJsonValidationErrors('gender');
    }

    public function test_show_returns_contact_with_category_and_tags(): void
    {
        $category = Category::factory()->create(['content' => '商品の交換について']);
        $contact = Contact::factory()->for($category)->create(['first_name' => '山田']);
        $tag = Tag::factory()->create(['name' => '質問']);
        $contact->tags()->attach($tag);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '山田')
            ->assertJsonPath('data.category.content', '商品の交換について')
            ->assertJsonPath('data.tags.0.name', '質問');
    }

    public function test_show_returns_404_json_when_not_found(): void
    {
        $this->getJson('/api/v1/contacts/9999')
            ->assertNotFound()
            ->assertExactJson(self::NOT_FOUND_JSON);
    }

    public function test_store_creates_contact_with_tags_and_returns_201(): void
    {
        $payload = $this->contactPayload();

        $response = $this->postJson('/api/v1/contacts', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.first_name', '山田')
            ->assertJsonPath('data.category.id', $payload['category_id'])
            ->assertJsonCount(2, 'data.tags');
        $this->assertDatabaseHas('contacts', ['email' => 'test@example.com', 'tel' => '09012345678']);
        $this->assertDatabaseCount('contact_tag', 2);
    }

    public function test_store_returns_422_with_japanese_messages(): void
    {
        $response = $this->postJson('/api/v1/contacts', $this->contactPayload([
            'first_name' => '',
            'gender' => 5,
            'tel' => '090-1234-5678',
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors([
            'first_name' => '姓を入力してください',
            'gender' => '性別の値が不正です',
            'tel' => '電話番号はハイフンなしの10〜11桁で入力してください',
            'category_id' => '選択されたカテゴリーが存在しません',
            'tag_ids.0' => '選択されたタグが存在しません',
        ]);
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_update_updates_contact_and_syncs_tags(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->count(2)->create());
        $newTag = Tag::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $this->contactPayload([
            'first_name' => '更新',
            'tag_ids' => [$newTag->id],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.first_name', '更新')
            ->assertJsonCount(1, 'data.tags')
            ->assertJsonPath('data.tags.0.id', $newTag->id);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => '更新']);
        $this->assertSame([$newTag->id], $contact->fresh()->tags->pluck('id')->all());
    }

    public function test_update_returns_404_when_not_found(): void
    {
        $this->putJson('/api/v1/contacts/9999', $this->contactPayload())
            ->assertNotFound()
            ->assertExactJson(self::NOT_FOUND_JSON);
    }

    public function test_update_returns_422_for_invalid_data(): void
    {
        $contact = Contact::factory()->create();

        $this->putJson("/api/v1/contacts/{$contact->id}", $this->contactPayload(['email' => 'invalid-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_destroy_deletes_contact_and_returns_204(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertNoContent();

        $this->assertModelMissing($contact);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }

    public function test_destroy_returns_404_when_not_found(): void
    {
        $this->deleteJson('/api/v1/contacts/9999')
            ->assertNotFound()
            ->assertExactJson(self::NOT_FOUND_JSON);
    }

    public function test_japanese_text_is_returned_without_unicode_escape(): void
    {
        $this->getJson('/api/v1/contacts?gender=4')
            ->assertStatus(422)
            ->assertSee('性別の値が不正です', false);

        $content = $this->getJson('/api/v1/contacts/9999')->getContent();

        $this->assertStringContainsString('お問い合わせが見つかりませんでした。', $content);
        $this->assertStringNotContainsString('\u', $content);
    }
}
