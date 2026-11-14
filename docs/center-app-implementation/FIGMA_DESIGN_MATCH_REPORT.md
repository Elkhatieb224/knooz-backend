# Center App — Figma Design Match Report

**Refs folder:** `flutter/center/docs/figma-refs/`  
**Tokens applied:** primary `#F68B1F`, nav/splash `#161616`, background `#F4F4F6`, Tajawal, RTL Arabic

## References used

| Ref file | Screen / component | Match level |
|---|---|---|
| `dashboard.png` | Home header, KPIs, ops/bookings cards, quick actions | High (structure + colors); icons Material vs custom |
| `dashboard_sheet.png` | Related dashboard sheet states | Partial (used as supporting ref) |
| `add_operation.png` | Add operation method sheet (QR/NFC/Plate tabs) | High |
| `qr_full.png` / `qr_scanner.png` | QR scanner overlay frame | Medium–High |
| `nfc_scanner.png` | NFC screen + manual fallback CTA | High |
| `plate_entry.png` | Manual plate form card | High |
| `operation_details.png` | Vehicle/customer + services + payment + start | High |
| `operation_or_service.png` | Supporting ops/services selection | Partial |
| `reports_more.png` | Reports KPIs + More menu items | Medium–High |
| `complaints.png` | Complaints list + create entry | Medium–High |
| `login_ref.png` | Login branding copy/layout cues | Medium (Center-specific title enforced) |

## Design decisions aligned with product

- Dark header / dark bottom nav per Center shell  
- Orange primary CTAs and FAB  
- Status chips for booking/operation states  
- Tip boxes for NFC/QR guidance  
- Staff financial restriction messaging on Reports (not a separate Figma variant)

## Remaining visual gaps

1. Custom illustrated icons / brand logo assets not packaged (Material Icons placeholders).  
2. Some Figma micro-interactions (lottie/confetti) not replicated.  
3. Complaints KPI strip (resolved/open/total counters) simplified vs full Figma stats row.  
4. More menu in Figma shows mixed labels (invoices/about); implemented product-scoped items only (customers, complaints, services, profile, logout).  
5. Exact spacing/radius may differ ±4–8dp without a design-token export.  
6. Empty/error illustrations not provided as dedicated frames — used generic `AppEmpty` / `AppErrorView`.

## Out-of-scope Figma frames (ignored)

Any User App frames mixed into Page 2 (wallet, shop, wash, tow, fuel, tire, customer membership QR) were **not** implemented, per CRITICAL RULE #1.
