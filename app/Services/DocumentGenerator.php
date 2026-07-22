<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\SelfBilledInvoice;
use Carbon\Carbon;
use Dompdf\Dompdf;

class DocumentGenerator
{
    private function company(): array
    {
        $c = CompanySetting::first();

        return [
            'name' => $c->company_name ?? 'AVSB Roadworks Sdn Bhd',
            'address' => $c->address ?? '',
            'reg_no' => $c->reg_no ?? '',
            'phone' => $c->business_phone ?? '',
            'email' => $c->business_email ?? '',
            'logo_path' => $c->logo_path ?? '',
        ];
    }

    private function embedLogo(string $path): string
    {
        if (empty($path)) {
            return '';
        }
        try {
            $storage = new FileStorageService;
            $data = $storage->get($path);
            if ($data === null) {
                return '';
            }
            $mime = 'image/png';
            if (preg_match('/\.(jpg|jpeg)$/i', $path)) {
                $mime = 'image/jpeg';
            } elseif (preg_match('/\.gif$/i', $path)) {
                $mime = 'image/gif';
            } elseif (preg_match('/\.webp$/i', $path)) {
                $mime = 'image/webp';
            }

            return 'data:'.$mime.';base64,'.base64_encode($data);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function fmt($n): string
    {
        return number_format((float) $n, 2);
    }

    private function render(string $title, array $data, string $accent = '#ca2316', string $clientLabel = 'Client', array $buyerInfo = []): string
    {
        $c = $this->company();
        $logoDataUri = $this->embedLogo($c['logo_path']);
        $itemsHtml = '';
        $i = 0;
        foreach ($data['items'] as $item) {
            $i++;
            $name = ! empty($item['item_name']) ? '<strong>'.htmlspecialchars($item['item_name']).'</strong><br/>' : '';
            $desc = htmlspecialchars($item['description'] ?? $item['name'] ?? '');
            $unit = htmlspecialchars($item['unit'] ?? '');
            $qty = $item['quantity'] ?? $item['qty'] ?? 0;
            $rate = $item['unit_rate'] ?? $item['rate'] ?? $item['unitRate'] ?? 0;
            $total = $item['total'] ?? r2($qty * $rate);
            $itemsHtml .= "<tr>
                <td>{$i}</td>
                <td>{$name}{$desc}</td>
                <td class='r'>{$unit}</td>
                <td class='r'>{$qty}</td>
                <td class='r'>".$this->fmt($rate)."</td>
                <td class='r'>".$this->fmt($total).'</td>
            </tr>';
        }

        $subtotalFmt = $this->fmt($data['subtotal'] ?? 0);
        $sstFmt = $this->fmt($data['sst'] ?? 0);
        $retentionFmt = $this->fmt($data['retention_amount'] ?? $data['retention'] ?? 0);
        $totalFmt = $this->fmt($data['total'] ?? 0);
        $sstRate = $data['sst_rate'] ?? 0;
        $retenRate = $data['retention_pct'] ?? $data['retention_rate'] ?? 0;
        $buyerTin = htmlspecialchars($buyerInfo['tin'] ?? '');
        $buyerRegNo = htmlspecialchars($buyerInfo['reg_no'] ?? '');
        $buyerSstRegNo = htmlspecialchars($buyerInfo['sst_reg_no'] ?? '');
        $buyerAddress = htmlspecialchars($buyerInfo['address'] ?? '');
        $buyerEmail = htmlspecialchars($buyerInfo['email'] ?? '');
        $buyerPhone = htmlspecialchars($buyerInfo['phone'] ?? '');
        $isSelfBilled = $title === 'SELF-BILLED INVOICE';
        $client = htmlspecialchars($data['client'] ?? '');
        // Build Bill To block
        $billToHtml = '';
        $clientLineHtml = "<p><strong>{$clientLabel}:</strong> {$client}</p>";
        if (! $isSelfBilled && ($buyerTin || $buyerRegNo || $buyerSstRegNo || $buyerAddress || $buyerEmail || $buyerPhone)) {
            $billToHtml .= '<div class="bill-to">';
            $billToHtml .= '<h3>Bill To:</h3>';
            $billToHtml .= '<p class="name">'.$client.'</p>';
            if ($buyerAddress) {
                $billToHtml .= '<p class="addr">'.$buyerAddress.'</p>';
            }
            $idsLine = '';
            if ($buyerTin) {
                $idsLine .= '<strong>TIN:</strong> '.$buyerTin;
            }
            if ($buyerRegNo) {
                $idsLine .= ($idsLine ? '  ·  ' : '').'<strong>BRN:</strong> '.$buyerRegNo;
            }
            if ($buyerSstRegNo) {
                $idsLine .= ($idsLine ? '  ·  ' : '').'<strong>SST Reg:</strong> '.$buyerSstRegNo;
            }
            if ($idsLine) {
                $billToHtml .= '<p>'.$idsLine.'</p>';
            }
            $contactLine = '';
            if ($buyerEmail) {
                $contactLine .= '<strong>Email:</strong> '.$buyerEmail;
            }
            if ($buyerPhone) {
                $contactLine .= ($contactLine ? '  ·  ' : '').'<strong>Phone:</strong> '.$buyerPhone;
            }
            if ($contactLine) {
                $billToHtml .= '<p>'.$contactLine.'</p>';
            }
            $billToHtml .= '</div>';
            $clientLineHtml = ''; // Bill To block already shows client name
        }
        $docNumber = htmlspecialchars($data['number'] ?? '');
        $docDate = htmlspecialchars($data['date'] ?? '');
        $docStatus = htmlspecialchars($data['status'] ?? '');
        $docNotes = htmlspecialchars($data['notes'] ?? '');
        $paymentRef = htmlspecialchars($data['payment_reference'] ?? '');
        $companyName = htmlspecialchars($c['name']);
        $companyAddr = htmlspecialchars($c['address']);
        $companyReg = htmlspecialchars($c['reg_no']);
        $companyPhone = htmlspecialchars($c['phone']);
        $companyEmail = htmlspecialchars($c['email']);

        $headerSubtitle = $data['header_subtitle'] ?? '';
        $headerSubtitleHtml = $headerSubtitle ? "<p>{$headerSubtitle}</p>" : '';
        $companyExtraHtml = '';
        if ($companyPhone || $companyEmail) {
            $line = $companyPhone ? "<strong>Tel:</strong> {$companyPhone}" : '';
            if ($companyEmail) {
                $line .= ($line ? ' | ' : '')."<strong>Email:</strong> {$companyEmail}";
            }
            $companyExtraHtml = '<br>'.$line;
        }
        $logoHtml = $logoDataUri ? "<img src=\"{$logoDataUri}\" style=\"height:64px;width:auto;\">" : '';
        $logoDivHtml = $logoHtml ? "<div class=\"header-logo\">{$logoHtml}</div>" : '';

        $extraRows = '';
        if ($title === 'INVOICE' && $paymentRef) {
            $extraRows .= "<tr><td colspan='4' class='r'><strong>Payment Ref:</strong></td><td class='r'>{$paymentRef}</td></tr>";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 20mm 15mm; }
body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11pt; color: #1a1a1a; line-height: 1.4; }
.header { border-bottom: 3px solid {$accent}; padding-bottom: 12px; margin-bottom: 20px; }
.header table { margin: 0 !important; }
.header .company { line-height: 1.2; }
.header .company p { margin: 1px 0; font-size: 10pt; color: #1a1a1a; }
.header-logo img { min-height: 100px; width: auto; display: block; }
.info { display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 10pt; }
.info .left p { margin: 2px 0; }
.info .right p { margin: 2px 0; text-align: right; }
.bill-to { margin-bottom: 12px; }
.bill-to h3 { margin: 0 0 4px 0; font-size: 10pt; color: {$accent}; }
.bill-to p { margin: 1px 0; font-size: 9pt; }
.bill-to .name { font-weight: bold; font-size: 10pt; }
.bill-to .addr { color: #555; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
th { background: {$accent}; color: white; padding: 7px 8px; font-size: 9pt; text-align: left; }
th.r { text-align: right; }
td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 9pt; }
td.r { text-align: right; }
.footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 9pt; color: #555; white-space: pre-line; }
.summary { width: 280px; margin-left: auto; }
.summary td { padding: 4px 8px; border: none; font-size: 10pt; }
.summary .total td { font-weight: bold; font-size: 12pt; border-top: 2px solid #1a1a1a; padding-top: 6px; }
.label { color: #555; }
</style>
</head>
<body>
<div class="header">

    <h2 style="font-size:18pt;margin:0;color:{$accent};letter-spacing:2px;">{$title}</h2>
    <p style="margin:1px 0;font-size:9pt;color:#555;">{$docNumber}</p>
    <table style="width:100%;border:none;margin:0;"><tr>
        <td style="vertical-align:top;border:none;padding:0;">
            <p>
                <strong style="font-size:12pt;color:#ca2316;">{$companyName}</strong><br>
                {$companyAddr}<br>
                <strong>Reg No:</strong> {$companyReg}{$companyExtraHtml}
            </p>
            {$headerSubtitleHtml}
        </td>
        <td style="vertical-align:top;text-align:right;border:none;padding:0 0 0 15px;width:200px;">
            {$logoDivHtml}
        </td>
    </tr></table>
</div>
{$billToHtml}
<div class="info">
    <div class="left">
        {$clientLineHtml}
        <p><strong>Date:</strong> {$docDate}</p>
    </div>
    <div class="right">
        <p><strong>Status:</strong> {$docStatus}</p>
    </div>
</div>
<table>
    <tr><th>#</th><th>Description</th><th class='r'>Unit</th><th class='r'>Qty</th><th class='r'>Rate (RM)</th><th class='r'>Total (RM)</th></tr>
    {$itemsHtml}
</table>
<table class="summary">
    <tr><td class="label">Subtotal</td><td class="r">RM {$subtotalFmt}</td></tr>
    <tr><td class="label">SST ({$sstRate}%)</td><td class="r">RM {$sstFmt}</td></tr>
    <tr><td class="label">Retention ({$retenRate}%)</td><td class="r">(RM {$retentionFmt})</td></tr>
    {$extraRows}
    <tr class="total"><td class="label">TOTAL</td><td class="r">RM {$totalFmt}</td></tr>
</table>
<div class="footer">
    <p>{$docNotes}</p>
    <p style="margin-top:8px; font-size:8pt; color:#999;">Generated by AVSB-ERP</p>
</div>
</body>
</html>
HTML;

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function quotation(Quotation $q): string
    {
        $items = is_array($q->items) ? $q->items : (json_decode($q->items ?? '[]', true) ?: []);

        return $this->render('QUOTATION', [
            'number' => $q->quote_number,
            'client' => $q->client,
            'date' => $q->date ? Carbon::parse($q->date)->format('d F Y') : date('d F Y'),
            'status' => strtoupper($q->status ?? 'DRAFT'),
            'items' => $items,
            'subtotal' => $q->subtotal,
            'sst' => $q->sst,
            'sst_rate' => $q->sst_rate,
            'retention_pct' => $q->retention_pct,
            'retention_amount' => $q->retention_amount,
            'total' => $q->total,
            'notes' => $q->notes,
        ], accent: '#ca2316', buyerInfo: [
            'tin' => $q->buyer_tin ?? '',
            'reg_no' => $q->buyer_reg_no ?? '',
            'sst_reg_no' => $q->buyer_sst_reg_no ?? '',
            'address' => $q->buyer_contact ?? '',
            'email' => $q->buyer_email ?? '',
            'phone' => $q->contact_phone ?? '',
        ]);
    }

    public function contract(Contract $c): string
    {
        $items = is_array($c->items) ? $c->items : (json_decode($c->items ?? '[]', true) ?: []);
        $notes = $c->terms ? 'Terms: '.$c->terms : '';
        if ($c->billing_milestones) {
            $milestones = is_array($c->billing_milestones) ? $c->billing_milestones : (json_decode($c->billing_milestones ?? '[]', true) ?: []);
            $notes .= "\n\nBilling Milestones:\n";
            foreach ($milestones as $m) {
                $notes .= "- {$m['description']}: {$m['percentage']}% (RM ".number_format($m['amount'] ?? 0, 2).")\n";
            }
        }
        $subtotal = $c->subtotal > 0 ? $c->subtotal : round($c->total_amount / (1 + ($c->sst_rate / 100) - ($c->retention_rate / 100)), 2);
        $sstAmount = round($subtotal * ($c->sst_rate / 100), 2);
        $retentionAmount = round($subtotal * ($c->retention_rate / 100), 2);

        return $this->render('CONTRACT', [
            'number' => $c->contract_number,
            'client' => $c->client,
            'date' => $c->date ? Carbon::parse($c->date)->format('d F Y') : date('d F Y'),
            'status' => strtoupper($c->status ?? 'DRAFT'),
            'items' => $items,
            'subtotal' => $subtotal,
            'sst' => $sstAmount,
            'sst_rate' => $c->sst_rate,
            'retention_rate' => $c->retention_rate,
            'retention' => $retentionAmount,
            'total' => $c->total_amount,
            'notes' => $notes,
        ], accent: '#ca2316', buyerInfo: [
            'tin' => $c->buyer_tin ?? '',
            'reg_no' => $c->buyer_reg_no ?? '',
            'sst_reg_no' => $c->buyer_sst_reg_no ?? '',
            'address' => $c->buyer_contact ?? '',
            'email' => $c->buyer_email ?? '',
            'phone' => $c->contact_phone ?? '',
        ]);
    }

    public function invoice(Invoice $i): string
    {
        $items = is_array($i->items) ? $i->items : (json_decode($i->items ?? '[]', true) ?: []);

        return $this->render('INVOICE', [
            'number' => $i->invoice_number,
            'client' => $i->client,
            'date' => $i->date ? Carbon::parse($i->date)->format('d F Y') : date('d F Y'),
            'status' => strtoupper(str_replace('_', ' ', $i->status ?? 'DRAFT')),
            'items' => $items,
            'subtotal' => $i->subtotal,
            'sst' => $i->sst,
            'sst_rate' => ($i->subtotal > 0) ? round($i->sst / $i->subtotal * 100, 2) : 0,
            'retention' => $i->retention,
            'total' => $i->total,
            'payment_reference' => $i->payment_reference,
            'notes' => $i->einvoice_notes ?? $i->notes,
        ], accent: '#ca2316', buyerInfo: [
            'tin' => $i->buyer_tin ?? '',
            'reg_no' => $i->buyer_reg_no ?? '',
            'sst_reg_no' => $i->buyer_sst_reg_no ?? '',
            'address' => $i->buyer_contact ?? '',
            'email' => $i->buyer_email ?? '',
            'phone' => $i->contact_phone ?? '',
        ]);
    }

    public function receipt(Receipt $r): string
    {
        $inv = $r->invoice;
        $client = htmlspecialchars($inv->client ?? '');
        $buyerTin = htmlspecialchars($inv->buyer_tin ?? '');
        $buyerRegNo = htmlspecialchars($inv->buyer_reg_no ?? '');
        $buyerAddress = htmlspecialchars($inv->buyer_contact ?? '');
        $balance = round(($inv->subtotal ?? 0) + ($inv->sst ?? 0) - ($inv->retention ?? 0) - ($r->amount ?? 0), 2);

        $c = $this->company();
        $logoDataUri = $this->embedLogo($c['logo_path']);
        $logoHtml = $logoDataUri ? "<img src=\"{$logoDataUri}\" style=\"height:64px;width:auto;\">" : '';
        $logoDivHtml = $logoHtml ? "<div class=\"header-logo\">{$logoHtml}</div>" : '';
        $companyName = htmlspecialchars($c['name']);
        $companyAddr = htmlspecialchars($c['address']);
        $companyReg = htmlspecialchars($c['reg_no']);
        $companyPhone = htmlspecialchars($c['phone']);
        $companyEmail = htmlspecialchars($c['email']);

        $companyExtraHtml = '';
        if ($companyPhone || $companyEmail) {
            $line = $companyPhone ? "<strong>Tel:</strong> {$companyPhone}" : '';
            if ($companyEmail) {
                $line .= ($line ? ' | ' : '')."<strong>Email:</strong> {$companyEmail}";
            }
            $companyExtraHtml = '<br>'.$line;
        }
        $logoHtml = $logoDataUri ? "<img src=\"{$logoDataUri}\" style=\"height:64px;width:auto;\">" : '';

        $addrLine = $buyerAddress ? "<p class=\"addr\">{$buyerAddress}</p>" : '';
        $idsLine = '';
        if ($buyerTin) {
            $idsLine .= "TIN: {$buyerTin}";
        }
        if ($buyerRegNo) {
            $idsLine .= ($idsLine ? '  ·  ' : '')."BRN: {$buyerRegNo}";
        }
        if ($idsLine) {
            $idsLine = '<p>'.$idsLine.'</p>';
        }
        $paymentRef = htmlspecialchars($r->payment?->payment_reference ?? '');
        $paymentRefRow = $paymentRef ? "<tr><td class=\"label\">Payment Ref</td><td class=\"value\">{$paymentRef}</td></tr>" : '';

        $receiptNo = htmlspecialchars($r->receipt_number);
        $invNo = htmlspecialchars($inv->invoice_number);
        $amountFmt = $this->fmt($r->amount);
        $balanceFmt = $this->fmt(max(0, $balance));
        $dateFmt = $r->date ? Carbon::parse($r->date)->format('d F Y') : date('d F Y');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 20mm 15mm; }
body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11pt; color: #1a1a1a; line-height: 1.4; }
.header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ca2316; padding-bottom: 12px; margin-bottom: 20px; }
.header .company h1 { font-size: 16pt; margin: 0; color: #ca2316; }
.header .company p { margin: 2px 0; font-size: 9pt; color: #555; }
.header .title { text-align: right; }
.header .title h2 { font-size: 18pt; margin: 0; color: #ca2316; letter-spacing: 2px; }
.header .title p { margin: 2px 0; font-size: 9pt; color: #555; }
.bill-to h3 { margin: 0 0 4px 0; font-size: 10pt; color: #ca2316; }
.bill-to p { margin: 1px 0; font-size: 9pt; }
.bill-to .name { font-weight: bold; font-size: 10pt; }
.bill-to .addr { color: #555; }
.details { margin-bottom: 16px; font-size: 10pt; }
.details table { width: 100%; border-collapse: collapse; }
.details td { padding: 4px 8px; }
.details .label { color: #555; width: 140px; }
.details .value { font-weight: bold; }
.footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 8pt; color: #555; text-align: center; }
</style>
</head>
<body>
<div class="header">
    <h2 style="font-size:18pt;margin:0;color:#ca2316;letter-spacing:2px;">OFFICIAL RECEIPT</h2>
    <p style="margin:1px 0;font-size:9pt;color:#555;">{$receiptNo}</p>
    <table style="width:100%;border:none;margin:0;"><tr>
        <td style="vertical-align:top;border:none;padding:0;">
            <p>
                <strong style="font-size:12pt;color:#ca2316;">{$companyName}</strong><br>
                {$companyAddr}<br>
                <strong>Reg No:</strong> {$companyReg}{$companyExtraHtml}
            </p>
        </td>
        <td style="vertical-align:top;text-align:right;border:none;padding:0 0 0 15px;width:200px;">
            {$logoDivHtml}
        </td>
    </tr></table>
</div>
<div class="bill-to">
    <h3>Received From:</h3>
    <p class="name">{$client}</p>
    {$addrLine}
    {$idsLine}
</div>
<div class="details" style="margin-top:4em;">
    <table>
        <tr><td class="label">Invoice</td><td class="value">{$invNo}</td></tr>
        {$paymentRefRow}
        <tr><td class="label">Date</td><td class="value">{$dateFmt}</td></tr>
        <tr><td class="label">Amount Paid</td><td class="value">RM {$amountFmt}</td></tr>
        <tr><td class="label">Balance Due</td><td class="value">RM {$balanceFmt}</td></tr>
    </table>
</div>
<div class="footer">
    <p>This official receipt acknowledges payment for the above referenced invoice.</p>
    <p style="margin-top:4px; color:#999;">Generated by AVSB-ERP</p>
</div>
</body>
</html>
HTML;

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function selfBilled(SelfBilledInvoice $s): string
    {
        $items = is_array($s->items) ? $s->items : (json_decode($s->items ?? '[]', true) ?: []);
        $supplierName = $s->supplier ? ($s->supplier->company_name ?? '') : '';

        return $this->render('SELF-BILLED INVOICE', [
            'number' => $s->invoice_number,
            'client' => $supplierName,
            'date' => $s->date ? Carbon::parse($s->date)->format('d F Y') : date('d F Y'),
            'status' => strtoupper(str_replace('_', ' ', $s->status ?? 'DRAFT')),
            'items' => $items,
            'subtotal' => $s->subtotal,
            'sst' => $s->sst,
            'sst_rate' => $s->sst_rate ?? 0,
            'retention' => $s->retention,
            'total' => $s->total,
            'notes' => $s->notes,
            'header_subtitle' => 'Supplier: '.htmlspecialchars($supplierName),
        ], '#ca2316', 'Supplier');
    }
}
