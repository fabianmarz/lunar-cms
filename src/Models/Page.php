<?php

namespace LunarCms\Models;

use Illuminate\Database\Eloquent\Model;
use LunarCms\PageStatus;

class Page extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'status',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function publish(): void
    {
        $this->status = PageStatus::Published;
        $this->published_at ??= now();
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status = PageStatus::Draft;
        $this->save();
    }
}
