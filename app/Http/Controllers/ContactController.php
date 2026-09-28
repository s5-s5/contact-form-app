<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * お問い合わせフォーム入力ページ
     */
    public function index(): View
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    /**
     * お問い合わせフォーム確認ページ
     */
    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();
        $category = Category::find($validated['category_id']);
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    /**
     * お問い合わせを保存し、サンクスページへ移動する
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        Contact::createWithTags($request->validated());

        return redirect('/thanks');
    }

    /**
     * サンクスページ
     */
    public function thanks(): View
    {
        return view('contact.thanks');
    }
}
