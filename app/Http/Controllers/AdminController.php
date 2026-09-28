<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * 管理画面（お問い合わせ一覧・検索・タグ管理）
     */
    public function index(IndexContactRequest $request): View
    {
        $contacts = Contact::with(['category', 'tags'])
            ->search($request->validated())
            ->latest()
            ->latest('id')
            ->paginate(7);
        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    /**
     * お問い合わせ詳細ページ
     */
    public function show(Contact $contact): View
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    /**
     * お問い合わせを削除する
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect('/admin');
    }
}
