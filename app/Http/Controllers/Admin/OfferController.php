<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfferRequest;
use App\Http\Requests\Admin\UpdateOfferRequest;
use App\Models\Offer;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('offers.manage');

        $offers = Offer::query()
            ->with('service')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.offers.index', compact('offers'));
    }

    public function create(): View
    {
        $this->authorize('offers.manage');

        return view('admin.offers.create', [
            'services' => Service::query()->orderBy('name_ar')->get(),
        ]);
    }

    public function store(StoreOfferRequest $request): RedirectResponse
    {
        $this->authorize('offers.manage');

        Offer::query()->create($request->validated());

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'تم إنشاء العرض بنجاح.');
    }

    public function show(Offer $offer): View
    {
        $this->authorize('offers.manage');

        $offer->load('service');

        return view('admin.offers.show', compact('offer'));
    }

    public function edit(Offer $offer): View
    {
        $this->authorize('offers.manage');

        return view('admin.offers.edit', [
            'offer' => $offer,
            'services' => Service::query()->orderBy('name_ar')->get(),
        ]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): RedirectResponse
    {
        $this->authorize('offers.manage');

        $offer->update($request->validated());

        return redirect()
            ->route('admin.offers.show', $offer)
            ->with('success', 'تم تحديث العرض بنجاح.');
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $this->authorize('offers.manage');

        $offer->delete();

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'تم حذف العرض بنجاح.');
    }
}
