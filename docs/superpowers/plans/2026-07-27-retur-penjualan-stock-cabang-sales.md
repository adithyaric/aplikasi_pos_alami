# Retur Penjualan Global, Stock Cabang, and Branch Sales Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Date:** 2026-07-27

**Goal:** Add a buyer-scoped `Retur Penjualan` flow, complete `Stock Cabang`, enable `Penjualan Cabang` through sales users, add `Retur Penjualan Cabang` from customer/toko, and add an `admin-cabang` role connected to one cabang with full cabang-management access.

**Tech Stack:** Laravel 9, Blade, Bootstrap 3/AdminLTE, jQuery, Select2, DataTables.

---

## Business Rules

- `Retur Penjualan` must be one page, similar to `Retur Pembelian`, and must not require choosing one invoice item-by-item.
- A return is entered as accumulated returned products: product, qty, selling price, subtotal, and reason.
- For warehouse buyer returns, valid buyer types are `agent`, `canvas`, and `outlet`/cabang.
- Warehouse buyer returns must reduce the latest unpaid warehouse invoice for the same buyer, even when the returned product does not exist on that latest invoice.
- The reduction value is `qty retur * harga jual`.
- The latest unpaid invoice for the selected `agent`, `canvas`, or `outlet`/cabang must never become zero or negative. Use `max_return_total = latest_invoice_total - 1` and block the return when `return_total > max_return_total`.
- Warehouse buyer returns for `agent` and `canvas` must not check or mutate physical stock.
- Warehouse buyer returns for `outlet`/cabang must reverse cabang stock because warehouse sales to cabang create `OwnerStock`.
- When admin chooses buyer type `outlet` in Penjualan, the return is cabang returning goods to gudang: reduce the latest unpaid warehouse invoice for that cabang, decrease that cabang's `OwnerStock`, and return the stock to warehouse `Stock`.
- Return stock quantities should still be stored for audit/history. Agent/canvas stock-return audit is hidden from stock pages.
- Branch stock comes from warehouse sales to cabang and is stored in cabang stock.
- Branch sales are made by sales users to customer/toko and allocate from that sales user's assigned cabang stock.
- Branch sales returns are customer/toko returns to the cabang and must put returned stock back into that cabang stock. This is different from cabang returning goods to gudang.
- Customer for sales is separate from Customer PO. Use a dedicated sales customer/toko concept, not `CustomerPo`.
- `admin-cabang` is connected to one cabang like salesman, but has broader access to manage that cabang.

---

## Current Codebase Context

- `PenjualanController` already has warehouse sales for `agent`, `canvas`, and `outlet` buyers.
- `WarehousePenjualanManager` allocates warehouse `stocks` and increases `OwnerStock` when warehouse sells to `outlet`/cabang.
- Current `RefundController` is still transaction-scoped: the form requires `penjualan_id` and refund products must exist on the selected invoice.
- `refunds` already has `buyer_type`, `buyer_id`, and `buyer_name`, but `refund_items` only stores product, qty, and reason.
- Existing `RefundPembelianController` has an `outlet_ke_gudang` flow that moves cabang stock back to warehouse stock, but it does not reduce the related warehouse sales invoice. The new sales-return flow must handle both stock reversal and invoice adjustment for `buyer_type = outlet`.
- `OwnerStock` is the current cabang-stock table, but `OwnerStockController` contains `dd()` calls and there are no `resources/views/owner-stocks/*` files.
- `owner_stocks` migration defines `batch_number` and `hpp`, while the model and service code use `sku` and `harga_beli`. This mismatch must be fixed before branch stock features are reliable.
- `StockMovement` is warehouse-global only and has no cabang/owner field, so cabang stock needs its own movement ledger or a scoped extension.
- Salesman users already get role `sales` and have `outlet_id`, but the authenticated route group currently does not include `sales`.
- Existing `CustomerPo` maps to table `customer_pos` and is used for purchasing PO customer data. Do not reuse this for sales customer/toko without explicitly renaming/separating it.

---

## Proposed Architecture

### Return Engine

Create a single return engine that supports:

- `warehouse_affiliate_return`: buyer is `agent` or `canvas`; reduce latest unpaid warehouse invoice; no stock mutation; stock audit is hidden.
- `warehouse_branch_return`: buyer is `outlet`/cabang; reduce latest unpaid warehouse invoice; decrease cabang `OwnerStock`; increase warehouse `Stock`; record both cabang and warehouse stock movements.
- `branch_customer_return`: buyer is customer/toko; reduce latest unpaid branch invoice for the same branch and customer; increase cabang stock.

The return page stays one page. The user selects return type/buyer and products; the backend finds and locks the latest unpaid invoice.

Do not mutate `penjualan_items` on the latest invoice because the returned product may not exist on that invoice. Instead, update `penjualans.total` and write an explicit invoice-adjustment/audit row.

### Branch Stock

Use `owner_stocks` as the stock-on-hand table for cabang and add an owner-scoped movement ledger:

- Warehouse sale to cabang: `owner_stock_movements.type = in`.
- Cabang return to warehouse from warehouse sales return: `owner_stock_movements.type = return_out`.
- Branch sale to toko: `owner_stock_movements.type = out`.
- Branch customer return: `owner_stock_movements.type = return_in`.
- Branch stock opname: `owner_stock_movements.type = adjustment`.
- Branch return to warehouse/supplier, if existing flow touches `OwnerStock`: `owner_stock_movements.type = return_out`.

This avoids mixing cabang balances into warehouse `stock_movements`.

### Branch Sales

Reuse the Penjualan page where possible, but split service behavior by `sale_channel`:

- `sale_channel = warehouse`: current warehouse sale behavior, stock source is `stocks`.
- `sale_channel = branch`: new branch sale behavior, stock source is `owner_stocks`.

For `sales` and `admin-cabang`, the form should automatically scope to their assigned cabang and sell only to customer/toko.

---

## File Map

| Action | File |
|--------|------|
| Modify | `routes/web.php` |
| Modify | `app/Http/Middleware/RoleMiddleware.php` |
| Modify | `app/Http/Controllers/AdminController.php` |
| Modify | `app/Http/Requests/AdminRequest.php` |
| Modify | `app/Models/User.php` |
| Modify | `app/Models/Outlet.php` |
| Modify | `app/Models/OwnerStock.php` |
| Modify | `app/Models/Penjualan.php` |
| Modify | `app/Models/PenjualanItemAllocation.php` |
| Modify | `app/Models/Refund.php` |
| Modify | `app/Models/RefundItem.php` |
| Modify | `app/Http/Controllers/PenjualanController.php` |
| Modify | `app/Http/Requests/WarehousePenjualanRequest.php` |
| Modify | `app/Services/WarehousePenjualanManager.php` |
| Modify | `app/Http/Controllers/RefundController.php` |
| Modify | `resources/views/refunds/partials/form.blade.php` |
| Modify | `resources/views/refunds/partials/form-script.blade.php` |
| Modify | `resources/views/penjualan/partials/warehouse-form.blade.php` |
| Modify | `resources/views/penjualan/partials/warehouse-form-script.blade.php` |
| Modify | `resources/views/layouts/sidebar.blade.php` |
| Add | `app/Services/SalesReturnManager.php` |
| Add | `app/Services/BranchPenjualanManager.php` |
| Add | `app/Models/PenjualanTotalAdjustment.php` |
| Add | `app/Models/OwnerStockMovement.php` |
| Add | `app/Models/OwnerStockAdjustment.php` |
| Add | `app/Http/Controllers/BranchStockController.php` |
| Add | `app/Http/Requests/BranchPenjualanRequest.php` |
| Add | `app/Http/Requests/SalesReturnRequest.php` |
| Add | `resources/views/branch-stocks/index.blade.php` |
| Add | `resources/views/branch-stocks/kartu.blade.php` |
| Add | `resources/views/branch-stocks/opname.blade.php` |
| Add | database migrations listed in Task 1 |

---

## Task 1: Database and Model Foundation

- [ ] Create migration to normalize `owner_stocks`: add `sku` and `harga_beli` if missing, backfill from `batch_number` and `hpp`, and add indexes on `owner_id`, `product_id`, and `stock_id`.
- [ ] Create `owner_stock_movements` with `owner_id`, `product_id`, `owner_stock_id`, `stock_id`, `user_id`, `type`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance`, `notes`, timestamps.
- [ ] Create `owner_stock_adjustments` with `owner_id`, `product_id`, `owner_stock_id`, `adjustment_date`, `system_qty`, `physical_qty`, `quantity`, `reason`, `keterangan`, `status`, `user_id`, timestamps.
- [ ] Add nullable `owner_stock_id` to `penjualan_item_allocations` so branch sales can rollback exact cabang-stock batches.
- [ ] Add return metadata to `refunds`: `return_scope`, `sale_channel`, `applied_penjualan_id`, `source_outlet_id`, `salesman_id`, `invoice_total_before`, `invoice_total_after`, `notes`.
- [ ] Valid `return_scope` values should include `warehouse_affiliate_return`, `warehouse_branch_return`, and `branch_customer_return`.
- [ ] Add pricing metadata to `refund_items`: `qty_input`, `unit`, `price`, `subtotal`, `stock_visibility`, and optional `source_owner_stock_id`.
- [ ] Create `penjualan_total_adjustments` with `penjualan_id`, `refund_id`, `type`, `amount`, `total_before`, `total_after`, `user_id`, `notes`, timestamps.
- [ ] Update models/fillables/relationships for `OwnerStockMovement`, `OwnerStockAdjustment`, `PenjualanTotalAdjustment`, `Refund`, `RefundItem`, `Penjualan`, and `PenjualanItemAllocation`.
- [ ] Add `Outlet::shops()` scope for `jenis_outlet = toko`, and keep `Outlet::branches()` for cabang.
- [ ] Decide whether the UI label is `Customer/Toko` while technically stored in `outlets` with `jenis_outlet = toko`; document this in model comments or controller naming.

Acceptance checks:

- [ ] `php artisan migrate:fresh --seed` runs without owner-stock column errors.
- [ ] Existing warehouse sales to cabang still create or update `OwnerStock`.
- [ ] `OwnerStock::with(['owner', 'product', 'stock'])` works after migration.

---

## Task 2: Access Control and `admin-cabang`

- [ ] Add `admin-cabang` to managed roles in `AdminController` and validation in `AdminRequest`.
- [ ] Require `outlet_id` for `admin-cabang`, `staff-outlet`, and `sales` users; validate the outlet is a cabang where needed.
- [ ] Keep `sales` users created from `SalesmanController` as role `sales`, linked to the salesman and cabang.
- [ ] Add helper methods for cabang scoping, for example `User::branchId()` and `User::isBranchScoped()`.
- [ ] Update `RoleMiddleware` home routes for `sales` and `admin-cabang`.
- [ ] Update the authenticated route group in `routes/web.php` to include `sales` and `admin-cabang` where needed.
- [ ] Add controller guard helpers: warehouse-only, branch-admin-or-sales, and branch-read access.
- [ ] Update sidebar visibility:
  - `superadmin`, `admin-gudang`, `owner`: warehouse-level pages.
  - `admin-cabang`: cabang stock, cabang stock card, cabang opname, branch penjualan, branch retur, salesmen/customers for own cabang.
  - `sales`: branch penjualan and branch retur for own cabang.
  - `staff-outlet`: keep current outlet request/return behavior unless intentionally replaced.

Acceptance checks:

- [ ] `admin-cabang` cannot access another cabang by changing request parameters.
- [ ] `sales` cannot access warehouse sales to agent/canvas.
- [ ] Existing `superadmin`, `admin-gudang`, and `owner` behavior remains unchanged.

---

## Task 3: Global `Retur Penjualan` Page

Replace the transaction-scoped refund form behavior with a buyer-scoped return form.

- [ ] Update `RefundController::create()` to load active agents, active canvases, branch/toko context when branch mode is enabled, and product options.
- [ ] Remove required invoice selection from `resources/views/refunds/partials/form.blade.php`.
- [ ] Add return scope selector:
  - Warehouse affiliate return: `agent` or `canvas`.
  - Warehouse branch return: `outlet`/cabang.
  - Branch customer return: customer/toko, only shown for branch-scoped users or branch return routes.
- [ ] Add buyer selector, return date, code, notes, and product rows.
- [ ] Product rows need product, qty, unit, selling price, subtotal, and reason.
- [ ] When buyer type is `outlet`/cabang, product rows must allocate from that cabang's `OwnerStock` FIFO by expiry/id, with optional batch detail display.
- [ ] Add AJAX endpoint for latest unpaid invoice preview:
  - Input: `sale_channel`, `buyer_type`, `buyer_id`, and optional `source_outlet_id`.
  - Output: invoice id/code/date/total/payment status/max return value.
- [ ] Add AJAX endpoint for last selling price:
  - Warehouse return: latest price for buyer plus product from warehouse sales history.
  - Branch return: latest price for branch plus customer/toko plus product from branch sales history.
- [ ] In the UI, show a clear warning when no unpaid invoice exists or when return total exceeds `max_return_total`.
- [ ] Keep edit/destroy available only if rollback can safely restore invoice total and stock movements.

Acceptance checks:

- [ ] User can enter multiple returned products without selecting an invoice.
- [ ] Latest unpaid invoice preview changes when buyer changes.
- [ ] Submit is blocked when the resulting invoice total would be `<= 0`.
- [ ] Returned product does not need to exist on the latest unpaid invoice.
- [ ] For outlet/cabang buyer returns, stock must exist in the selected cabang before the return can be saved.

---

## Task 4: `SalesReturnManager`

Create `app/Services/SalesReturnManager.php` and move return side effects out of the controller.

Warehouse affiliate return algorithm (`agent` and `canvas`):

- [ ] Lock the selected buyer's latest unpaid warehouse invoice:
  - `sale_channel = warehouse`
  - `buyer_type in agent,canvas`
  - `buyer_id = selected buyer`
  - `payment_status = unpaid`
  - order by `sale_date desc, id desc`
- [ ] Normalize and group returned items by product.
- [ ] Default price from last buyer/product sale price, but allow user override.
- [ ] Compute `return_total = sum(qty * price)`.
- [ ] Validate `return_total <= latest_invoice.total - 1`.
- [ ] Create `refunds` with `return_scope = warehouse_buyer_return`, `penjualan_id = null`, and `applied_penjualan_id = latest_invoice.id`.
- [ ] Create `refund_items` with qty, price, subtotal, reason, and `stock_visibility = hidden`.
- [ ] Update `penjualans.total` to `total - return_total`.
- [ ] Create `penjualan_total_adjustments` row for audit.
- [ ] Sync `PenjualanPayment` status/amount if present, but do not mark the invoice paid.
- [ ] Do not create warehouse `Stock` or `StockMovement` for agent/canvas return stock.

Warehouse branch return algorithm (`outlet`/cabang buyer selected by admin):

- [ ] Lock the selected cabang's latest unpaid warehouse invoice:
  - `sale_channel = warehouse`
  - `buyer_type = outlet`
  - `buyer_id = selected cabang/outlet id`
  - `payment_status = unpaid`
  - order by `sale_date desc, id desc`
- [ ] Normalize and group returned items by product.
- [ ] Default price from last cabang/product warehouse sale price, but allow user override.
- [ ] Compute `return_total = sum(qty * price)`.
- [ ] Validate `return_total <= latest_invoice.total - 1`.
- [ ] Validate the selected cabang has enough `OwnerStock` for each returned product. Allocate FIFO by expiry/id if the UI does not choose a specific batch.
- [ ] Create `refunds` with `return_scope = warehouse_branch_return`, `penjualan_id = null`, and `applied_penjualan_id = latest_invoice.id`.
- [ ] Create `refund_items` with qty, price, subtotal, reason, `stock_visibility = visible`, and `source_owner_stock_id` where available.
- [ ] Update `penjualans.total` to `total - return_total`.
- [ ] Create `penjualan_total_adjustments` row for audit.
- [ ] Decrease allocated cabang `OwnerStock.qty`.
- [ ] Increase linked warehouse `Stock.qty` when `owner_stock.stock_id` exists.
- [ ] If returned cabang stock has no linked warehouse `stock_id`, create a warehouse `Stock` row with `sku = product.code . '-RETUR-CABANG-' . refund.code`, `condition = used`, and positive qty.
- [ ] Create `owner_stock_movements` with `type = return_out` and `reference_type = Refund::class`.
- [ ] Create warehouse `StockMovement` with `type = in` and `reference_type = Refund::class`.
- [ ] Sync `PenjualanPayment` status/amount if present, but do not mark the invoice paid.

Branch customer return algorithm:

- [ ] Lock latest unpaid branch invoice for same branch and customer/toko:
  - `sale_channel = branch`
  - `outlet_id = branch id`
  - `buyer_type = toko`
  - `buyer_id = customer/toko id`
  - `payment_status = unpaid`
- [ ] Apply the same invoice total reduction and adjustment history rule.
- [ ] Increase `OwnerStock` for the selected branch and product.
- [ ] If there is no original `owner_stock_id`, create/update an `OwnerStock` row with `stock_id = null`, `sku = product.code . '-RETUR-' . refund.code`, `harga_beli = product.harga_beli`, and positive qty.
- [ ] Create `owner_stock_movements` with `type = return_in` and `reference_type = Refund::class`.
- [ ] Mark branch stock-return rows as visible in stock cabang pages.

Rollback algorithm:

- [ ] Restore adjusted invoice total using `penjualan_total_adjustments`.
- [ ] Delete or reverse the adjustment row.
- [ ] For branch customer returns, subtract the returned qty from `OwnerStock` and delete/reverse `owner_stock_movements`.
- [ ] For warehouse branch returns, re-decrease warehouse `Stock`, re-increase cabang `OwnerStock`, and delete/reverse both warehouse and cabang movements.
- [ ] For warehouse affiliate returns, only rollback invoice adjustment and refund rows.

Acceptance checks:

- [ ] Manager methods are idempotent inside DB transactions.
- [ ] Race condition is prevented by `lockForUpdate()` on target invoice and touched stock rows.
- [ ] Controller catches domain exceptions and returns existing toast-style validation errors.

---

## Task 5: Branch Stock Pages

Build `BranchStockController` and views based on the existing warehouse stock pages, but scoped by cabang.

Routes:

- [ ] `GET /branch-stock` -> branch stock summary.
- [ ] `GET /branch-stock/kartu` -> product selector for cabang stock card.
- [ ] `GET /branch-stock/kartu/data` -> movement data for product plus cabang.
- [ ] `GET /branch-stock/opname` -> cabang stock opname screen.
- [ ] `GET /branch-stock/opname/data` -> current cabang stock data.
- [ ] `POST /branch-stock/opname/save` -> save cabang adjustment.
- [ ] Optional: `GET /branch-stock/opname/export-template`.

Implementation:

- [ ] Summary page groups `OwnerStock` by `owner_id` and `product_id`, showing qty, last sku, expiry, and value.
- [ ] `admin-cabang` and `sales` users are forced to their own `outlet_id`.
- [ ] Warehouse roles can filter cabang.
- [ ] Kartu stock reads `owner_stock_movements` by `owner_id` and `product_id`, with running balance.
- [ ] Opname compares physical qty to summed `OwnerStock.qty`, writes `owner_stock_adjustments`, adjusts the latest `OwnerStock` row for that product, and writes `owner_stock_movements`.
- [ ] Remove `dd()` calls from `OwnerStockController` or replace that controller entirely with `BranchStockController`.
- [ ] Add movement logging wherever current code changes `OwnerStock`: warehouse sale to cabang, warehouse branch return, delivery order to cabang, branch sale to toko, branch return, and refund pembelian outlet flow.

Acceptance checks:

- [ ] Branch stock page only shows cabang stock, not agent/canvas hidden return audit.
- [ ] Kartu stock balance matches `OwnerStock` total for selected cabang and product.
- [ ] Opname cannot adjust another cabang when logged in as `admin-cabang` or `sales`.

---

## Task 6: Branch Sales Through Salesman

Add branch-sales behavior while reusing the Penjualan UI where practical.

- [ ] Create `BranchPenjualanManager` for `sale_channel = branch`.
- [ ] Create `BranchPenjualanRequest` or extend the existing request carefully without allowing sales users into warehouse sale rules.
- [ ] Update route access so `sales` and `admin-cabang` can use `penjualan.index/create/show/print` in branch mode.
- [ ] For branch mode, force:
  - `sale_channel = branch`
  - `outlet_id = authenticated user's cabang`
  - `salesman_id = authenticated salesman's id` for role `sales`
  - `buyer_type = toko`
  - `buyer_id = selected customer/toko`
- [ ] Product list must come from `OwnerStock` for the user's cabang, not warehouse `Stock`.
- [ ] Allocate branch stock FIFO by expiry/id from `OwnerStock`.
- [ ] Create `PenjualanItem` rows and `PenjualanItemAllocation` rows with `owner_stock_id`.
- [ ] Decrease `OwnerStock.qty` by allocated qty.
- [ ] Create `owner_stock_movements` with `type = out`.
- [ ] On update, rollback old branch allocations before applying the new items.
- [ ] On delete, either block deletes like warehouse sales or implement safe rollback with audit. Prefer blocking deletes and using correction flows.
- [ ] Update `PenjualanController::index()` filters:
  - warehouse roles see warehouse sales.
  - `admin-cabang` sees branch sales for own cabang.
  - `sales` sees branch sales created by self or assigned to their salesman record.
- [ ] Update form labels for branch mode: "Customer/Toko" instead of "Jenis Pembeli".
- [ ] Add customer/toko management if current `outlets` CRUD cannot cleanly filter `jenis_outlet = toko`.

Acceptance checks:

- [ ] Sales user can create sale to customer/toko using only own cabang stock.
- [ ] Stock is reduced from `OwnerStock`, not warehouse `stocks`.
- [ ] Sales user cannot see or allocate products unavailable in own cabang.
- [ ] Admin cabang can manage all sales in own cabang.

---

## Task 7: Retur Penjualan Cabang

Use the same return page and `SalesReturnManager`, but in branch-customer mode.

- [ ] Add branch return link/sidebar for `sales` and `admin-cabang`.
- [ ] Force return scope to `branch_customer_return` for branch-scoped users.
- [ ] Customer/toko selector must be scoped to customers used by or assigned to that cabang.
- [ ] Latest unpaid invoice lookup must be branch-scoped and customer-scoped.
- [ ] Return total must reduce that latest unpaid branch invoice and keep total `> 0`.
- [ ] Returned stock must increase the same cabang's `OwnerStock`.
- [ ] Kartu stock must show branch return as stock in.
- [ ] Refund index/show should display responsible salesman/admin cabang and applied invoice.

Acceptance checks:

- [ ] Branch return increases cabang stock.
- [ ] Branch return does not modify warehouse stock.
- [ ] Branch return cannot reduce another cabang/customer invoice.
- [ ] Branch return cannot make latest invoice zero or negative.

---

## Task 8: Reports, Printing, and Invoice Display

- [ ] Update `penjualan.show`, print, and surat jalan/invoice views to show invoice adjustments from returns.
- [ ] Display original item subtotal, return adjustment total, and final invoice total clearly.
- [ ] Update refund index/show to show applied invoice instead of selected source invoice.
- [ ] Add filters to reports for `sale_channel = warehouse` vs `branch`.
- [ ] Add branch stock movement report/export if existing laporan index expects stock-card exports.
- [ ] Ensure hidden agent/canvas return stock audit does not appear in stock cabang reports.

Acceptance checks:

- [ ] Invoice total in list, show, print, and payment edit is consistent after return adjustment.
- [ ] Refund detail shows all returned products and the invoice that was reduced.
- [ ] Branch stock reports reconcile with branch stock page.

---

## Task 9: Tests and Manual QA

Automated tests to add:

- [ ] Warehouse buyer return reduces latest unpaid agent invoice.
- [ ] Warehouse buyer return can include product not present on latest unpaid invoice.
- [ ] Warehouse buyer return is blocked when total would make invoice `<= 0`.
- [ ] Warehouse buyer return does not mutate `stocks` or `owner_stocks`.
- [ ] Branch sale by sales user reduces only assigned cabang `OwnerStock`.
- [ ] Branch sale by sales user cannot access another cabang stock.
- [ ] Branch customer return reduces latest unpaid branch invoice and increases cabang `OwnerStock`.
- [ ] Return edit/destroy rollback restores invoice total and stock movement.
- [ ] Admin cabang can manage own cabang but not another cabang.

Manual QA:

- [ ] Create warehouse sale to cabang and confirm cabang stock appears in `/branch-stock`.
- [ ] Create a return for warehouse sale buyer type `outlet`; confirm latest unpaid cabang invoice is reduced, cabang stock decreases, and warehouse stock increases.
- [ ] Login as sales, create branch sale to customer/toko, and confirm cabang stock decreases.
- [ ] Create several unpaid invoices for same agent/canvas; create one global return and confirm only the latest unpaid invoice is reduced.
- [ ] Create several unpaid invoices for same toko/customer in one cabang; create branch return and confirm only latest unpaid branch invoice is reduced.
- [ ] Confirm final invoice total cannot become zero.
- [ ] Confirm branch stock card shows warehouse sale in, branch sale out, branch return in, and opname adjustment.

---

## Implementation Order

1. Database/model foundation and owner-stock column normalization.
2. Access-control helpers and `admin-cabang` role.
3. Branch stock movement ledger and branch stock pages.
4. Warehouse global `Retur Penjualan` without branch returns.
5. Branch sales through sales/admin-cabang.
6. Branch customer returns.
7. Reports, printing, and cleanup.
8. Tests and manual QA.

This order keeps stock-ledger correctness ahead of branch sales/returns and prevents return work from depending on incomplete branch stock pages.

---

## Open Decisions Before Coding

- Confirm whether sales customer/toko should be stored as `outlets.jenis_outlet = toko` or a new dedicated table. The recommended path is `outlets` with a new `shops()` scope because the project already models outlet types.
- Confirm whether partial invoices should also be eligible for return adjustment. The current requirement says latest unpaid invoice, so the plan uses `payment_status = unpaid` only.
- Confirm whether return price can be manually overridden when no prior buyer/product price exists. The recommended path is to allow override but record the entered price on `refund_items`.
- Confirm whether delete/edit of returns should be allowed after payments are recorded on the adjusted invoice. The safer default is to block edits/deletes once payment history exists after the return.
- Confirm whether `RefundPembelian` `outlet_ke_gudang` should remain as a pure stock-return flow or be deprecated after `warehouse_branch_return` handles cabang invoice credit plus stock reversal. The safer first step is to keep it and label the difference clearly.
