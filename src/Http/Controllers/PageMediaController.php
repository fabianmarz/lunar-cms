<?php

namespace LunarCms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LunarCms\Models\Page;

class PageMediaController
{
    /**
     * Attach an uploaded image to the page's blocks collection. The block
     * editor stores the returned media id in the block's data payload.
     */
    public function store(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:10240'],
        ]);

        $media = $page->addMediaFromRequest('file')->toMediaCollection('blocks');

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
            'name' => $media->name,
        ], 201);
    }
}
