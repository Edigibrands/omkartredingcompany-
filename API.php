<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';

function out(bool $ok, string $message = '', $data = null, int $status = 200): never {
    http_response_code($status);
    echo json_encode(['success'=>$ok, 'message'=>$message, 'data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}
function body(): array {
    $raw = file_get_contents('php://input');
    $v = json_decode($raw ?: '{}', true);
    return is_array($v) ? $v : [];
}
function auth(): void {
    if (empty($_SESSION['user'])) out(false, 'Please log in to continue.', null, 401);
}
function clean($v): string { return trim((string)($v ?? '')); }
function num($v): float {
    if (!is_numeric($v) || (float)$v < 0) out(false, 'Invalid amount or quantity.', null, 422);
    return round((float)$v, 3);
}
function nextNo(PDO $pdo, string $prefix, string $table): string {
    $year = date('y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table` WHERE DATE_FORMAT(created_at,'%y') = " . $pdo->quote($year));
    return $prefix . $year . '-' . str_pad((string)((int)$stmt->fetchColumn()+1), 4, '0', STR_PAD_LEFT);
}
function stockMove(PDO $pdo, int $productId, string $type, float $qty, string $ref, string $note=''): void {
    $s = $pdo->prepare("SELECT id, name, stock_qty FROM products WHERE id=? FOR UPDATE");
    $s->execute([$productId]); $p = $s->fetch();
    if (!$p) throw new RuntimeException('Product not found.');
    $new = (float)$p['stock_qty'] + ($type === 'IN' ? $qty : -$qty);
    if ($new < -0.0001) throw new RuntimeException('Insufficient stock for ' . $p['name'] . '. Available: ' . $p['stock_qty']);
    $pdo->prepare("UPDATE products SET stock_qty=? WHERE id=?")->execute([max(0,$new),$productId]);
    $pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_no,note,created_by) VALUES(?,?,?,?,?,?)")
        ->execute([$productId,$type,$qty,$ref,$note,$_SESSION['user']['id'] ?? null]);
}
$action = $_GET['action'] ?? '';
$input = body();

try {
    if ($action === 'login') {
        $username = clean($input['username'] ?? '');
        $password = (string)($input['password'] ?? '');
        $s = $pdo->prepare("SELECT * FROM users WHERE username=? AND is_active=1");
        $s->execute([$username]); $u = $s->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) out(false,'Invalid username or password.',null,401);
        $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'role'=>$u['role']];
        out(true,'Welcome back.',$_SESSION['user']);
    }
    if ($action === 'logout') { session_destroy(); out(true,'Logged out.'); }
    if ($action === 'me') out(true,'Session',$_SESSION['user'] ?? null);
    auth();

    if ($action === 'dashboard') {
        $stats = [
            'products'=>(int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn(),
            'stock_value'=>(float)$pdo->query("SELECT COALESCE(SUM(stock_qty*purchase_rate),0) FROM products")->fetchColumn(),
            'purchase_month'=>(float)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM purchases WHERE MONTH(purchase_date)=MONTH(CURDATE()) AND YEAR(purchase_date)=YEAR(CURDATE())")->fetchColumn(),
            'sales_month'=>(float)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM sales WHERE MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())")->fetchColumn(),
            'low_stock'=>(int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_qty<=reorder_level")->fetchColumn(),
            'quotes_pending'=>(int)$pdo->query("SELECT COUNT(*) FROM quotations WHERE status='Pending'")->fetchColumn()
        ];
        $recent = $pdo->query("SELECT 'Sale' type, invoice_no doc_no, customer_name party, grand_total, sale_date doc_date FROM sales UNION ALL SELECT 'Purchase',purchase_no,supplier_name,grand_total,purchase_date FROM purchases ORDER BY doc_date DESC LIMIT 8")->fetchAll();
        $low = $pdo->query("SELECT name,sku,stock_qty,unit,reorder_level FROM products WHERE is_active=1 AND stock_qty<=reorder_level ORDER BY stock_qty ASC LIMIT 8")->fetchAll();
        out(true,'Dashboard loaded.',['stats'=>$stats,'recent'=>$recent,'low_stock'=>$low]);
    }
    if ($action === 'products') {
        $rows = $pdo->query("SELECT p.*,c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 ORDER BY p.name")->fetchAll();
        out(true,'Products loaded.',$rows);
    }
    if ($action === 'save_product') {
        $name=clean($input['name']??''); if(!$name) out(false,'Product name is required.',null,422);
        $sku=clean($input['sku']??''); if(!$sku) $sku='OT-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));
        $id=(int)($input['id']??0);
        $params=[$sku,$name,(int)($input['category_id']??0) ?: null,clean($input['unit']??'Piece'),num($input['purchase_rate']??0),num($input['selling_rate']??0),num($input['reorder_level']??0)];
        if($id) {
            $pdo->prepare("UPDATE products SET sku=?,name=?,category_id=?,unit=?,purchase_rate=?,selling_rate=?,reorder_level=? WHERE id=?")->execute([...$params,$id]);
        } else {
            $pdo->prepare("INSERT INTO products(sku,name,category_id,unit,purchase_rate,selling_rate,reorder_level,stock_qty) VALUES(?,?,?,?,?,?,?,0)")->execute($params);
        }
        out(true,'Product saved.');
    }
    if ($action === 'categories') out(true,'Categories',$pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll());
    if ($action === 'parties') {
        $type=clean($_GET['type']??'Customer');
        $s=$pdo->prepare("SELECT * FROM parties WHERE party_type=? AND is_active=1 ORDER BY name"); $s->execute([$type]);
        out(true,'Parties',$s->fetchAll());
    }
    if ($action === 'save_party') {
        $name=clean($input['name']??''); if(!$name) out(false,'Name is required.',null,422);
        $pdo->prepare("INSERT INTO parties(party_type,name,phone,email,address,gst_number) VALUES(?,?,?,?,?,?)")
            ->execute([clean($input['party_type']??'Customer'),$name,clean($input['phone']??''),clean($input['email']??''),clean($input['address']??''),clean($input['gst_number']??'')]);
        out(true,'Party saved.');
    }
    if ($action === 'purchase') {
        $items=$input['items']??[]; if(!is_array($items)||!count($items)) out(false,'Add at least one purchase item.',null,422);
        $pdo->beginTransaction();
        $no=nextNo($pdo,'PUR','purchases'); $subtotal=0;
        foreach($items as $it) $subtotal += num($it['qty']??0)*num($it['rate']??0);
        $discount=num($input['discount']??0); $taxMode=clean($input['tax_mode']??'Non-GST'); $taxRate=$taxMode==='GST'?num($input['tax_rate']??18):0; $tax=round(max(0,$subtotal-$discount)*$taxRate/100,2); $total=max(0,$subtotal+$tax-$discount);
        $pdo->prepare("INSERT INTO purchases(purchase_no,supplier_name,supplier_phone,purchase_date,subtotal,tax_amount,tax_mode,tax_rate,discount,grand_total,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$no,clean($input['supplier_name']??'Walk-in Supplier'),clean($input['supplier_phone']??''),clean($input['date']??date('Y-m-d')),$subtotal,$tax,$taxMode,$taxRate,$discount,$total,clean($input['notes']??''),$_SESSION['user']['id']]);
        $pid=(int)$pdo->lastInsertId();
        foreach($items as $it) {
            $product=(int)($it['product_id']??0); $qty=num($it['qty']??0); $rate=num($it['rate']??0);
            $pdo->prepare("INSERT INTO purchase_items(purchase_id,product_id,quantity,rate,line_total) VALUES(?,?,?,?,?)")->execute([$pid,$product,$qty,$rate,$qty*$rate]);
            $pdo->prepare("UPDATE products SET purchase_rate=? WHERE id=?")->execute([$rate,$product]);
            stockMove($pdo,$product,'IN',$qty,$no,'Purchase entry');
        }
        $pdo->commit(); out(true,'Purchase saved and stock updated.',['id'=>$pid,'document_no'=>$no,'grand_total'=>$total]);
    }
    if ($action === 'sale') {
        $items=$input['items']??[]; if(!is_array($items)||!count($items)) out(false,'Add at least one sale item.',null,422);
        $pdo->beginTransaction();
        $no=nextNo($pdo,'INV','sales'); $subtotal=0;
        foreach($items as $it) $subtotal += num($it['qty']??0)*num($it['rate']??0);
        $labour=num($input['labour']??0); $other=num($input['other']??0); $discount=num($input['discount']??0);
        $taxMode=clean($input['tax_mode']??'Non-GST'); $taxRate=$taxMode==='GST'?num($input['tax_rate']??18):0;
        $tax=round(max(0,$subtotal+$labour+$other-$discount)*$taxRate/100,2);
        $total=max(0,$subtotal+$labour+$other+$tax-$discount);
        $pdo->prepare("INSERT INTO sales(invoice_no,customer_name,customer_phone,customer_address,sale_date,subtotal,labour_charges,other_charges,tax_amount,tax_mode,tax_rate,discount,grand_total,payment_status,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$no,clean($input['customer_name']??'Walk-in Customer'),clean($input['customer_phone']??''),clean($input['customer_address']??''),clean($input['date']??date('Y-m-d')),$subtotal,$labour,$other,$tax,$taxMode,$taxRate,$discount,$total,clean($input['payment_status']??'Unpaid'),clean($input['notes']??''),$_SESSION['user']['id']]);
        $sid=(int)$pdo->lastInsertId();
        foreach($items as $it) {
            $product=(int)($it['product_id']??0); $qty=num($it['qty']??0); $rate=num($it['rate']??0);
            $pdo->prepare("INSERT INTO sale_items(sale_id,product_id,quantity,rate,line_total) VALUES(?,?,?,?,?)")->execute([$sid,$product,$qty,$rate,$qty*$rate]);
            stockMove($pdo,$product,'OUT',$qty,$no,'Sales invoice');
        }
        $pdo->commit(); out(true,'Sale saved and stock deducted.',['id'=>$sid,'document_no'=>$no,'grand_total'=>$total]);
    }
    if ($action === 'quotation') {
        $items=$input['items']??[]; if(!is_array($items)||!count($items)) out(false,'Add at least one quotation item.',null,422);
        $subtotal=0; foreach($items as $it) $subtotal+=num($it['qty']??0)*num($it['rate']??0);
        $labour=num($input['labour']??0); $other=num($input['other']??0); $discount=num($input['discount']??0);
        $taxMode=clean($input['tax_mode']??'Non-GST'); $taxRate=$taxMode==='GST'?num($input['tax_rate']??18):0;
        $tax=round(max(0,$subtotal+$labour+$other-$discount)*$taxRate/100,2);
        $total=max(0,$subtotal+$labour+$other+$tax-$discount);
        $no=nextNo($pdo,'QUO','quotations');
        $pdo->prepare("INSERT INTO quotations(quotation_no,customer_name,customer_phone,customer_address,quote_date,valid_until,subtotal,labour_charges,other_charges,tax_amount,tax_mode,tax_rate,discount,grand_total,status,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$no,clean($input['customer_name']??''),clean($input['customer_phone']??''),clean($input['customer_address']??''),clean($input['date']??date('Y-m-d')),clean($input['valid_until']??date('Y-m-d',strtotime('+15 days'))),$subtotal,$labour,$other,$tax,$taxMode,$taxRate,$discount,$total,'Pending',clean($input['notes']??''),$_SESSION['user']['id']]);
        $qid=(int)$pdo->lastInsertId();
        foreach($items as $it) $pdo->prepare("INSERT INTO quotation_items(quotation_id,description,quantity,unit,rate,line_total) VALUES(?,?,?,?,?,?)")->execute([$qid,clean($it['description']??''),num($it['qty']??0),clean($it['unit']??'Piece'),num($it['rate']??0),num($it['qty']??0)*num($it['rate']??0)]);
        out(true,'Quotation created.',['id'=>$qid,'document_no'=>$no,'grand_total'=>$total]);
    }
    if ($action === 'manufacture') {
        $items=$input['items']??[]; $finished=(int)($input['finished_product_id']??0); $output=num($input['output_quantity']??0);
        if(!$finished || !is_array($items) || !count($items)) out(false,'Select a finished product and at least one raw material.',null,422);
        $pdo->beginTransaction();
        $no=nextNo($pdo,'MFG','production_batches'); $materialCost=0;
        foreach($items as $it) $materialCost += num($it['qty']??0)*num($it['rate']??0);
        $labour=num($input['labour_cost']??0); $other=num($input['other_cost']??0); $total=$materialCost+$labour+$other;
        $pdo->prepare("INSERT INTO production_batches(batch_no,finished_product_id,output_quantity,labour_cost,other_cost,total_material_cost,total_cost,production_date,notes,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)")
            ->execute([$no,$finished,$output,$labour,$other,$materialCost,$total,clean($input['date']??date('Y-m-d')),clean($input['notes']??''),$_SESSION['user']['id']]);
        $batch=(int)$pdo->lastInsertId();
        foreach($items as $it) {
            $raw=(int)($it['product_id']??0); $qty=num($it['qty']??0); $rate=num($it['rate']??0);
            $pdo->prepare("INSERT INTO production_items(batch_id,raw_product_id,quantity_used,unit_cost,line_cost) VALUES(?,?,?,?,?)")->execute([$batch,$raw,$qty,$rate,$qty*$rate]);
            stockMove($pdo,$raw,'OUT',$qty,$no,'Raw material used in manufacturing');
        }
        stockMove($pdo,$finished,'IN',$output,$no,'Finished goods produced');
        $pdo->prepare("UPDATE products SET purchase_rate=? WHERE id=?")->execute([round($total/$output,2),$finished]);
        $pdo->commit(); out(true,'Production completed. Raw material deducted and finished stock added.',['id'=>$batch,'document_no'=>$no,'total_cost'=>$total,'unit_cost'=>round($total/$output,2)]);
    }
    if ($action === 'production_history') {
        $rows=$pdo->query("SELECT pb.*,p.name finished_product FROM production_batches pb JOIN products p ON p.id=pb.finished_product_id ORDER BY pb.id DESC LIMIT 100")->fetchAll();
        out(true,'Production history',$rows);
    }
    if ($action === 'list_docs') {
        $type=clean($_GET['type']??'sales');
        $map=['sales'=>['sales','invoice_no','sale_date'],'purchase'=>['purchases','purchase_no','purchase_date'],'quotation'=>['quotations','quotation_no','quote_date']];
        if(!isset($map[$type])) out(false,'Invalid document type.',null,422);
        [$table,$no,$date]=$map[$type];
        out(true,'Documents',$pdo->query("SELECT id,`$no` document_no,".($type==='purchase'?'supplier_name':'customer_name')." party_name,grand_total,`$date` document_date".($type==='sales'?",payment_status":($type==='quotation'?",status":""))." FROM `$table` ORDER BY id DESC LIMIT 100")->fetchAll());
    }
    if ($action === 'stock_movements') {
        $rows=$pdo->query("SELECT sm.*,p.name product_name,p.sku,p.unit FROM stock_movements sm JOIN products p ON p.id=sm.product_id ORDER BY sm.id DESC LIMIT 150")->fetchAll();
        out(true,'Stock movement history',$rows);
    }
    if ($action === 'update_quote_status') {
        $id=(int)($input['id']??0); $status=clean($input['status']??'Pending');
        if(!in_array($status,['Pending','Accepted','Rejected','Converted'],true)) out(false,'Invalid status.',null,422);
        $pdo->prepare("UPDATE quotations SET status=? WHERE id=?")->execute([$status,$id]); out(true,'Quotation status updated.');
    }
    if ($action === 'report') {
        $sales=$pdo->query("SELECT DATE_FORMAT(sale_date,'%Y-%m') month, SUM(grand_total) total FROM sales GROUP BY DATE_FORMAT(sale_date,'%Y-%m') ORDER BY month DESC LIMIT 12")->fetchAll();
        $purchases=$pdo->query("SELECT DATE_FORMAT(purchase_date,'%Y-%m') month, SUM(grand_total) total FROM purchases GROUP BY DATE_FORMAT(purchase_date,'%Y-%m') ORDER BY month DESC LIMIT 12")->fetchAll();
        out(true,'Reports',['sales'=>$sales,'purchases'=>$purchases]);
    }
    out(false,'Unknown action.',null,404);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(false, $e instanceof PDOException ? 'Database operation failed. Check duplicate SKU or database setup.' : $e->getMessage(), null, 500);
}
