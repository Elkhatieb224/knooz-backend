<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminActivityLogger;
use App\Services\Content\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(
        protected ContentService $content,
        protected AdminActivityLogger $activity,
    ) {
    }

    public function index(): View
    {
        $this->authorize('settings.manage');

        return view('admin.content.index', [
            'pages' => ContentService::PAGES,
            'contents' => $this->content->all(),
        ]);
    }

    public function edit(string $page): View
    {
        $this->authorize('settings.manage');
        abort_unless(isset(ContentService::PAGES[$page]), 404);

        return view('admin.content.edit', [
            'page' => $page,
            'pageLabel' => ContentService::PAGES[$page],
            'content' => $this->content->get($page),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $this->authorize('settings.manage');
        abort_unless(isset(ContentService::PAGES[$page]), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $this->content->put($page, $data['title'], $data['body']);
        $this->activity->log(
            $request->user('admin'),
            'content.update',
            'تحديث محتوى: '.ContentService::PAGES[$page],
            meta: ['page' => $page],
            request: $request,
        );

        return redirect()
            ->route('admin.content.index')
            ->with('success', 'تم حفظ المحتوى بنجاح.');
    }
}
