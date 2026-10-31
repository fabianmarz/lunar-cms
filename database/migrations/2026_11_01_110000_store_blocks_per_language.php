<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Lunar\Core\Models\Language;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Blocks become one list per language, so translations can differ in
     * structure. The previous per-field language maps collapse to the default
     * language's value inside that language's list.
     */
    public function up(): void
    {
        $default = Language::query()->where('default', true)->value('code') ?? config('app.locale');

        DB::table('pages')->get(['id', 'blocks'])->each(function (object $page) use ($default): void {
            $blocks = json_decode($page->blocks ?? '[]', true) ?: [];

            if ($blocks === [] || ! array_is_list($blocks)) {
                return;
            }

            $blocks = array_map(function (array $block) use ($default): array {
                foreach (['content', 'alt', 'label'] as $field) {
                    $value = $block['data'][$field] ?? null;

                    if (is_array($value)) {
                        $block['data'][$field] = $value[$default] ?? (string) (reset($value) ?: '');
                    }
                }

                return $block;
            }, $blocks);

            DB::table('pages')->where('id', $page->id)->update(['blocks' => json_encode([$default => $blocks])]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $default = Language::query()->where('default', true)->value('code') ?? config('app.locale');

        DB::table('pages')->get(['id', 'blocks'])->each(function (object $page) use ($default): void {
            $blocks = json_decode($page->blocks ?? '[]', true) ?: [];

            if ($blocks === [] || array_is_list($blocks)) {
                return;
            }

            $list = $blocks[$default] ?? (reset($blocks) ?: []);

            $list = array_map(function (array $block) use ($default): array {
                foreach (['content', 'alt', 'label'] as $field) {
                    if (isset($block['data'][$field]) && is_string($block['data'][$field])) {
                        $block['data'][$field] = [$default => $block['data'][$field]];
                    }
                }

                return $block;
            }, $list);

            DB::table('pages')->where('id', $page->id)->update(['blocks' => json_encode($list)]);
        });
    }
};
