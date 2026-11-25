<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCenterRequest;
use App\Http\Requests\Admin\UpdateCenterRequest;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Service;
use App\Services\Notifications\NotificationHooks;
use App\Support\ServiceChannels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CenterController extends Controller
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('centers.view');

        $centers = Center::query()
            ->with('managerCenterUser')
            ->withCount('services')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('channel'), function ($q) use ($request) {
                $channel = $request->string('channel')->toString();
                if (in_array($channel, [ServiceChannels::CENTER, ServiceChannels::HOME, ServiceChannels::EMERGENCY], true)) {
                    $q->offeringChannel($channel);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.centers.index', [
            'centers' => $centers,
            'channels' => [
                ServiceChannels::CENTER => ServiceChannels::labels()[ServiceChannels::CENTER],
                ServiceChannels::HOME => ServiceChannels::labels()[ServiceChannels::HOME],
                ServiceChannels::EMERGENCY => ServiceChannels::labels()[ServiceChannels::EMERGENCY],
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('centers.manage');

        return view('admin.centers.create', $this->formData());
    }

    public function store(StoreCenterRequest $request): RedirectResponse
    {
        $this->authorize('centers.manage');

        $center = DB::transaction(function () use ($request) {
            $data = $request->safe()->except([
                'services',
                'manager_name',
                'manager_email',
                'manager_phone',
                'manager_password',
                'manager_password_confirmation',
                'manager_center_user_id',
            ]);

            $center = Center::query()->create($data);
            $this->syncServices($center, $request->input('services', []));

            $manager = CenterUser::query()->create([
                'center_id' => $center->id,
                'name' => $request->validated('manager_name'),
                'email' => $request->validated('manager_email'),
                'phone' => $request->validated('manager_phone'),
                'password' => $request->validated('manager_password'),
                'role' => CenterUserRole::Manager,
                'status' => UserStatus::Active,
                'theme' => Theme::Light,
            ]);

            $center->update(['manager_center_user_id' => $manager->id]);

            return $center->fresh(['managerCenterUser']);
        });

        $this->notificationHooks->centerCreated($center);

        return redirect()
            ->route('admin.centers.show', $center)
            ->with('success', 'تم إنشاء المركز وحساب مسؤول المركز بنجاح.');
    }

    public function show(Center $center): View
    {
        $this->authorize('centers.view');

        $center->load(['managerCenterUser', 'manager', 'services', 'employees']);

        return view('admin.centers.show', compact('center'));
    }

    public function edit(Center $center): View
    {
        $this->authorize('centers.manage');

        $center->load('services');

        return view('admin.centers.edit', array_merge($this->formData(), compact('center')));
    }

    public function update(UpdateCenterRequest $request, Center $center): RedirectResponse
    {
        $this->authorize('centers.manage');

        DB::transaction(function () use ($request, $center) {
            $data = $request->safe()->except(['services']);
            $center->update($data);
            $this->syncServices($center, $request->input('services', []));

            if (! empty($data['manager_center_user_id'])) {
                CenterUser::query()
                    ->whereKey($data['manager_center_user_id'])
                    ->update(['center_id' => $center->id]);
            }
        });

        return redirect()
            ->route('admin.centers.show', $center)
            ->with('success', 'تم تحديث المركز بنجاح.');
    }

    public function destroy(Center $center): RedirectResponse
    {
        $this->authorize('centers.manage');

        if ($center->bookings()->whereIn('status', ['new', 'in_progress'])->exists()) {
            return back()->with('error', 'لا يمكن حذف مركز لديه حجوزات جارية.');
        }

        $center->delete();

        return redirect()
            ->route('admin.centers.index')
            ->with('success', 'تم حذف المركز بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $services = Service::query()
            ->where('is_active', true)
            ->whereIn('channel', [
                ServiceChannels::CENTER,
                ServiceChannels::HOME,
                ServiceChannels::EMERGENCY,
            ])
            ->orderBy('channel')
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return [
            'types' => CenterType::labels(),
            'statuses' => CenterStatus::labels(),
            'services' => $services,
            'servicesByChannel' => $services->groupBy(fn (Service $s) => $s->channel ?: 'other'),
            'channelLabels' => [
                ServiceChannels::CENTER => ServiceChannels::labels()[ServiceChannels::CENTER],
                ServiceChannels::HOME => ServiceChannels::labels()[ServiceChannels::HOME],
                ServiceChannels::EMERGENCY => ServiceChannels::labels()[ServiceChannels::EMERGENCY],
            ],
            'managers' => CenterUser::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $services
     */
    protected function syncServices(Center $center, array $services): void
    {
        $allowedChannels = $center->offeredChannels();
        $catalog = Service::query()
            ->whereIn('id', array_keys($services))
            ->get()
            ->keyBy('id');

        $sync = [];

        foreach ($services as $serviceId => $row) {
            if (empty($row['enabled'])) {
                continue;
            }

            $service = $catalog->get((int) $serviceId);
            if (! $service) {
                continue;
            }

            // Only attach services that match an enabled execution channel on this center.
            if (! in_array((string) $service->channel, $allowedChannels, true)) {
                continue;
            }

            $mode = (($row['app_profit_mode'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
            $profit = (float) ($row['app_profit'] ?? 0);
            if ($mode === 'percent') {
                $profit = min(100, max(0, $profit));
            }

            $sync[$serviceId] = [
                'contract_price' => $row['contract_price'] ?? 0,
                'app_profit' => $profit,
                'app_profit_mode' => $mode,
                'is_available' => ! empty($row['is_available']),
            ];
        }

        $center->services()->sync($sync);
    }
}
