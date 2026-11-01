# Omkar Trading – Furniture Business Management Demo

A PHP + MySQL demo for furniture raw-material trading and finished-furniture sales/manufacturing.

## Included in this demo

- Dashboard with product count, stock valuation, month purchase/sales, low-stock count and pending quotations
- Product catalogue and categories (foam, fabric, steel, wood, accessories, finished furniture)
- Purchase entry: increases raw-material or finished-product stock
- Sales invoice: deducts stock and blocks sales when stock is insufficient
- Quotations: estimate-only; does not change stock
- Manufacturing batch: deducts selected raw materials, adds finished-goods stock and calculates an estimated unit cost including labour/other costs
- Customer and supplier contact list
- Stock movement audit trail
- Monthly sales and purchase summaries
- GST / Non-GST mode with selectable GST rate; invoice/quotation opens a print page that can be saved as PDF using the browser's Print > Save as PDF
- Demo admin and sales logins

## Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- PHP extensions: PDO and pdo_mysql
- Apache (XAMPP/WAMP) or PHP's built-in development server

## Run with XAMPP

1. Copy the `omkar-trading-demo` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL from XAMPP.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Import `database/schema.sql`. It creates the `omkar_trading` database and demo data.
5. Confirm the credentials in `config/database.php` (default XAMPP values: user `root`, blank password).
6. Open `http://localhost/omkar-trading-demo/`.

## Demo accounts

- Admin: `admin` / `password`
- Sales: `sales` / `password`

Change these credentials before using the application with real business data. The sample records are fictional.

## Run with PHP built-in server

From this folder:

```bash
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000`.

The MySQL database must still be created and imported first.

## Important demo limitations – do not treat this as production software yet

1. This is a working MVP/demo, not a production-hardened ERP.
2. GST is calculated using a selected percentage and shown as one GST amount. Configure the correct GST rates, HSN/SAC, place-of-supply rules, CGST/SGST split or IGST treatment with the accountant before issuing legally compliant tax invoices.
3. Invoice/quotation PDF is generated through the browser print dialog (choose Save as PDF); server-side PDF storage/email/WhatsApp delivery is not included.
4. Multiple accounts are seeded, but full role-based permission management, user creation, password reset and activity audit are not yet implemented.
5. Manufacturing uses manually entered material quantities per batch. Reusable BOM/recipe templates, wastage, returns, work orders and detailed production planning are future enhancements.
6. There is no payment collection ledger, supplier/customer outstanding reconciliation, purchase returns, sales returns, barcode support or backup scheduler yet.
7. Sample company identity/address/GSTIN/signature are not configured. Add verified legal business details before real invoicing.
8. Do not deploy publicly without HTTPS, strong credentials, CSRF protection, stricter authorization, rate limiting, backups and security review.

## Core workflow to demonstrate

1. Add or select a raw material in Inventory.
2. Record a Purchase. The available quantity increases.
3. Create a Manufacturing Batch. Raw materials are deducted and finished furniture stock increases.
4. Create a Sales Invoice. Finished product stock decreases.
5. Create a Quotation. It does not change stock. Print and save the quotation as PDF.
6. Review Stock Movements to show the complete audit trail.

## File structure

```text
omkar-trading-demo/
├── index.php
├── API.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
└── README.md
```
