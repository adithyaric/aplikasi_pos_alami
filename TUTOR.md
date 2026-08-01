# TUTOR.md

## Reset Demo Data

Use this when you want a clean demo database:

```bash
php artisan migrate:fresh --seed
```

All demo passwords are:

```text
password
```

## Demo Login

Warehouse roles:

- `superadmin@mailinator.com` / `password`: full admin access.
- `admin-gudang@alami.test` / `password`: warehouse operational admin.
- `owner@alami.test` / `password`: owner/review role. Owner can view/approve where supported, but should not create warehouse pembelian.

Branch roles:

- `alfreda.branch@alami.test` / `password`: admin cabang for Cabang ALAMI AREA JOGJA KOTA.
- `rina.branch@alami.test` / `password`: admin cabang for Cabang ALAMI AREA KULON PROGO.
- `sales-jogja-1@alami.test` / `password`: salesman linked to Cabang ALAMI AREA JOGJA KOTA.
- `sales-kp-1@alami.test` / `password`: salesman linked to Cabang ALAMI AREA KULON PROGO.

More seeded sales users exist: `sales-jogja-2`, `sales-jogja-3`, `sales-kp-2`, and `sales-kp-3`, all using `@alami.test`.

## Role Boundaries

- `superadmin` and `admin-gudang`: create `Pembelian`, receive stock, create supplier purchase returns, sell from warehouse to agent/canvas/cabang, and create global `Retur Penjualan`.
- `owner`: review/admin visibility; do not use this role for creating `Pembelian`.
- `admin-cabang`: focus on branch sales operations, monitor branch sales created by sales, create customer sales returns for its own cabang, and request branch stock returns back to warehouse from the sales return menu.
- `sales`: focus on branch sales operations, create branch sales to toko/customer, and create customer sales returns for its own cabang.
- `admin-cabang` and `sales` are intentionally blocked from warehouse `Pembelian` routes.

## Seeded Master Data

- Supplier: `Pabrik ALAMI`.
- Products: `ALAMI Kretek Original`, `ALAMI Menthol`, `ALAMI Slim`, `ALAMI Bold`.
- Buyers from warehouse: 4 agents, 2 canvases, and 2 cabang.
- Branch customers: `Toko Sembako Malioboro` and `Toko Retail Wates`.
- Stock: warehouse stock is seeded from one completed pembelian; cabang stock is seeded from warehouse sales to cabang.
- Transactions: 3 warehouse sales and 1 branch sale are seeded to make return flows testable immediately. Seeded branch invoice numbering uses the `INV-CBG-xxxxx` prefix.

## Flow 1: Pembelian Gudang

Login as `admin-gudang@alami.test`.

1. Open `/pembelian`.
2. Click create.
3. Select supplier `Pabrik ALAMI`.
4. Add product rows, for example `ALAMI Menthol`, qty `1`, unit `Slop`.
5. Save the PO.
6. Open `/penerimaan` or the PO penerimaan page.
7. Fill received qty and set receipt status to `completed`.
8. Check `/stock` or `/stock-kartu`.

Expected result: warehouse stock increases after penerimaan is completed.

## Flow 2: Retur Pembelian Gudang ke Supplier

Login as `admin-gudang@alami.test`.

1. Open `/refundPembelian?type=gudang_ke_supplier`.
2. Create a return.
3. Select supplier `Pabrik ALAMI`.
4. Select products from warehouse stock.
5. Choose return mode:
   - `replacement`: records the return as complete history without reducing stock.
   - `cash_refund`: reduces stock first and leaves status as `retur`.
6. For `cash_refund`, open the return detail and use `Terima Retur`.
7. Choose resolution `barang` when replacement goods are received back.

Expected result: supplier return history is stored. Cash refund flow can reduce stock and then complete the receiving step.

## Flow 3: Penjualan Gudang

Login as `admin-gudang@alami.test` or `superadmin@mailinator.com`.

1. Open `/penjualan`.
2. Create a sale.
3. Choose buyer type:
   - `Agen`: stock leaves warehouse only.
   - `Canvas`: stock leaves warehouse only.
   - `Cabang`: stock leaves warehouse and creates/updates cabang stock.
4. Add products and qty.
5. Choose `Cash` or `Termin`.
6. Save.

Expected result: warehouse stock is reduced. If buyer type is cabang/outlet, `owner_stocks` for that cabang is increased.

## Flow 4: Retur Penjualan Global

Login as `admin-gudang@alami.test` or `superadmin@mailinator.com`.

1. Open `/refund`.
2. Create a return without selecting a specific invoice.
3. Choose buyer type `Agen`, `Canvas`, or `Cabang`.
4. Select the buyer and product return rows.
5. Save.

Expected result: the system applies the return to the latest invoice for that buyer whose status is not `paid`. The invoice total is reduced, but the return is blocked if it would make the invoice total zero or negative.

Important note: the "latest invoice" rule means the latest invoice whose status is not `paid`, so `partial` invoices are also valid return targets.

Stock behavior:

- Agent/canvas returns are recorded as hidden stock history and do not mutate warehouse stock.
- Cabang/outlet returns reduce cabang stock and restore warehouse stock.

## Flow 5: Admin Cabang Stock and Sales Support

Login as `alfreda.branch@alami.test`.

1. Open `/branch-stock`.
2. Check available stock for the related cabang only.
3. Open `/branch-stock/kartu` for stock movement history.
4. Open `/branch-stock/opname` for branch stock opname.
5. Open `/refund?return_scope=warehouse_branch_return` only when branch stock must be sent back to warehouse.

Expected result: admin cabang can only manage its own cabang. The role stays centered on branch sales and branch stock. Branch-to-warehouse return requests stay in `Retur Penjualan` and wait for superadmin confirmation.

## Flow 6: Penjualan Cabang by Admin Cabang

Login as `alfreda.branch@alami.test`.

1. Open `/penjualan/cabang`.
2. Check the branch sales list for that cabang.
3. Use `Retur Penjualan` when toko/customer goods are returned to the cabang.
4. Use `Retur Cabang ke Gudang` only when the cabang needs to send stock back to warehouse.

Expected result: admin cabang monitors branch sales and handles branch-side return decisions, but does not create branch sales invoices directly.

## Flow 7: Penjualan Cabang by Salesman

Login as `sales-jogja-1@alami.test`.

1. Open `/penjualan/cabang`.
2. Create a sale to `Toko Sembako Malioboro`.
3. Add products from the related cabang stock.
4. If the toko is not available yet, use the `Tambah Customer/Toko` pop-up on the branch sales flow.
5. Save.

Expected result: the sale is assigned to the linked salesman, only uses stock from that salesman's cabang, and the invoice number uses the `INV-CBG-xxxxx` format.

## Flow 8: Retur Penjualan Cabang from Toko

Login as `alfreda.branch@alami.test` or `sales-jogja-1@alami.test`.

1. Open `/refund`.
2. Create a return.
3. Buyer is toko/customer.
4. Select `Toko Sembako Malioboro`.
5. Add returned products and qty.
6. Save.

Expected result: the return is applied to the latest unpaid branch invoice for that toko. The invoice total is reduced but cannot become zero or negative. Returned product stock is added back to the related cabang stock.

## Practical Return Examples

### Example 1: Salesman handles toko return

Login as `sales-jogja-1@alami.test`.

1. Salesman creates a branch sale to `Toko Sembako Malioboro`.
2. The toko returns part of the goods.
3. Salesman opens `/refund`.
4. Salesman creates `Retur Penjualan` for that toko.

Result:

- The latest branch invoice for that toko is reduced.
- Branch stock goes back up.
- The return stays in the branch sales flow.

### Example 2: Admin cabang returns branch stock to warehouse

Login as `alfreda.branch@alami.test`.

1. Branch already received stock from warehouse through a warehouse sale to cabang.
2. Some stock is not suitable to keep in branch stock.
3. Admin cabang opens `/refund?return_scope=warehouse_branch_return`.
4. Admin cabang creates `Retur Cabang ke Gudang`.

Result:

- The return request is saved with status `Pending Superadmin`.
- Branch stock is not reduced yet.
- Warehouse stock is not increased yet.
- Branch invoice is not cut yet.
- This remains a sales return flow, not `refundPembelian`.

### Example 3: Superadmin or admin gudang handles warehouse-side return

Login as `superadmin@mailinator.com` or `admin-gudang@alami.test`.

Case A: superadmin confirms branch return to warehouse

1. Login as `superadmin@mailinator.com`.
2. Open `/refund?return_scope=warehouse_branch_return`.
3. Open the pending `Retur Cabang ke Gudang`.
4. Click the superadmin confirmation action.

Result:

- Branch stock is reduced.
- Warehouse stock is increased.
- The latest unpaid branch warehouse invoice is reduced.
- The request moves from `Pending Superadmin` to `Approved`.

Case B: warehouse creates direct sales return for agent/canvas/cabang invoice

1. Login as `superadmin@mailinator.com` or `admin-gudang@alami.test`.
2. Open `/refund`.
3. Create `Retur Penjualan`.
4. Choose `Agen`, `Canvas`, or `Cabang`.

Result:

- The latest warehouse invoice with status not `paid` is reduced immediately.
- For `Cabang`, branch stock is also reduced and warehouse stock is restored.
- For `Agen` or `Canvas`, stock is not physically moved.

Case C: return from warehouse purchase to supplier

1. Open `/refundPembelian?type=gudang_ke_supplier`.
2. Create the supplier return.
3. If using `cash_refund`, finish the receive/resolve step when the supplier settles it.

Result:

- This stays fully on the warehouse/purchase side.
- Branch users do not need this flow.

## Return Chain Summary

Use this mental model:

1. `Salesman` or `admin-cabang` handles `Retur Penjualan` when toko/customer returns goods to the cabang.
2. `Admin cabang` handles `Retur Cabang ke Gudang` from the same `/refund` menu when branch stock must move back to warehouse.
3. `Superadmin` confirms pending `Retur Cabang ke Gudang`, so branch stock, warehouse stock, and branch invoice are updated at approval time.
4. `Superadmin` or `admin-gudang` handles warehouse-level direct `Retur Penjualan` for agent/canvas/cabang invoices and warehouse `Retur Pembelian` to supplier.

## Automated Test Coverage

The seeded tutorial flows are covered by:

```bash
php artisan test --filter=CurrentDistributionFlowSeederTest
php artisan test --filter=SeededOperationalFlowsTest
php artisan test --filter=BranchSalesAndReturnFlowTest
php artisan test --filter=RefundWarehouseBuyerFlowTest
```

Run the full suite before committing:

```bash
php artisan test
```
