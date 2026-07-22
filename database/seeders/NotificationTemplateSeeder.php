<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;

class NotificationTemplateSeeder
{
    public function run(): void
    {
        $templates = [
            // ── Leaves ──
            [
                'event_type' => 'leave.applied',
                'category' => 'approval',
                'subject_template' => 'New Leave Application — {{staff_name}}',
                'body_template' => '<p><strong>{{staff_name}}</strong> has applied for {{days}} day(s) of <strong>{{leave_type}}</strong> leave.</p><p><strong>Dates:</strong> {{date_range}}</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'leave.approved',
                'category' => 'status',
                'subject_template' => 'Leave Approved — {{leave_type}} ({{date_range}})',
                'body_template' => '<p>Your <strong>{{leave_type}}</strong> leave application for <strong>{{date_range}}</strong> ({{days}} day(s)) has been <strong style="color:green">approved</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'leave.rejected',
                'category' => 'status',
                'subject_template' => 'Leave Rejected — {{leave_type}} ({{date_range}})',
                'body_template' => '<p>Your <strong>{{leave_type}}</strong> leave application for <strong>{{date_range}}</strong> has been <strong style="color:red">rejected</strong>.</p><p><strong>Reason:</strong> {{reason}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Expense Claims ──
            [
                'event_type' => 'claim.submitted',
                'category' => 'approval',
                'subject_template' => 'New Expense Claim — {{staff_name}} (RM{{amount}})',
                'body_template' => '<p><strong>{{staff_name}}</strong> has submitted an expense claim.</p><p><strong>Title:</strong> {{title}}</p><p><strong>Amount:</strong> RM{{amount}}</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'claim.approved',
                'category' => 'status',
                'subject_template' => 'Expense Claim Approved — {{title}}',
                'body_template' => '<p>Your expense claim <strong>{{title}}</strong> (RM{{amount}}) has been <strong style="color:green">approved</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'claim.rejected',
                'category' => 'status',
                'subject_template' => 'Expense Claim Rejected — {{title}}',
                'body_template' => '<p>Your expense claim <strong>{{title}}</strong> has been <strong style="color:red">rejected</strong>.</p><p><strong>Reason:</strong> {{reason}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'claim.paid',
                'category' => 'status',
                'subject_template' => 'Expense Claim Paid — {{title}}',
                'body_template' => '<p>Your expense claim <strong>{{title}}</strong> (RM{{amount}}) has been <strong style="color:green">paid</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Timecards ──
            [
                'event_type' => 'timecard.submitted',
                'category' => 'approval',
                'subject_template' => 'New Timecard Submitted — {{staff_name}}',
                'body_template' => '<p><strong>{{staff_name}}</strong> has submitted a timecard for <strong>{{date}}</strong> ({{hours}} hours).</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'timecard.approved',
                'category' => 'status',
                'subject_template' => 'Timecard Approved — {{date}}',
                'body_template' => '<p>Your timecard for <strong>{{date}}</strong> has been <strong style="color:green">approved</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'timecard.rejected',
                'category' => 'status',
                'subject_template' => 'Timecard Rejected — {{date}}',
                'body_template' => '<p>Your timecard for <strong>{{date}}</strong> has been <strong style="color:red">rejected</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Subcontractor Claims ──
            [
                'event_type' => 'subcon-claim.submitted',
                'category' => 'approval',
                'subject_template' => 'New Subcontractor Claim — {{subcontractor}} (RM{{amount}})',
                'body_template' => '<p>A subcontractor claim has been submitted by <strong>{{subcontractor}}</strong>.</p><p><strong>Claim Number:</strong> {{claim_number}}</p><p><strong>Amount:</strong> RM{{amount}}</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'subcon-claim.verified',
                'category' => 'approval',
                'subject_template' => 'Subcontractor Claim Verified — {{claim_number}}',
                'body_template' => '<p>The subcontractor claim <strong>{{claim_number}}</strong> from <strong>{{subcontractor}}</strong> has been verified and needs your approval.</p><p><strong>Amount:</strong> RM{{amount}}</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'subcon-claim.approved',
                'category' => 'status',
                'subject_template' => 'Subcontractor Claim Approved — {{claim_number}}',
                'body_template' => '<p>The subcontractor claim <strong>{{claim_number}}</strong> (RM{{amount}}) has been <strong style="color:green">approved</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'subcon-claim.rejected',
                'category' => 'status',
                'subject_template' => 'Subcontractor Claim Rejected — {{claim_number}}',
                'body_template' => '<p>The subcontractor claim <strong>{{claim_number}}</strong> has been <strong style="color:red">rejected</strong>.</p><p><strong>Reason:</strong> {{reason}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'subcon-claim.paid',
                'category' => 'status',
                'subject_template' => 'Subcontractor Claim Paid — {{claim_number}}',
                'body_template' => '<p>The subcontractor claim <strong>{{claim_number}}</strong> (RM{{amount}}) has been <strong style="color:green">paid</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Project Claims ──
            [
                'event_type' => 'project-claim.submitted',
                'category' => 'approval',
                'subject_template' => 'New Project Claim — {{title}} (RM{{amount}})',
                'body_template' => '<p>A project claim <strong>{{title}}</strong> has been submitted for project <strong>{{project_name}}</strong>.</p><p><strong>Amount:</strong> RM{{amount}}</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'project-claim.approved',
                'category' => 'status',
                'subject_template' => 'Project Claim Approved — {{title}}',
                'body_template' => '<p>The project claim <strong>{{title}}</strong> (RM{{amount}}) has been <strong style="color:green">approved</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'project-claim.rejected',
                'category' => 'status',
                'subject_template' => 'Project Claim Rejected — {{title}}',
                'body_template' => '<p>The project claim <strong>{{title}}</strong> has been <strong style="color:red">rejected</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'project-claim.paid',
                'category' => 'status',
                'subject_template' => 'Project Claim Paid — {{title}}',
                'body_template' => '<p>The project claim <strong>{{title}}</strong> (RM{{amount}}) has been <strong style="color:green">paid</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Self-Billed Invoices ──
            [
                'event_type' => 'self-billed.created',
                'category' => 'approval',
                'subject_template' => 'New Self-Billed Invoice — {{invoice_number}} (RM{{total}})',
                'body_template' => '<p>A self-billed invoice <strong>{{invoice_number}}</strong> from <strong>{{supplier}}</strong> has been created and needs approval.</p><p><strong>Total:</strong> RM{{total}}</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'self-billed.approved',
                'category' => 'status',
                'subject_template' => 'Self-Billed Invoice Approved — {{invoice_number}}',
                'body_template' => '<p>The self-billed invoice <strong>{{invoice_number}}</strong> has been <strong style="color:green">approved</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'self-billed.submitted',
                'category' => 'status',
                'subject_template' => 'Self-Billed Invoice Submitted to LHDN — {{invoice_number}}',
                'body_template' => '<p>The self-billed invoice <strong>{{invoice_number}}</strong> has been submitted to LHDN MyInvois.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'self-billed.paid',
                'category' => 'status',
                'subject_template' => 'Self-Billed Invoice Paid — {{invoice_number}}',
                'body_template' => '<p>The self-billed invoice <strong>{{invoice_number}}</strong> (RM{{total}}) has been <strong style="color:green">paid</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'self-billed.rejected',
                'category' => 'status',
                'subject_template' => 'Self-Billed Invoice Rejected — {{invoice_number}}',
                'body_template' => '<p>Your self-billed invoice <strong>{{invoice_number}}</strong> has been <strong style="color:red">rejected</strong>.</p><p><strong>Reason:</strong> {{reason}}</p><p>It has been returned to draft for revision.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Invoices ──
            [
                'event_type' => 'invoice.issued',
                'category' => 'status',
                'subject_template' => 'Invoice Issued — {{invoice_number}} (RM{{total}})',
                'body_template' => '<p>Invoice <strong>{{invoice_number}}</strong> has been issued.</p><p><strong>Client:</strong> {{client}}</p><p><strong>Total:</strong> RM{{total}}</p><p><strong>Due Date:</strong> {{due_date}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'invoice.paid',
                'category' => 'status',
                'subject_template' => 'Invoice Paid — {{invoice_number}}',
                'body_template' => '<p>Invoice <strong>{{invoice_number}}</strong> from <strong>{{client}}</strong> has been <strong style="color:green">paid</strong>.</p><p><strong>Total:</strong> RM{{total}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'invoice.partial-payment',
                'category' => 'status',
                'subject_template' => 'Partial Payment Received — {{invoice_number}}',
                'body_template' => '<p>A partial payment of RM{{amount}} has been received for invoice <strong>{{invoice_number}}</strong>.</p><p><strong>Remaining:</strong> RM{{remaining}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Phases ──
            [
                'event_type' => 'phase.started',
                'category' => 'status',
                'subject_template' => 'Phase Started — {{phase_name}} ({{project_name}})',
                'body_template' => '<p>Phase <strong>{{phase_name}}</strong> in project <strong>{{project_name}}</strong> has been started.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'phase.completed',
                'category' => 'status',
                'subject_template' => 'Phase Completed — {{phase_name}} ({{project_name}})',
                'body_template' => '<p>Phase <strong>{{phase_name}}</strong> in project <strong>{{project_name}}</strong> has been completed.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Tasks ──
            [
                'event_type' => 'task.assigned',
                'category' => 'status',
                'subject_template' => 'New Task Assigned — {{task_title}}',
                'body_template' => '<p>You have been assigned a task: <strong>{{task_title}}</strong> in project <strong>{{project_name}}</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'task.completed',
                'category' => 'status',
                'subject_template' => 'Task Completed — {{task_title}} ({{project_name}})',
                'body_template' => '<p>Task <strong>{{task_title}}</strong> in project <strong>{{project_name}}</strong> has been completed.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Projects ──
            [
                'event_type' => 'project.completed',
                'category' => 'status',
                'subject_template' => 'Project Completed — {{project_name}}',
                'body_template' => '<p>Project <strong>{{project_name}}</strong> is now <strong style="color:green">completed</strong>.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── E-Invoice ──
            [
                'event_type' => 'einvoice.submitted',
                'category' => 'status',
                'subject_template' => 'E-Invoice Submitted — {{invoice_number}}',
                'body_template' => '<p>E-invoice for <strong>{{invoice_number}}</strong> has been submitted to LHDN MyInvois.</p><p><strong>Submission UID:</strong> {{submission_uid}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'einvoice.cancelled',
                'category' => 'status',
                'subject_template' => 'E-Invoice Cancelled — {{invoice_number}}',
                'body_template' => '<p>E-invoice for <strong>{{invoice_number}}</strong> has been cancelled.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'einvoice.failed',
                'category' => 'alert',
                'subject_template' => 'E-Invoice Submission Failed — {{invoice_number}}',
                'body_template' => '<p>E-invoice submission for <strong>{{invoice_number}}</strong> has <strong style="color:red">failed</strong>.</p><p><strong>Error:</strong> {{error}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Purchase Orders ──
            [
                'event_type' => 'po.submitted',
                'category' => 'approval',
                'subject_template' => 'Purchase Order Submitted — {{po_number}}',
                'body_template' => '<p>Purchase order <strong>{{po_number}}</strong> (RM{{total}}) has been submitted and needs to be received.</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'po.received',
                'category' => 'status',
                'subject_template' => 'Purchase Order Received — {{po_number}}',
                'body_template' => '<p>Purchase order <strong>{{po_number}}</strong> has been marked as received.</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Payroll ──
            [
                'event_type' => 'payslip.available',
                'category' => 'status',
                'subject_template' => 'Payslip Available — {{period}}',
                'body_template' => '<p>Your payslip for <strong>{{period}}</strong> is now available.</p><p><strong>Net Pay:</strong> RM{{net_pay}}</p><p><a href="{{url}}">Download in AVSB ERP</a></p>',
            ],

            // ── Attendance ──
            [
                'event_type' => 'attendance.flagged',
                'category' => 'alert',
                'subject_template' => 'Attendance Alert — {{staff_name}} ({{date}})',
                'body_template' => '<p><strong>{{staff_name}}</strong> clocked in/out for <strong>{{date}}</strong> and the session exceeded 14 hours.</p><p><a href="{{url}}">Review in AVSB ERP</a></p>',
            ],

            // ── Alerts ──
            [
                'event_type' => 'license.expiring',
                'category' => 'alert',
                'subject_template' => 'License Expiring Soon — {{license_name}} ({{days_left}} days)',
                'body_template' => '<p>License <strong>{{license_name}}</strong> for asset <strong>{{asset_name}}</strong> is expiring in <strong>{{days_left}} days</strong>.</p><p><strong>Expiry Date:</strong> {{expiry_date}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'inventory.low-stock',
                'category' => 'alert',
                'subject_template' => 'Low Stock Alert — {{item_name}} ({{current_qty}} remaining)',
                'body_template' => '<p>Inventory item <strong>{{item_name}}</strong> (SKU: {{sku}}) is running low.</p><p><strong>Current Stock:</strong> {{current_qty}}</p><p><strong>Min. Level:</strong> {{min_level}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Quotations ──
            [
                'event_type' => 'quote.submitted',
                'category' => 'approval',
                'subject_template' => 'Quotation Submitted — {{quote_number}}',
                'body_template' => '<p>Quotation <strong>{{quote_number}}</strong> has been submitted.</p><p><strong>Client:</strong> {{client}}</p><p><strong>Total:</strong> RM {{total}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'quote.accepted',
                'category' => 'approval',
                'subject_template' => 'Quotation Accepted — {{quote_number}}',
                'body_template' => '<p>Quotation <strong>{{quote_number}}</strong> has been accepted by the client.</p><p><strong>Client:</strong> {{client}}</p><p><strong>Total:</strong> RM {{total}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'quote.converted',
                'category' => 'approval',
                'subject_template' => 'Quotation Converted — {{quote_number}}',
                'body_template' => '<p>Quotation <strong>{{quote_number}}</strong> has been converted to a contract.</p><p><strong>Client:</strong> {{client}}</p><p><strong>Contract:</strong> {{contract_number}}</p><p><a href="{{url}}">View Contract</a></p>',
            ],
            [
                'event_type' => 'quote.declined',
                'category' => 'status',
                'subject_template' => 'Quotation Declined — {{quote_number}}',
                'body_template' => '<p>Quotation <strong>{{quote_number}}</strong> has been declined.</p><p><strong>Client:</strong> {{client}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],

            // ── Contracts ──
            [
                'event_type' => 'contract.created',
                'category' => 'approval',
                'subject_template' => 'Contract Created — {{contract_number}}',
                'body_template' => '<p>A new contract has been created.</p><p><strong>Contract:</strong> {{contract_number}}</p><p><strong>Client:</strong> {{client}}</p><p><strong>Amount:</strong> RM {{amount}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'contract.activated',
                'category' => 'approval',
                'subject_template' => 'Contract Activated — {{contract_number}}',
                'body_template' => '<p>Contract <strong>{{contract_number}}</strong> has been activated.</p><p><strong>Client:</strong> {{client}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
            [
                'event_type' => 'contract.completed',
                'category' => 'approval',
                'subject_template' => 'Contract Completed — {{contract_number}}',
                'body_template' => '<p>Contract <strong>{{contract_number}}</strong> has been completed.</p><p><strong>Client:</strong> {{client}}</p><p><strong>Amount:</strong> RM {{amount}}</p><p><a href="{{url}}">View in AVSB ERP</a></p>',
            ],
        ];

        $count = 0;
        foreach ($templates as $tpl) {
            NotificationTemplate::firstOrCreate(
                ['event_type' => $tpl['event_type']],
                $tpl
            );
            $count++;
        }

        echo "Seeded {$count} notification templates.\n";
    }
}
