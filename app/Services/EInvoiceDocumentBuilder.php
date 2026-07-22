<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\SelfBilledInvoice;

class EInvoiceDocumentBuilder
{
    private function formatDate($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }
        if (is_string($date) && $date !== '') {
            return date('Y-m-d', strtotime($date));
        }
        return date('Y-m-d');
    }

    private function formatDateTime($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d\TH:i:s\Z');
        }
        if (is_string($date) && $date !== '') {
            return date('Y-m-d\TH:i:s\Z', strtotime($date));
        }
        return date('Y-m-d\TH:i:s\Z');
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function formatAmount(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        return sprintf(
            '%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x',
            ord($data[0]), ord($data[1]), ord($data[2]), ord($data[3]),
            ord($data[4]), ord($data[5]),
            ord($data[6]), ord($data[7]),
            ord($data[8]), ord($data[9]),
            ord($data[10]), ord($data[11]), ord($data[12]), ord($data[13]), ord($data[14]), ord($data[15])
        );
    }

    private function mapUnitCode(string $unit): string
    {
        $map = [
            'Sqm' => 'MTK',
            'Meter' => 'MTR',
            'Trip' => 'NMP',
            'Setup' => 'C62',
            'Hour' => 'HUR',
            'Day' => 'DAY',
            'Lot' => 'C62',
        ];
        return $map[$unit] ?? 'C62';
    }

    private function indent(int $level): string
    {
        return str_repeat('  ', $level);
    }

    // -----------------------------------------------------------------------
    //  Party builders
    // -----------------------------------------------------------------------

    private function buildPostalAddress(array $addr, int $indent = 0): string
    {
        $in = $this->indent($indent);
        $xml = "{$in}<cac:PostalAddress>\n";

        $addressLine = $addr['address'] ?? '';
        if ($addressLine !== '') {
            $xml .= "{$in}  <cac:AddressLine>\n";
            $xml .= "{$in}    <cbc:Line>" . $this->escapeXml($addressLine) . "</cbc:Line>\n";
            $xml .= "{$in}  </cac:AddressLine>\n";
        }

        if (!empty($addr['city'])) {
            $xml .= "{$in}  <cbc:CityName>" . $this->escapeXml($addr['city']) . "</cbc:CityName>\n";
        }
        if (!empty($addr['postcode'])) {
            $xml .= "{$in}  <cbc:PostalZone>" . $this->escapeXml($addr['postcode']) . "</cbc:PostalZone>\n";
        }
        if (!empty($addr['state'])) {
            $xml .= "{$in}  <cbc:CountrySubentity>" . $this->escapeXml($addr['state']) . "</cbc:CountrySubentity>\n";
        }

        $country = $addr['country'] ?? 'MYS';
        $xml .= "{$in}  <cac:Country>\n";
        $xml .= "{$in}    <cbc:IdentificationCode listID=\"ISO3166-1\">" . $this->escapeXml($country) . "</cbc:IdentificationCode>\n";
        $xml .= "{$in}  </cac:Country>\n";

        $xml .= "{$in}</cac:PostalAddress>\n";
        return $xml;
    }

    private function buildContact(array $contact, int $indent = 0): string
    {
        $in = $this->indent($indent);
        $xml = "{$in}<cac:Contact>\n";
        if (!empty($contact['phone'])) {
            $xml .= "{$in}  <cbc:Telephone>" . $this->escapeXml($contact['phone']) . "</cbc:Telephone>\n";
        }
        if (!empty($contact['email'])) {
            $xml .= "{$in}  <cbc:ElectronicMail>" . $this->escapeXml($contact['email']) . "</cbc:ElectronicMail>\n";
        }
        $xml .= "{$in}</cac:Contact>\n";
        return $xml;
    }

    /**
     * Build a complete AccountingParty (supplier or customer).
     *
     * $party keys:
     *   - tin          string  Tax Identification Number
     *   - name         string  Registration / company name
     *   - reg_no       string  Business registration number (optional)
     *   - sst_reg_no   string  SST registration number (optional)
     *   - address      string  Full address (free text)
     *   - city         string  (optional)
     *   - postcode     string  (optional)
     *   - state        string  (optional)
     *   - country      string  ISO 3166-1 alpha-3 (default MYS)
     *   - phone        string  (optional)
     *   - email        string  (optional)
     *   - msic         string  MSIC code (optional, for supplier)
     *   - msic_desc    string  MSIC description (optional)
     *   - supplier     bool    true = AccountingSupplierParty, false = AccountingCustomerParty
     *   - addTINParty  bool    include TIN in PartyIdentification (default true)
     *   - addSSTScheme bool    include SST tax scheme (default true)
     */
    private function buildParty(array $party, int $indent = 0): string
    {
        $in = $this->indent($indent);
        $xml = '';

        $tag = !empty($party['supplier']) ? 'AccountingSupplierParty' : 'AccountingCustomerParty';
        $xml .= "{$in}<cac:{$tag}>\n";
        $xml .= "{$in}  <cac:Party>\n";

        if (!empty($party['tin'])) {
            $xml .= "{$in}    <cac:PartyIdentification>\n";
            $xml .= "{$in}      <cbc:ID schemeID=\"TIN\">" . $this->escapeXml($party['tin']) . "</cbc:ID>\n";
            $xml .= "{$in}    </cac:PartyIdentification>\n";
        }

        if (!empty($party['reg_no'])) {
            $xml .= "{$in}    <cac:PartyIdentification>\n";
            $xml .= "{$in}      <cbc:ID schemeID=\"BRN\">" . $this->escapeXml($party['reg_no']) . "</cbc:ID>\n";
            $xml .= "{$in}    </cac:PartyIdentification>\n";
        }

        if (!empty($party['name'])) {
            $xml .= "{$in}    <cac:PartyName>\n";
            $xml .= "{$in}      <cbc:Name>" . $this->escapeXml($party['name']) . "</cbc:Name>\n";
            $xml .= "{$in}    </cac:PartyName>\n";
        }

        $hasAddress = !empty($party['address']);
        if ($hasAddress) {
            $xml .= $this->buildPostalAddress([
                'address' => $party['address'] ?? '',
                'city' => $party['city'] ?? '',
                'postcode' => $party['postcode'] ?? '',
                'state' => $party['state'] ?? '',
                'country' => $party['country'] ?? 'MYS',
            ], $indent + 2);
        }

        if (!empty($party['sst_reg_no'])) {
            $xml .= "{$in}    <cac:PartyTaxScheme>\n";
            $xml .= "{$in}      <cbc:CompanyID>" . $this->escapeXml($party['sst_reg_no']) . "</cbc:CompanyID>\n";
            $xml .= "{$in}      <cac:TaxScheme>\n";
            $xml .= "{$in}        <cbc:ID>OTH</cbc:ID>\n";
            $xml .= "{$in}      </cac:TaxScheme>\n";
            $xml .= "{$in}    </cac:PartyTaxScheme>\n";
        }

        $xml .= "{$in}    <cac:PartyLegalEntity>\n";
        $xml .= "{$in}      <cbc:RegistrationName>" . $this->escapeXml($party['name'] ?? '') . "</cbc:RegistrationName>\n";
        $xml .= "{$in}    </cac:PartyLegalEntity>\n";

        $hasContact = !empty($party['phone']) || !empty($party['email']);
        if ($hasContact) {
            $xml .= $this->buildContact([
                'phone' => $party['phone'] ?? '',
                'email' => $party['email'] ?? '',
            ], $indent + 2);
        }

        if (!empty($party['msic'])) {
            $xml .= "{$in}    <cac:IndustryClassificationCode listID=\"MSIC\"";
            if (!empty($party['msic_desc'])) {
                $xml .= " name=\"" . $this->escapeXml($party['msic_desc']) . "\"";
            }
            $xml .= ">" . $this->escapeXml($party['msic']) . "</cac:IndustryClassificationCode>\n";
        }

        $xml .= "{$in}  </cac:Party>\n";
        $xml .= "{$in}</cac:{$tag}>\n";

        return $xml;
    }

    // -----------------------------------------------------------------------
    //  Monetary helpers
    // -----------------------------------------------------------------------

    private function buildTaxTotal(float $subtotal, float $sst, string $currency, int $indent = 0): string
    {
        $in = $this->indent($indent);
        $xml = "{$in}<cac:TaxTotal>\n";
        $xml .= "{$in}  <cbc:TaxAmount currencyID=\"{$currency}\">" . $this->formatAmount($sst) . "</cbc:TaxAmount>\n";

        if (abs($sst) > 0.005) {
            $xml .= "{$in}  <cac:TaxSubtotal>\n";
            $xml .= "{$in}    <cbc:TaxableAmount currencyID=\"{$currency}\">" . $this->formatAmount($subtotal) . "</cbc:TaxableAmount>\n";
            $xml .= "{$in}    <cbc:TaxAmount currencyID=\"{$currency}\">" . $this->formatAmount($sst) . "</cbc:TaxAmount>\n";
            $xml .= "{$in}    <cac:TaxCategory>\n";
            $xml .= "{$in}      <cbc:ID>01</cbc:ID>\n";
            $xml .= "{$in}      <cac:TaxScheme>\n";
            $xml .= "{$in}        <cbc:ID>SST</cbc:ID>\n";
            $xml .= "{$in}      </cac:TaxScheme>\n";
            $xml .= "{$in}    </cac:TaxCategory>\n";
            $xml .= "{$in}  </cac:TaxSubtotal>\n";
        }

        $xml .= "{$in}</cac:TaxTotal>\n";
        return $xml;
    }

    private function buildLegalMonetaryTotal(float $subtotal, float $sst, float $retention, float $total, string $currency, int $indent = 0): string
    {
        $in = $this->indent($indent);
        $taxExclusive = $subtotal - $retention;
        $taxInclusive = $taxExclusive + $sst;

        $xml = "{$in}<cac:LegalMonetaryTotal>\n";
        $xml .= "{$in}  <cbc:LineExtensionAmount currencyID=\"{$currency}\">" . $this->formatAmount($subtotal) . "</cbc:LineExtensionAmount>\n";
        $xml .= "{$in}  <cbc:TaxExclusiveAmount currencyID=\"{$currency}\">" . $this->formatAmount(max(0, $taxExclusive)) . "</cbc:TaxExclusiveAmount>\n";
        $xml .= "{$in}  <cbc:TaxInclusiveAmount currencyID=\"{$currency}\">" . $this->formatAmount(max(0, $taxInclusive)) . "</cbc:TaxInclusiveAmount>\n";
        $xml .= "{$in}  <cbc:PayableAmount currencyID=\"{$currency}\">" . $this->formatAmount($total) . "</cbc:PayableAmount>\n";
        $xml .= "{$in}</cac:LegalMonetaryTotal>\n";
        return $xml;
    }

    // -----------------------------------------------------------------------
    //  Invoice lines builder
    // -----------------------------------------------------------------------

    private function buildInvoiceLines(array $items, string $currency, string $classificationCode, int $indent = 0): string
    {
        $in = $this->indent($indent);
        $xml = '';
        $n = 0;

        foreach ($items as $item) {
            $n++;
            $desc = $item['description'] ?? '';
            $qty = (float) ($item['quantity'] ?? $item['qty'] ?? 1);
            $unitPrice = (float) ($item['unit_rate'] ?? $item['rate'] ?? $item['unitRate'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? r2($qty * $unitPrice));
            $unit = $item['unit'] ?? 'C62';
            $unitCode = $this->mapUnitCode($unit);

            $xml .= "{$in}<cac:InvoiceLine>\n";
            $xml .= "{$in}  <cbc:ID>{$n}</cbc:ID>\n";
            $xml .= "{$in}  <cbc:InvoicedQuantity unitCode=\"{$unitCode}\">{$qty}</cbc:InvoicedQuantity>\n";
            $xml .= "{$in}  <cbc:LineExtensionAmount currencyID=\"{$currency}\">" . $this->formatAmount($lineTotal) . "</cbc:LineExtensionAmount>\n";

            $xml .= "{$in}  <cac:Item>\n";
            if ($desc !== '') {
                $xml .= "{$in}    <cbc:Description>" . $this->escapeXml($desc) . "</cbc:Description>\n";
            }
            if ($classificationCode !== '') {
                $xml .= "{$in}    <cac:CommodityClassification>\n";
                $xml .= "{$in}      <cbc:ItemClassificationCode listID=\"PTC\">" . $this->escapeXml($classificationCode) . "</cbc:ItemClassificationCode>\n";
                $xml .= "{$in}    </cac:CommodityClassification>\n";
            }
            $xml .= "{$in}  </cac:Item>\n";

            $xml .= "{$in}  <cac:Price>\n";
            $xml .= "{$in}    <cbc:PriceAmount currencyID=\"{$currency}\">" . $this->formatAmount($unitPrice) . "</cbc:PriceAmount>\n";
            $xml .= "{$in}  </cac:Price>\n";

            $xml .= "{$in}</cac:InvoiceLine>\n";
        }

        return $xml;
    }

    // -----------------------------------------------------------------------
    //  Core XML builder
    // -----------------------------------------------------------------------

    private function wrapXml(string $body, string $uuid, string $issueDate, string $invoiceNumber,
        string $invoiceTypeCode, string $documentCurrencyCode, string $supplyDate = ''): string
    {
        $customizationID = 'urn:cen.eu:en16931:2017#compliant#urn:x-gov:my:e-invoice:1.0';
        $profileID = 'urn:cen.eu:en16931:2017';
        $profileVersion = '1.0';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' . "\n";
        $xml .= '         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' . "\n";
        $xml .= '         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">' . "\n";
        $xml .= '  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>' . "\n";
        $xml .= '  <cbc:CustomizationID>' . $this->escapeXml($customizationID) . '</cbc:CustomizationID>' . "\n";
        $xml .= '  <cbc:ProfileID>' . $this->escapeXml($profileID) . '</cbc:ProfileID>' . "\n";
        $xml .= '  <cbc:ProfileVersionID>' . $this->escapeXml($profileVersion) . '</cbc:ProfileVersionID>' . "\n";
        $xml .= '  <cbc:ID>' . $this->escapeXml($invoiceNumber) . '</cbc:ID>' . "\n";
        $xml .= '  <cbc:UUID>' . $this->escapeXml($uuid) . '</cbc:UUID>' . "\n";
        $xml .= '  <cbc:IssueDate>' . $this->escapeXml($issueDate) . '</cbc:IssueDate>' . "\n";
        $xml .= '  <cbc:InvoiceTypeCode listVersionID="1.0">' . $this->escapeXml($invoiceTypeCode) . '</cbc:InvoiceTypeCode>' . "\n";
        $xml .= '  <cbc:DocumentCurrencyCode>' . $this->escapeXml($documentCurrencyCode) . '</cbc:DocumentCurrencyCode>' . "\n";

        if ($supplyDate !== '') {
            $xml .= '  <cac:InvoicePeriod>' . "\n";
            $xml .= '    <cbc:StartDate>' . $this->escapeXml($supplyDate) . '</cbc:StartDate>' . "\n";
            $xml .= '    <cbc:EndDate>' . $this->escapeXml($supplyDate) . '</cbc:EndDate>' . "\n";
            $xml .= '  </cac:InvoicePeriod>' . "\n";
        }

        $xml .= $body;
        $xml .= '</Invoice>' . "\n";

        return $xml;
    }

    // -----------------------------------------------------------------------
    //  Public entry points
    // -----------------------------------------------------------------------

    public function buildInvoice(Invoice $invoice): string
    {
        $company = CompanySetting::first();

        $items = $invoice->items ?? [];
        if (!is_array($items)) {
            $items = json_decode($items, true) ?: [];
        }

        $uuid = $invoice->uuid ?: $this->generateUuid();
        $invoiceTypeCode = '01';
        $currency = $invoice->currency ?: 'MYR';
        $country = $invoice->country ?: 'MYS';
        $issueDate = $this->formatDate($invoice->date ?: null);
        $supplyDate = $invoice->supply_date ? $this->formatDate($invoice->supply_date) : '';
        $invoiceNumber = $invoice->invoice_number;

        $subtotal = (float) ($invoice->subtotal ?? 0);
        $sst = (float) ($invoice->sst ?? 0);
        $retention = (float) ($invoice->retention ?? 0);
        $total = (float) ($invoice->total ?? 0);
        $classificationCode = $invoice->classification_code ?: '022';

        $sellerTIN = $invoice->seller_tin ?: ($company->tax_id_number ?? '');
        $sellerSST = $invoice->seller_sst_reg_no ?: ($company->sst_registration_no ?? '');

        $xml = '';
        $indent = 1;

        $xml .= $this->buildParty([
            'supplier' => true,
            'tin' => $sellerTIN,
            'name' => $company->company_name ?? '',
            'reg_no' => $company->reg_no ?? '',
            'sst_reg_no' => $sellerSST,
            'address' => $company->address ?? '',
            'country' => $country,
            'phone' => $company->business_phone ?? '',
            'email' => $company->business_email ?? '',
            'msic' => $company->msic_code ?? '',
            'msic_desc' => $company->msic_description ?? '',
        ], $indent);

        $xml .= $this->buildParty([
            'supplier' => false,
            'tin' => $invoice->buyer_tin ?? '',
            'name' => $invoice->client ?? '',
            'reg_no' => $invoice->buyer_reg_no ?? '',
            'sst_reg_no' => $invoice->buyer_sst_reg_no ?? '',
            'address' => $invoice->buyer_contact ?? '',
            'country' => $country,
            'phone' => $invoice->contact_phone ?? '',
            'email' => $invoice->buyer_email ?? '',
        ], $indent);

        if ($invoice->einvoice_type === 'credit_note') {
            $originalUuid = '';
            if ($invoice->credit_note_for_id) {
                $original = \App\Models\Invoice::find($invoice->credit_note_for_id);
                if ($original) $originalUuid = $original->uuid ?: '';
            }
            if ($originalUuid !== '') {
                $in = $this->indent($indent);
                $xml .= "{$in}<cac:BillingReference>\n";
                $xml .= "{$in}  <cac:InvoiceDocumentReference>\n";
                $xml .= "{$in}    <cbc:ID>" . $this->escapeXml($originalUuid) . "</cbc:ID>\n";
                $xml .= "{$in}  </cac:InvoiceDocumentReference>\n";
                $xml .= "{$in}</cac:BillingReference>\n";
            }
        }

        $xml .= $this->buildTaxTotal($subtotal, $sst, $currency, $indent);
        $xml .= $this->buildLegalMonetaryTotal($subtotal, $sst, $retention, $total, $currency, $indent);
        $xml .= $this->buildInvoiceLines($items, $currency, $classificationCode, $indent);

        $notes = $invoice->einvoice_notes ?? '';
        if ($notes !== '') {
            $in = $this->indent($indent);
            $xml .= "{$in}<cac:AdditionalDocumentReference>\n";
            $xml .= "{$in}  <cbc:ID>NOTES</cbc:ID>\n";
            $xml .= "{$in}  <cbc:DocumentType>Notes</cbc:DocumentType>\n";
            $xml .= "{$in}  <cac:Attachment>\n";
            $xml .= "{$in}    <cbc:EmbeddedDocumentBinaryObject mimeCode=\"text/plain\">" . $this->escapeXml($notes) . "</cbc:EmbeddedDocumentBinaryObject>\n";
            $xml .= "{$in}  </cac:Attachment>\n";
            $xml .= "{$in}</cac:AdditionalDocumentReference>\n";
        }

        $xml = $this->wrapXml($xml, $uuid, $issueDate, $invoiceNumber, $invoiceTypeCode, $currency, $supplyDate);
        return base64_encode($xml);
    }

    public function buildSelfBilled(SelfBilledInvoice $sb): string
    {
        $company = CompanySetting::first();

        $items = $sb->items ?? [];
        if (!is_array($items)) {
            $items = json_decode($items, true) ?: [];
        }

        $uuid = $sb->uuid ?: $this->generateUuid();
        $invoiceTypeCode = '02';
        $currency = 'MYR';
        $country = 'MYS';
        $issueDate = $this->formatDate($sb->date ?: null);
        $supplyDate = $sb->supply_date ? $this->formatDate($sb->supply_date) : '';
        $invoiceNumber = $sb->invoice_number;

        $subtotal = (float) ($sb->subtotal ?? 0);
        $sst = (float) ($sb->sst ?? 0);
        $retention = (float) ($sb->retention ?? 0);
        $total = (float) ($sb->total ?? 0);
        $classificationCode = $company->msic_code ?: '022';

        $supplier = $sb->supplier;

        $xml = '';
        $indent = 1;

        $xml .= $this->buildParty([
            'supplier' => true,
            'tin' => $supplier->tax_id ?? '',
            'name' => $supplier->company_name ?? '',
            'reg_no' => $supplier->registration_no ?? '',
            'sst_reg_no' => $supplier->sst_reg_no ?? '',
            'address' => $supplier->address ?? '',
            'country' => $country,
            'phone' => $supplier->phone ?? '',
            'email' => $supplier->email ?? '',
            'msic' => '',
            'msic_desc' => '',
        ], $indent);

        $xml .= $this->buildParty([
            'supplier' => false,
            'tin' => $company->tax_id_number ?? '',
            'name' => $company->company_name ?? '',
            'reg_no' => $company->reg_no ?? '',
            'sst_reg_no' => $company->sst_registration_no ?? '',
            'address' => $company->address ?? '',
            'country' => $country,
            'phone' => $company->business_phone ?? '',
            'email' => $company->business_email ?? '',
            'msic' => $classificationCode,
            'msic_desc' => $company->msic_description ?? '',
        ], $indent);

        $xml .= $this->buildTaxTotal($subtotal, $sst, $currency, $indent);
        $xml .= $this->buildLegalMonetaryTotal($subtotal, $sst, $retention, $total, $currency, $indent);
        $xml .= $this->buildInvoiceLines($items, $currency, $classificationCode, $indent);

        $notes = $sb->notes ?? '';
        if ($notes !== '') {
            $in = $this->indent($indent);
            $xml .= "{$in}<cac:AdditionalDocumentReference>\n";
            $xml .= "{$in}  <cbc:ID>NOTES</cbc:ID>\n";
            $xml .= "{$in}  <cbc:DocumentType>Notes</cbc:DocumentType>\n";
            $xml .= "{$in}  <cac:Attachment>\n";
            $xml .= "{$in}    <cbc:EmbeddedDocumentBinaryObject mimeCode=\"text/plain\">" . $this->escapeXml($notes) . "</cbc:EmbeddedDocumentBinaryObject>\n";
            $xml .= "{$in}  </cac:Attachment>\n";
            $xml .= "{$in}</cac:AdditionalDocumentReference>\n";
        }

        $xml = $this->wrapXml($xml, $uuid, $issueDate, $invoiceNumber, $invoiceTypeCode, $currency, $supplyDate);
        return base64_encode($xml);
    }
}
