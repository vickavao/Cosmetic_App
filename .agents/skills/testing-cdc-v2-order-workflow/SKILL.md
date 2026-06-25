---
name: testing-cdc-v2-order-workflow
description: Test the CDC V2 4-step order workflow end-to-end across multiple user roles. Use when verifying order lifecycle, role-based access, stock management, or invoice generation changes.
---

# Testing the CDC V2 Order Workflow

## Overview

This skill covers end-to-end browser testing of the 4-step order workflow:
1. **Commande** (Agent Marketeur N2) — create order (Comptant or Credit)
2. **Validation** (Chef Marketing N1) — validate or reject
3. **Bon de Sortie** (Magasinier) — prepare goods, decrement stock
4. **Livraison + Facture** (Agent Marketeur N2) — deliver, auto-generate invoice

## Devin Secrets Needed

No secrets required. The app uses seeded test accounts with password `password`.

## Prerequisites

1. Ensure the Laravel server is running: `php artisan serve`
2. Database must be seeded: `php artisan migrate:fresh --seed`
3. Frontend assets must be built: `npm run build`
4. Auth views might need publishing if missing: `php artisan vendor:publish --tag=laravel-auth-views` or create minimal stubs in `resources/views/auth/`

## Test Accounts

| Email | Role | Password |
|-------|------|----------|
| `chef@premidis.test` | Chef Marketing (N1) | `password` |
| `agent1@premidis.test` | Agent Marketeur (N2) | `password` |
| `terrain1@premidis.test` | Agent Terrain (N3) | `password` |
| `magasinier@premidis.test` | Magasinier | `password` |

## Test Procedure

### Test 1: Create COMPTANT Order (Agent Marketeur)
1. Login as `agent1@premidis.test`
2. Navigate to sidebar "Mes Commandes" > "+ Nouvelle commande"
3. Select a client, verify "Type de vente" defaults to "Au comptant"
4. Add a product line, set quantity
5. Verify total updates dynamically (Alpine.js)
6. Submit — verify redirect to order show page with status "En attente"

### Test 2: Validate Order (Chef Marketing)
1. Logout, login as `chef@premidis.test`
2. Navigate to "Commandes a Valider" in sidebar
3. Click on the pending order
4. Click "Valider la commande"
5. Verify status changes to "Validee" with flash message

### Test 3: Bon de Sortie + Stock Decrement (Magasinier)
1. Logout, login as `magasinier@premidis.test`
2. Navigate to "Bons de Sortie" > "Creer un bon de sortie"
3. Select the validated order
4. Submit — verify stock decremented (check product stock before/after)
5. Verify order status changes to "Prete a livraison"

### Test 4: Delivery + Auto-Invoice (Agent Marketeur)
1. Logout, login as `agent1@premidis.test`
2. Navigate to "Livraisons" > "Nouvelle livraison"
3. Verify the ready order appears with correct details
4. Click "Livrer et generer la facture", accept confirm dialog
5. Verify delivery page shows "Livre" status
6. Verify invoice was auto-generated (check link on delivery page or invoices list)

### Test 5: Create CREDIT Order with Date Echeance
1. Still as agent1, create a new order
2. Change "Type de vente" to "A credit"
3. Verify "Date d'echeance" field appears (Alpine.js x-show)
4. Set a future date, add products, submit
5. Verify order show page displays "A credit" and the echeance date

### Test 6: Reject Order (Chef Marketing)
1. Login as chef, find the credit order
2. Click "Refuser" button — verify Alpine.js reveals the rejection form
3. Type a rejection motif, click "Confirmer le refus"
4. Verify status changes to "Refusee"/"Annulee" with motif displayed

### Test 7: Access Control — Agent Terrain Blocked
1. Login as `terrain1@premidis.test`
2. Navigate directly to `/orders/create`
3. Verify 403 Forbidden page (Agent Terrain cannot create orders per CDC V2)

## Common Issues and Workarounds

### Status Badge Errors (`UnhandledMatchError`)
The dashboard and orders index use `match()` expressions for status badge colors. If new statuses are added to the `OrderStatus` enum, ensure ALL cases are covered in:
- `resources/views/dashboard/partials/agent.blade.php`
- `resources/views/orders/index.blade.php`

### Variable Naming Mismatches
Controller-to-view variable passing might use different names. Common mismatches:
- Controller passes `$orders` (collection) but view expects `$order` (singular)
- View uses `$note` but controller passes `$goodsIssueNote`
- Column name `quantite` (French) vs `quantity` (English) — check migration column names

### OrderPolicy Access Control
The `OrderPolicy::create()` method controls who can access `/orders/create`. Per CDC V2, only these roles should create orders:
- Admin, Directeur, Commercial, AgentMarketeur
- **NOT** MarketeurTerrain (Agent Terrain N3)

### Auth Views Missing
If login/register pages show errors, the Breeze auth views might not be published. Run:
```bash
php artisan vendor:publish --tag=laravel-auth-views
```
Or create minimal stubs for `resources/views/auth/login.blade.php` etc.

### Alpine.js Not Working
If the credit/echeance toggle doesn't work, ensure:
1. `npm run build` was run after any JS changes
2. The `x-data`, `x-show`, `x-model` directives are correctly placed
3. Check browser console for JS errors

## Verification Checklist

- [ ] Stock only changes at Bon de Sortie (not at order creation or validation)
- [ ] Invoice inherits `type_vente` and `date_echeance` from the order
- [ ] Credit orders require `date_echeance` (validation error if missing)
- [ ] Rejection requires and displays a motif
- [ ] Agent Terrain gets 403 on order creation
- [ ] Each step shows correct status badge and flash message
