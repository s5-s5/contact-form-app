<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * タグ管理（作成・編集・更新・削除）と認証
 */
class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_view_tag_edit_page(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($this->user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertOk();
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('質問');
    }

    public function test_authenticated_user_can_create_tag(): void
    {
        $response = $this->actingAs($this->user)->post('/admin/tags', ['name' => '新機能の要望']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新機能の要望']);
    }

    public function test_tag_creation_shows_validation_errors(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $this->actingAs($this->user)->from('/admin')->post('/admin/tags', ['name' => ''])
            ->assertRedirect('/admin')
            ->assertSessionHasErrors(['name' => 'タグ名を入力してください']);
        $this->actingAs($this->user)->from('/admin')->post('/admin/tags', ['name' => str_repeat('あ', 51)])
            ->assertSessionHasErrors(['name' => 'タグ名は50文字以内で入力してください']);
        $this->actingAs($this->user)->from('/admin')->post('/admin/tags', ['name' => '質問'])
            ->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_authenticated_user_can_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($this->user)->put("/admin/tags/{$tag->id}", ['name' => 'よくある質問']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'よくある質問']);
    }

    public function test_tag_can_keep_its_own_name_but_cannot_use_another_tags_name(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);
        Tag::factory()->create(['name' => '要望']);

        $this->actingAs($this->user)->put("/admin/tags/{$tag->id}", ['name' => '質問'])
            ->assertRedirect('/admin')
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)->from("/admin/tags/{$tag->id}/edit")->put("/admin/tags/{$tag->id}", ['name' => '要望'])
            ->assertRedirect("/admin/tags/{$tag->id}/edit")
            ->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '質問']);
    }

    public function test_authenticated_user_can_delete_tag_and_its_relations(): void
    {
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag);

        $response = $this->actingAs($this->user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertModelMissing($tag);
        $this->assertDatabaseMissing('contact_tag', ['tag_id' => $tag->id]);
        $this->assertModelExists($contact);
    }

    public function test_guest_cannot_operate_tags(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $this->get("/admin/tags/{$tag->id}/edit")->assertRedirect('/login');
        $this->post('/admin/tags', ['name' => '新しいタグ'])->assertRedirect('/login');
        $this->put("/admin/tags/{$tag->id}", ['name' => '変更後'])->assertRedirect('/login');
        $this->delete("/admin/tags/{$tag->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '質問']);
        $this->assertDatabaseCount('tags', 1);
    }
}
