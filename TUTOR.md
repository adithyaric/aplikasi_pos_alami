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
- `admin-cabang`: manage its own cabang stock, return cabang stock to warehouse, create branch sales to toko/customer, and create customer sales returns.
- `sales`: create branch sales to toko/customer and create customer sales returns for its own cabang.
- `admin-cabang` and `sales` are intentionally blocked from warehouse `Pembelian` routes.

## Seeded Master Data

- Supplier: `Pabrik ALAMI`.
- Products: `ALAMI Kretek Original`, `ALAMI Menthol`, `ALAMI Slim`, `ALAMI Bold`.
- Buyers from warehouse: 4 agents, 2 canvases, and 2 cabang.
- Branch customers: `Toko Sembako Malioboro` and `Toko Retail Wates`.
- Stock: warehouse stock is seeded from one completed pembelian; cabang stock is seeded from warehouse sales to cabang.
- Transactions: 3 warehouse sales and 1 branch sale are seeded to make return flows testable immediately.

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

Expected result: the system applies the return to the latest unpaid invoice for that buyer. The invoice total is reduced, but the return is blocked if it would make the invoice total zero or negative.

Stock behavior:

- Agent/canvas returns are recorded as hidden stock history and do not mutate warehouse stock.
- Cabang/outlet returns reduce cabang stock and restore warehouse stock.

## Flow 5: Admin Cabang Stock and Returns

Login as `alfreda.branch@alami.test`.

1. Open `/branch-stock`.
2. Check available stock for the related cabang only.
3. Open `/branch-stock/kartu` for stock movement history.
4. Open `/branch-stock/opname` for branch stock opname.
5. Open `/refundPembelian?type=outlet_ke_gudang` to return cabang stock to warehouse.

Expected result: admin cabang can only manage its own cabang. Cabang-to-warehouse returns reduce branch stock and increase warehouse stock.

## Flow 6: Penjualan Cabang by Admin Cabang

Login as `alfreda.branch@alami.test`.

1. Open `/penjualan`.
2. Create a sale.
3. Buyer is locked to toko/customer.
4. Select `Toko Sembako Malioboro`.
5. Add products from the cabang stock.
6. Save as `Termin` if you want to test `Retur Penjualan`.

Expected result: branch stock decreases and a branch customer invoice is created.

## Flow 7: Penjualan Cabang by Salesman

Login as `sales-jogja-1@alami.test`.

1. Open `/penjualan`.
2. Create a sale to `Toko Sembako Malioboro`.
3. Add products from the related cabang stock.
4. Save.

Expected result: the sale is assigned to the linked salesman and only uses stock from that salesman's cabang.

## Flow 8: Retur Penjualan Cabang from Toko

Login as `alfreda.branch@alami.test` or `sales-jogja-1@alami.test`.

1. Open `/refund`.
2. Create a return.
3. Buyer is toko/customer.
4. Select `Toko Sembako Malioboro`.
5. Add returned products and qty.
6. Save.

Expected result: the return is applied to the latest unpaid branch invoice for that toko. The invoice total is reduced but cannot become zero or negative. Returned product stock is added back to the related cabang stock.

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
