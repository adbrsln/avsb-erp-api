<?php

namespace App\Services\Notification;

class NotificationEvent
{
    const LEAVE_APPLIED = 'leave.applied';

    const LEAVE_APPROVED = 'leave.approved';

    const LEAVE_REJECTED = 'leave.rejected';

    const CLAIM_SUBMITTED = 'claim.submitted';

    const CLAIM_APPROVED = 'claim.approved';

    const CLAIM_REJECTED = 'claim.rejected';

    const CLAIM_PAID = 'claim.paid';

    const TIMECARD_SUBMITTED = 'timecard.submitted';

    const TIMECARD_APPROVED = 'timecard.approved';

    const TIMECARD_REJECTED = 'timecard.rejected';

    const SUBCON_CLAIM_SUBMITTED = 'subcon-claim.submitted';

    const SUBCON_CLAIM_VERIFIED = 'subcon-claim.verified';

    const SUBCON_CLAIM_APPROVED = 'subcon-claim.approved';

    const SUBCON_CLAIM_REJECTED = 'subcon-claim.rejected';

    const SUBCON_CLAIM_PAID = 'subcon-claim.paid';

    const PROJECT_CLAIM_SUBMITTED = 'project-claim.submitted';

    const PROJECT_CLAIM_APPROVED = 'project-claim.approved';

    const PROJECT_CLAIM_REJECTED = 'project-claim.rejected';

    const PROJECT_CLAIM_PAID = 'project-claim.paid';

    const SELF_BILLED_CREATED = 'self-billed.created';

    const SELF_BILLED_APPROVED = 'self-billed.approved';

    const SELF_BILLED_REJECTED = 'self-billed.rejected';

    const SELF_BILLED_SUBMITTED = 'self-billed.submitted';

    const SELF_BILLED_PAID = 'self-billed.paid';

    const INVOICE_ISSUED = 'invoice.issued';

    const INVOICE_PAID = 'invoice.paid';

    const INVOICE_PARTIAL_PAYMENT = 'invoice.partial-payment';

    const PHASE_STARTED = 'phase.started';

    const PHASE_COMPLETED = 'phase.completed';

    const TASK_ASSIGNED = 'task.assigned';

    const TASK_COMPLETED = 'task.completed';

    const PROJECT_CREATED = 'project.created';

    const PROJECT_ASSIGNED = 'project.assigned';

    const PROJECT_COMPLETED = 'project.completed';

    const EINVOICE_SUBMITTED = 'einvoice.submitted';

    const EINVOICE_CANCELLED = 'einvoice.cancelled';

    const EINVOICE_FAILED = 'einvoice.failed';

    const QUOTE_SUBMITTED = 'quote.submitted';

    const QUOTE_ACCEPTED = 'quote.accepted';

    const QUOTE_CONVERTED = 'quote.converted';

    const QUOTE_DECLINED = 'quote.declined';

    const CONTRACT_CREATED = 'contract.created';

    const CONTRACT_ACTIVATED = 'contract.activated';

    const CONTRACT_COMPLETED = 'contract.completed';

    const PO_SUBMITTED = 'po.submitted';

    const PO_RECEIVED = 'po.received';

    const PAYSLIP_AVAILABLE = 'payslip.available';

    const ATTENDANCE_FLAGGED = 'attendance.flagged';

    const LICENSE_EXPIRING = 'license.expiring';

    const INVENTORY_LOW_STOCK = 'inventory.low-stock';
}
