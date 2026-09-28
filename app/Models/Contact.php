<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    /**
     * 性別の値と表示名の対応
     */
    public const GENDERS = [
        1 => '男性',
        2 => '女性',
        3 => 'その他',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * first_name は姓、last_name は名（仕様書のとおり）
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gender' => 'integer',
    ];

    /**
     * お問い合わせの種類
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 付与されたタグ（中間テーブル contact_tag 経由）
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * 性別の表示名
     */
    protected function genderLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::GENDERS[$this->gender] ?? '',
        );
    }

    /**
     * 氏名（姓と名を半角スペースでつなげたもの）
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name.' '.$this->last_name,
        );
    }

    /**
     * 検索条件で絞り込む
     *
     * keyword は姓・名・メールアドレスの部分一致。スペース区切りで複数入力した場合は、すべてを含むものに絞り込む。
     * gender が 0（または未指定）の場合は性別で絞り込まない。
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeSearch(Builder $query, array $filters): Builder
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $gender = (int) ($filters['gender'] ?? 0);
        $categoryId = $filters['category_id'] ?? null;
        $date = $filters['date'] ?? null;

        return $query
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                foreach (preg_split('/[\s　]+/u', $keyword) as $word) {
                    $query->where(function (Builder $query) use ($word) {
                        $query->where('first_name', 'like', "%{$word}%")
                            ->orWhere('last_name', 'like', "%{$word}%")
                            ->orWhere('email', 'like', "%{$word}%");
                    });
                }
            })
            ->when($gender !== 0, fn (Builder $query) => $query->where('gender', $gender))
            ->when($categoryId, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($date, fn (Builder $query) => $query->whereDate('created_at', $date));
    }
}
