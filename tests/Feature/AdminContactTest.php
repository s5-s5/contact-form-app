<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理画面のお問い合わせ詳細・削除
 */
class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_page_shows_contact_with_category_and_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => '商品の交換について']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 3,
            'building' => 'テストビル101',
        ]);
        $contact->tags()->attach(Tag::factory()->create(['name' => '不具合報告']));

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertViewHas('contact', fn ($viewContact) => $viewContact->is($contact)
            && $viewContact->relationLoaded('category'));
        $response->assertSee('山田');
        $response->assertSee('その他');
        $response->assertSee('テストビル101');
        $response->assertSee('商品の交換について');
        $response->assertSee('不具合報告');
    }

    public function test_contact_can_be_deleted_and_redirects_to_admin(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertModelMissing($contact);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }

    public function test_guest_cannot_view_or_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $this->get("/admin/contacts/{$contact->id}")->assertRedirect('/login');
        $this->delete("/admin/contacts/{$contact->id}")->assertRedirect('/login');

        $this->assertModelExists($contact);
    }
}
