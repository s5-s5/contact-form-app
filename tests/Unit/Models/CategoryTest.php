<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * カテゴリのリレーション
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_contacts(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->count(3)->for($category)->create();
        Contact::factory()->for(Category::factory())->create();

        $this->assertCount(3, $category->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }
}
