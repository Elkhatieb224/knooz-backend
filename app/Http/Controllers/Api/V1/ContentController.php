<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Content\ContentService;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function __construct(protected ContentService $content)
    {
    }

    public function show(string $page): JsonResponse
    {
        abort_unless(isset(ContentService::PAGES[$page]), 404);

        return response()->json([
            'data' => $this->content->get($page),
        ]);
    }
}
