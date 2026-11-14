# Data Isolation

## Goals

1. Company A never reads Company B fleet/invoices/subscriptions.
2. Center A never mutates Center B bookings/operations.
3. Panel credentials cannot cross into another panel’s guard.

## Mechanisms

### 1. Separate auth tables & guards

Cross-panel URL access fails `auth:{guard}` (redirect to that panel’s login) even if another guard is authenticated in the same session.

### 2. Tenant helper

`App\Support\Tenant`:

- `companyId()` → `Auth::guard('company')->user()?->company_id`
- `centerId()` → `Auth::guard('center')->user()?->center_id`

### 3. Eloquent scopes

- `App\Models\Concerns\BelongsToCompany` → `scopeForCompany`
- `App\Models\Concerns\BelongsToCenter` → `scopeForCenter`

Applied on `Vehicle`, `Invoice`, `Subscription`, `Booking`, `Operation`.

### 4. Controller ownership checks

Company / Center controllers call `abort_unless(... === Tenant::…(), 404)` on show/update actions.

### 5. Middleware

`EnsureCompany` / `EnsureCenter` require the tenant FK to be present and status `active`.

## Testing

`tests/Feature/PanelAuthIsolationTest.php` covers:

- Admin login → `/admin`
- Company session blocked from `/admin`
- Center session blocked from `/company`
- Company cannot open another company’s vehicle (404)
- Center cannot update another center’s booking (404)

## Extending isolation

When adding company/center endpoints:

1. Resolve tenant id via `Tenant`
2. Scope queries with `forCompany` / `forCenter`
3. `abort_unless` on route-model bound records
4. Prefer policies later (`CompanyVehiclePolicy`, etc.) without removing abort checks
