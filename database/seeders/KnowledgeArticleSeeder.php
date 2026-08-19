<?php

namespace Database\Seeders;

use App\Models\KnowledgeArticle;
use Illuminate\Database\Seeder;

class KnowledgeArticleSeeder extends Seeder
{
    /**
     * Full user-manual content for AVSB-ERP. Idempotent — keyed on unique slug.
     * Run standalone: php artisan db:seed --class=KnowledgeArticleSeeder
     */
    public function run(): void
    {
        $articles = [
            // ─────────────────────────── Getting started ───────────────────────────
            [
                'slug' => 'getting-started-with-avsb-erp',
                'title' => 'Getting started with AVSB-ERP',
                'category' => 'how-to',
                'module' => 'dashboard',
                'summary' => 'Your first day: logging in, understanding roles, and where to find help.',
                'sort_order' => 10,
                'is_published' => true,
                'tags' => ['getting started', 'login', 'onboarding', 'dashboard'],
                'body' => <<<'HTML'
<h3>Log in</h3>
<ol>
<li>Open your browser and go to the AVSB-ERP address provided by your administrator.</li>
<li>Enter your work email and password.</li>
<li>If you enter a wrong password five times, your account locks for 15 minutes as a security measure.</li>
</ol>
<h4>Roles</h4>
<p>Your role decides which pages you see and what you can do. The main roles are <strong>staff</strong>, <strong>project manager (pm)</strong>, <strong>HR</strong>, <strong>finance</strong>, <strong>admin</strong>, and <strong>super admin</strong>. Staff see operational pages; managers see projects and approvals; HR sees team, payroll, and leave; finance sees the full billing and accounting stack.</p>
<h4>Finding help</h4>
<p>Open the <strong>Knowledge Base</strong> from the Help &amp; Docs section of the sidebar. Search for a topic, or browse by category: How-To, FAQ, Statutory, and Reference.</p>
<blockquote>If a page is missing from your sidebar, your role does not have access to it. Contact an admin if you believe that is wrong.</blockquote>
HTML,
            ],
            [
                'slug' => 'understanding-the-dashboard',
                'title' => 'Understanding your dashboard',
                'category' => 'reference',
                'module' => 'dashboard',
                'summary' => 'An overview of the role-based dashboard and what each section shows.',
                'sort_order' => 20,
                'is_published' => true,
                'tags' => ['dashboard', 'overview', 'reference'],
                'body' => <<<'HTML'
<h3>Role-based view</h3>
<p>The dashboard adapts to your role. Staff see operational information, HR see team and leave summaries, and finance see invoicing and outstanding amounts.</p>
<h4>Staff</h4>
<p>Staff see their own today view: attendance status, outstanding tasks, recent leave, and claims.</p>
<h4>HR and finance</h4>
<p>HR see team headcount, leave requests, and payroll status. Finance see invoiced amounts, outstanding balances, and payment summaries.</p>
<h4>Project managers</h4>
<p>PMs see project health, task completion, and team attendance at a glance.</p>
HTML,
            ],
            [
                'slug' => 'changing-your-password',
                'title' => 'Changing your password',
                'category' => 'faq',
                'module' => 'settings',
                'summary' => 'How to update your password and the requirements for a strong one.',
                'sort_order' => 30,
                'is_published' => true,
                'tags' => ['password', 'security', 'settings', 'faq'],
                'body' => <<<'HTML'
<h3>Steps</h3>
<ol>
<li>Open the settings menu and choose <strong>Change Password</strong>.</li>
<li>Enter your current password.</li>
<li>Enter a new password that is at least 8 characters long and contains an uppercase letter, a lowercase letter, and a digit.</li>
<li>Confirm the new password and save.</li>
</ol>
<h4>If you forgot your password</h4>
<p>Contact a super admin to reset it for you. Password resets do not lock your account.</p>
HTML,
            ],
            [
                'slug' => 'why-was-my-login-blocked',
                'title' => 'Why was my login blocked?',
                'category' => 'faq',
                'module' => 'auth',
                'summary' => 'Login lockout after repeated failed attempts, and how to recover.',
                'sort_order' => 40,
                'is_published' => true,
                'tags' => ['login', 'locked', 'password', 'security', 'faq'],
                'body' => <<<'HTML'
<h3>Lockout</h3>
<p>After five failed login attempts your account is locked for <strong>15 minutes</strong> as a security measure.</p>
<h4>What to do</h4>
<p>Wait for the lock to expire, or contact a super admin to reset your password. All login attempts, including failures and lockouts, are recorded in the audit log.</p>
<blockquote>Locked accounts still show a clear message so you know why you cannot sign in.</blockquote>
HTML,
            ],
            [
                'slug' => 'roles-and-access-explained',
                'title' => 'Roles and access explained',
                'category' => 'reference',
                'module' => 'auth',
                'summary' => 'What each role can do and how the sidebar reflects your access.',
                'sort_order' => 50,
                'is_published' => true,
                'tags' => ['roles', 'access', 'permissions', 'staff', 'admin'],
                'body' => <<<'HTML'
<h3>The roles</h3>
<ul>
<li><strong>Staff</strong> — attendance, punch, leaves, payslips, claims, and the knowledge base.</li>
<li><strong>Project manager</strong> — staff access plus projects, clients, attendance, approvals, and subcontractors.</li>
<li><strong>HR</strong> — staff access plus team, payroll, leave groups, part-time wages, and claims approvals.</li>
<li><strong>Finance</strong> — staff access plus the full finance stack: quotations, contracts, invoices, self-billed, purchase orders, bills, inventory, payments, and accounting.</li>
<li><strong>Admin</strong> — all application pages except super-admin-only tools.</li>
<li><strong>Super admin</strong> — everything, including user management, system health, and knowledge management.</li>
</ul>
<h4>Where access is enforced</h4>
<p>The sidebar only shows pages your role can use. The backend enforces the same rules, so hiding a page is never a security boundary by itself.</p>
<blockquote>User management and system health are super-admin-only. Knowledge management is also super-admin-only.</blockquote>
HTML,
            ],

            // ─────────────────────────── Punch & attendance ───────────────────────────
            [
                'slug' => 'clock-in-out-from-site',
                'title' => 'Clock in and out from site',
                'category' => 'how-to',
                'module' => 'punch',
                'summary' => 'How to punch your attendance from the field, including geofence and photo requirements.',
                'sort_order' => 60,
                'is_published' => true,
                'tags' => ['punch', 'attendance', 'clock in', 'geofence', 'photo'],
                'body' => <<<'HTML'
<h3>Before you start</h3>
<p>Punch requires you to be inside an active geofenced site with a valid GPS location and a photo taken at the location.</p>
<h4>Clock in</h4>
<ol>
<li>Open the <strong>Punch</strong> page from the sidebar.</li>
<li>Allow location access and stay within the geofence.</li>
<li>Take a clear photo of your current location.</li>
<li>Tap <strong>Clock In</strong>.</li>
</ol>
<h4>Clock out</h4>
<ol>
<li>Stay within the geofenced site.</li>
<li>Take a photo of the completed work area.</li>
<li>Tap <strong>Clock Out</strong>.</li>
</ol>
<blockquote>If you are outside the geofence or your GPS accuracy is poor, the app blocks the punch and explains why.</blockquote>
HTML,
            ],
            [
                'slug' => 'geofence-enforcement-explained',
                'title' => 'Geofence enforcement explained',
                'category' => 'reference',
                'module' => 'punch',
                'summary' => 'How geofenced sites control where you can clock in and out.',
                'sort_order' => 70,
                'is_published' => true,
                'tags' => ['geofence', 'punch', 'location', 'enforcement'],
                'body' => <<<'HTML'
<h3>What a geofence is</h3>
<p>A geofence is a virtual circle around a site location, defined by a centre point and a radius. Active projects with coordinates get geofenced automatically, and admins can manage sites on the Geofence Sites page.</p>
<h4>How it affects punching</h4>
<ul>
<li>You must be inside an active geofence to clock in or out.</li>
<li>Your GPS accuracy must be within 50 metres.</li>
<li>If no geofenced sites are configured, punching is blocked with a clear message.</li>
</ul>
<h4>Relaxed mode</h4>
<p>Company settings can switch geofence enforcement off. When off, you can punch from anywhere, but coordinates are still recorded.</p>
HTML,
            ],
            [
                'slug' => 'gps-accuracy-requirements',
                'title' => 'GPS accuracy requirements for punching',
                'category' => 'faq',
                'module' => 'punch',
                'summary' => 'Why accuracy matters and how to improve your GPS signal on site.',
                'sort_order' => 80,
                'is_published' => true,
                'tags' => ['gps', 'accuracy', 'location', 'punch', 'faq'],
                'body' => <<<'HTML'
<h3>Why it matters</h3>
<p>The app needs a reliable location to prove you were on site. Punches are rejected when GPS accuracy is worse than <strong>50 metres</strong>.</p>
<h4>Improving accuracy</h4>
<ul>
<li>Stand outside, away from buildings and heavy machinery.</li>
<li>Keep your phone's location services on and set to high accuracy.</li>
<li>Wait a few seconds for the GPS to settle before punching.</li>
</ul>
<blockquote>An indoor punch with poor GPS will be rejected. Move to an open area and retry.</blockquote>
HTML,
            ],
            [
                'slug' => 'punch-photo-requirements',
                'title' => 'Photo requirements for punching',
                'category' => 'faq',
                'module' => 'punch',
                'summary' => 'What photos are needed at clock-in and clock-out.',
                'sort_order' => 90,
                'is_published' => true,
                'tags' => ['photo', 'punch', 'clock in', 'clock out', 'faq'],
                'body' => <<<'HTML'
<h3>When a photo is required</h3>
<p>Both clock-in and clock-out need a photo. At clock-in, take a photo of your current location. At clock-out, take a photo of the completed work area.</p>
<h4>Photo quality</h4>
<ul>
<li>Keep the photo clear and well lit.</li>
<li>Make sure the surroundings are recognisable.</li>
<li>Blurry or dark photos may be rejected or flagged for review.</li>
</ul>
<p>Photos are stored with the attendance record and visible to HR and managers.</p>
HTML,
            ],
            [
                'slug' => 'auto-clock-out-explained',
                'title' => 'Auto clock-out: how it works',
                'category' => 'reference',
                'module' => 'punch',
                'summary' => 'When the system closes an open attendance session for you automatically.',
                'sort_order' => 100,
                'is_published' => true,
                'tags' => ['auto clock out', 'attendance', 'punch', 'reference'],
                'body' => <<<'HTML'
<h3>When it happens</h3>
<p>If you forget to clock out, the system closes your session at your scheduled end-of-work time plus a grace period. The session is flagged as <strong>auto-closed</strong> for review.</p>
<h4>What you should do</h4>
<p>If you are still working, clock in again and continue. The auto-closed session does not cut your overtime; a new clock-in records the rest of your day.</p>
<h4>Company controls</h4>
<p>Admins can enable or disable auto clock-out and set the grace period in company settings. It only applies to staff with scheduled work hours.</p>
HTML,
            ],
            [
                'slug' => 'schedule-windows-and-flagging',
                'title' => 'Work schedule windows and flagging',
                'category' => 'reference',
                'module' => 'attendance',
                'summary' => 'How start and end times are used to flag early or late punches.',
                'sort_order' => 110,
                'is_published' => true,
                'tags' => ['schedule', 'work hours', 'flagging', 'attendance', 'late'],
                'body' => <<<'HTML'
<h3>Schedule windows</h3>
<p>Company settings define a default work start and end time. Individual staff can override these on their staff profile.</p>
<h4>Flagging</h4>
<p>Punches outside the scheduled window, with a 15-minute grace period, are flagged as <strong>schedule flagged</strong> on the attendance record. HR can see the reason at a glance.</p>
<h4>Overnight shifts</h4>
<p>Shifts that cross midnight are supported — a window ending before it starts is treated as ending the next morning.</p>
<blockquote>If no work hours are configured for a staff member, schedule flagging does not apply.</blockquote>
HTML,
            ],
            [
                'slug' => 'attendance-records-and-reports',
                'title' => 'Viewing attendance records and reports',
                'category' => 'how-to',
                'module' => 'attendance',
                'summary' => 'Where to find punches, monthly views, and the CSV export.',
                'sort_order' => 120,
                'is_published' => true,
                'tags' => ['attendance', 'records', 'monthly view', 'csv', 'export'],
                'body' => <<<'HTML'
<h3>Attendance page</h3>
<p>Open <strong>Attendance</strong> to see all punches. Use the date range to move between days and months.</p>
<h4>Monthly view</h4>
<p>Switch to the monthly view to see each staff member's totals: days worked and total hours.</p>
<h4>Export</h4>
<p>Export the current view as CSV for payroll or reporting. The export includes clock-in and clock-out coordinates and any schedule flags.</p>
<blockquote>Super admin accounts are excluded from attendance listings and totals so they do not distort the team view.</blockquote>
HTML,
            ],
            [
                'slug' => 'multiple-punches-per-day',
                'title' => 'Can I punch more than once a day?',
                'category' => 'faq',
                'module' => 'attendance',
                'summary' => 'Why you may see several attendance records on one day.',
                'sort_order' => 130,
                'is_published' => true,
                'tags' => ['punch', 'multiple', 'attendance', 'faq', 're-punch'],
                'body' => <<<'HTML'
<h3>Yes — each punch is its own session</h3>
<p>Every clock-in creates a new attendance record. If you clock out and clock back in (for example, after an auto clock-out while still working), you get a second record for the same day.</p>
<h4>Why it matters</h4>
<p>Each session keeps its own times, coordinates, and photos. HR or a manager can correct a session if the times are wrong.</p>
HTML,
            ],

            // ─────────────────────────── Leaves ───────────────────────────
            [
                'slug' => 'request-and-withdraw-leave',
                'title' => 'Request and withdraw leave',
                'category' => 'how-to',
                'module' => 'leaves',
                'summary' => 'Apply for annual and other leave types, and withdraw a pending or approved leave.',
                'sort_order' => 140,
                'is_published' => true,
                'tags' => ['leave', 'annual', 'withdraw', 'balance', 'holiday'],
                'body' => <<<'HTML'
<h3>Request leave</h3>
<ol>
<li>Open <strong>Leaves</strong> from the sidebar.</li>
<li>Choose a leave type and the start and end dates.</li>
<li>Check your remaining balance.</li>
<li>Submit for approval.</li>
</ol>
<h4>Withdraw a leave</h4>
<ol>
<li>Find the leave in your list.</li>
<li>Tap <strong>Withdraw</strong>.</li>
<li>Pending leaves cancel immediately; approved leaves restore your balance only if they have not started.</li>
</ol>
<blockquote>Weekends and public holidays are excluded from your leave day count automatically.</blockquote>
HTML,
            ],
            [
                'slug' => 'leave-entitlement-rules',
                'title' => 'How leave balances are calculated',
                'category' => 'statutory',
                'module' => 'leaves',
                'summary' => 'Understand entitlements, balances, and how holidays affect your leave days.',
                'sort_order' => 150,
                'is_published' => true,
                'tags' => ['leave', 'balance', 'entitlement', 'statutory', 'annual'],
                'body' => <<<'HTML'
<h3>Balance</h3>
<p>Your balance is <strong>entitled - used + adjusted</strong>. When a leave is approved, the working days are deducted; when a leave is withdrawn before starting, they are restored.</p>
<h4>Working days</h4>
<p>Leave day counts skip weekends and public holidays automatically, so a request spanning a weekend only counts the working days in between.</p>
<h4>Adjustments</h4>
<p>HR can adjust a staff member's balance directly, which is reflected in the adjusted component of the formula.</p>
HTML,
            ],
            [
                'slug' => 'public-holidays-and-leave',
                'title' => 'Public holidays and leave',
                'category' => 'reference',
                'module' => 'leaves',
                'summary' => 'How public holidays are managed and why they affect leave.',
                'sort_order' => 160,
                'is_published' => true,
                'tags' => ['public holiday', 'leave', 'holiday', 'reference'],
                'body' => <<<'HTML'
<h3>Holiday calendar</h3>
<p>The Public Holidays page holds Malaysian holidays, both recurring (same date every year) and one-off dates. The default set covers New Year, Labour Day, National Day, Malaysia Day, and Christmas.</p>
<h4>Effect on leave</h4>
<ul>
<li>Holidays are excluded from leave day counts.</li>
<li>Leave spanning a holiday does not consume leave days for that date.</li>
<li>If every selected day is a holiday or weekend, the request is rejected.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'leave-approvals-explained',
                'title' => 'The leave approval flow',
                'category' => 'how-to',
                'module' => 'leaves',
                'summary' => 'How leave requests move from pending to approved, and who approves them.',
                'sort_order' => 170,
                'is_published' => true,
                'tags' => ['leave', 'approval', 'approve', 'reject', 'queue'],
                'body' => <<<'HTML'
<h3>Flow</h3>
<ol>
<li>Staff submit a leave request with dates and a reason.</li>
<li>The request appears in the <strong>Leave Approvals</strong> queue for managers and HR.</li>
<li>An approver approves or rejects it.</li>
<li>On approval, the balance is updated; on rejection, nothing is deducted.</li>
</ol>
<h4>Withdrawing after approval</h4>
<p>An approved leave can be withdrawn before it starts, which restores the balance. Once it has started, it can no longer be withdrawn.</p>
HTML,
            ],
            [
                'slug' => 'leave-groups-and-balances',
                'title' => 'Leave groups and entitlements',
                'category' => 'reference',
                'module' => 'leave-groups',
                'summary' => 'How HR sets entitlements per staff group and leave type.',
                'sort_order' => 180,
                'is_published' => true,
                'tags' => ['leave group', 'entitlement', 'balance', 'annual', 'hr'],
                'body' => <<<'HTML'
<h3>Leave groups</h3>
<p>HR groups staff into leave groups, each defining entitlements per leave type and year. A staff member's balance comes from their group.</p>
<h4>Types</h4>
<p>Common types are annual, medical, and unpaid. Each type can have its own entitlement and carryover rules.</p>
<blockquote>Changing a group's entitlement does not retroactively change already-used leave days.</blockquote>
HTML,
            ],

            // ─────────────────────────── Claims & approvals ───────────────────────────
            [
                'slug' => 'file-an-expense-claim',
                'title' => 'File an expense claim',
                'category' => 'how-to',
                'module' => 'claims',
                'summary' => 'Submit travel, material, and other expenses for approval.',
                'sort_order' => 190,
                'is_published' => true,
                'tags' => ['claim', 'expense', 'reimbursement', 'approval'],
                'body' => <<<'HTML'
<h3>Create a claim</h3>
<ol>
<li>Open <strong>My Claims</strong> from the sidebar.</li>
<li>Tap <strong>New Claim</strong> and choose a category.</li>
<li>Add line items with a description, date, and amount.</li>
<li>Attach any receipts or supporting documents.</li>
<li>Submit for approval.</li>
</ol>
<h4>What happens next</h4>
<p>Your approver reviews the claim. You are notified when it is approved or rejected. Approved claims flow into payroll or the payment hub depending on your company settings.</p>
HTML,
            ],
            [
                'slug' => 'claims-approval-queue',
                'title' => 'The claims approval queue',
                'category' => 'how-to',
                'module' => 'approvals',
                'summary' => 'How HR and finance review and decide on expense claims.',
                'sort_order' => 200,
                'is_published' => true,
                'tags' => ['claim', 'approval', 'approve', 'reject', 'finance', 'hr'],
                'body' => <<<'HTML'
<h3>Reviewing claims</h3>
<ol>
<li>Open <strong>Claims Approvals</strong> or the <strong>Approval Hub</strong>.</li>
<li>Open a claim to see line items and attached receipts.</li>
<li>Approve or reject, adding a reason when rejecting.</li>
</ol>
<h4>After approval</h4>
<p>Approved claims move into the payment flow or payroll depending on the claim type and company policy.</p>
HTML,
            ],
            [
                'slug' => 'approval-hub-explained',
                'title' => 'The Approval Hub',
                'category' => 'reference',
                'module' => 'approvals',
                'summary' => 'One place to review leave, claims, payments, and subcontractor requests.',
                'sort_order' => 210,
                'is_published' => true,
                'tags' => ['approval hub', 'approvals', 'leave', 'claim', 'payment'],
                'body' => <<<'HTML'
<h3>What it shows</h3>
<p>The Approval Hub collects every queue you are allowed to approve: leave requests, expense claims, project claims, subcontractor claims, and payment approvals.</p>
<h4>Role-based queues</h4>
<ul>
<li>Managers approve leave.</li>
<li>HR and finance approve claims.</li>
<li>Finance approves project claims, subcontractor claims, and payments.</li>
</ul>
<p>Each queue filters to the item types your role may decide on.</p>
HTML,
            ],
            [
                'slug' => 'what-happens-after-claim-approval',
                'title' => 'What happens after a claim is approved',
                'category' => 'reference',
                'module' => 'claims',
                'summary' => 'The path from approval to payment or payslip.',
                'sort_order' => 220,
                'is_published' => true,
                'tags' => ['claim', 'approved', 'payment', 'payroll', 'reference'],
                'body' => <<<'HTML'
<h3>Approved and next</h3>
<p>Once approved, a claim is either paid through the <strong>Payment Hub</strong> or included in the next payroll run, depending on the claim type.</p>
<ul>
<li>Expense claims — paid via the payment hub or payroll.</li>
<li>Project claims — reviewed and paid by finance.</li>
<li>Subcontractor claims — paid against the subcontractor.</li>
</ul>
<p>Payments against claims create journal entries automatically so accounting stays in sync.</p>
HTML,
            ],

            // ─────────────────────────── Payroll ───────────────────────────
            [
                'slug' => 'epf-socso-eis-explained',
                'title' => 'EPF, SOCSO, and EIS contributions',
                'category' => 'statutory',
                'module' => 'payroll',
                'summary' => 'How employee and employer statutory contributions are calculated in payroll.',
                'sort_order' => 230,
                'is_published' => true,
                'tags' => ['epf', 'socso', 'eis', 'payroll', 'statutory', 'kwsp'],
                'body' => <<<'HTML'
<h3>Statutory contributions</h3>
<p>Payroll calculates three main statutory contributions:</p>
<ul>
<li><strong>EPF (KWSP)</strong> — retirement savings, split between employee and employer.</li>
<li><strong>SOCSO (PERKESO)</strong> — employment injury and invalidity protection.</li>
<li><strong>EIS</strong> — employment insurance for job loss support.</li>
</ul>
<h4>Opting out</h4>
<p>Individual staff can be marked as not contributing to EPF or SOCSO from their staff profile. Contributions are then calculated as zero for that staff member while other rules still apply.</p>
<h4>Schedules and tiers</h4>
<p>Contribution rates come from the statutory tables maintained by an administrator, matched to the staff member's wage band and schedule.</p>
HTML,
            ],
            [
                'slug' => 'skbbk-socso-24h-explained',
                'title' => 'SKBBK (Socso 24-hour) coverage',
                'category' => 'statutory',
                'module' => 'payroll',
                'summary' => 'The optional 24-hour SOCSO scheme and how it is applied.',
                'sort_order' => 240,
                'is_published' => true,
                'tags' => ['skbbk', 'socso 24h', 'socso', 'payroll', 'statutory'],
                'body' => <<<'HTML'
<h3>What SKBBK is</h3>
<p>SKBBK extends SOCSO coverage to 24 hours a day, including outside work. It is optional and has its own contribution phase and bracket table.</p>
<h4>How it is applied</h4>
<p>Payroll adds the SKBBK contribution when the staff member has SOCSO enabled and the company has the SKBBK phase active. The active bracket is configured in company settings.</p>
HTML,
            ],
            [
                'slug' => 'pcb-tax-deduction-explained',
                'title' => 'PCB tax deductions',
                'category' => 'statutory',
                'module' => 'payroll',
                'summary' => 'How monthly income tax deductions are calculated for staff.',
                'sort_order' => 250,
                'is_published' => true,
                'tags' => ['pcb', 'tax', 'income tax', 'payroll', 'statutory'],
                'body' => <<<'HTML'
<h3>Monthly tax deduction</h3>
<p>PCB (Potongan Cukai Bulanan) is the monthly income tax deducted from salaries. It is calculated from the staff member's wages and tax schedule.</p>
<h4>Citizenship matters</h4>
<p>Malaysian citizens and permanent residents follow the standard schedule; non-citizens are treated under the flat-rate rules. Keep nationality up to date on the staff profile.</p>
<blockquote>Tax calculations are estimates for payroll purposes. Annual filing is handled with the LHDN forms by your finance team.</blockquote>
HTML,
            ],
            [
                'slug' => 'payroll-periods-and-runs',
                'title' => 'Payroll periods and runs',
                'category' => 'how-to',
                'module' => 'payroll',
                'summary' => 'How HR creates payroll periods, runs payroll, and reviews pay items.',
                'sort_order' => 260,
                'is_published' => true,
                'tags' => ['payroll', 'period', 'run', 'salary', 'wages', 'hr'],
                'body' => <<<'HTML'
<h3>Create a period</h3>
<ol>
<li>Open <strong>Payroll</strong>.</li>
<li>Create a payroll period with a name and date range.</li>
<li>Run payroll for the period — the system generates a pay item for every active staff member.</li>
</ol>
<h4>What a run calculates</h4>
<p>Each pay item includes base wages, approved claims, allowances, and the statutory deductions (EPF, SOCSO, EIS, PCB, SKBBK) where applicable.</p>
<h4>Review</h4>
<p>Review each pay item before confirming. Adjustments can be made for corrections such as overtime or allowances.</p>
HTML,
            ],
            [
                'slug' => 'adjusting-a-pay-item',
                'title' => 'Adjusting a pay item',
                'category' => 'how-to',
                'module' => 'payroll',
                'summary' => 'How to adjust earnings and recalculate statutory deductions.',
                'sort_order' => 270,
                'is_published' => true,
                'tags' => ['payroll', 'adjustment', 'pay item', 'earnings', 'recalculate'],
                'body' => <<<'HTML'
<h3>Open the pay item</h3>
<ol>
<li>From the payroll period, open the pay item you need to change.</li>
<li>Edit earnings or add an adjustment, for example overtime or a deduction.</li>
<li>Recalculate statutory deductions so EPF, SOCSO, EIS, and PCB reflect the new amount.</li>
<li>Save.</li>
</ol>
<h4>Confirm and mark paid</h4>
<p>Once all items look correct, confirm the period. After confirmation, items are marked paid when salaries are disbursed.</p>
HTML,
            ],
            [
                'slug' => 'marking-payroll-as-paid',
                'title' => 'Confirming and paying payroll',
                'category' => 'how-to',
                'module' => 'payroll',
                'summary' => 'The confirmation and payment steps at the end of a payroll cycle.',
                'sort_order' => 280,
                'is_published' => true,
                'tags' => ['payroll', 'confirm', 'paid', 'disbursement', 'pay'],
                'body' => <<<'HTML'
<h3>Confirm the period</h3>
<p>When every pay item is correct, confirm the payroll period. Confirmation locks the items for payment processing.</p>
<h4>Mark paid</h4>
<p>After salaries are disbursed, mark the period paid. This closes the payroll cycle and makes payslips available to staff.</p>
<blockquote>Confirming is a significant step — review every item before confirming.</blockquote>
HTML,
            ],
            [
                'slug' => 'understanding-your-payslip',
                'title' => 'Understanding your payslip',
                'category' => 'reference',
                'module' => 'payslips',
                'summary' => 'How to read your payslip: earnings, deductions, and net pay.',
                'sort_order' => 290,
                'is_published' => true,
                'tags' => ['payslip', 'net pay', 'earnings', 'deductions', 'payroll'],
                'body' => <<<'HTML'
<h3>Finding your payslip</h3>
<p>Open <strong>My Payslips</strong> to download payslips for your pay periods.</p>
<h4>Reading it</h4>
<ul>
<li><strong>Earnings</strong> — base wages plus allowances and approved claims.</li>
<li><strong>Deductions</strong> — EPF, SOCSO, EIS, PCB, and SKBBK where applicable.</li>
<li><strong>Net pay</strong> — earnings minus deductions and any advances.</li>
</ul>
<p>If a number looks wrong, contact HR before the payroll period is confirmed.</p>
HTML,
            ],
            [
                'slug' => 'part-time-wages-explained',
                'title' => 'Part-time wages',
                'category' => 'reference',
                'module' => 'part-time',
                'summary' => 'How part-time staff are paid and why they are handled separately.',
                'sort_order' => 300,
                'is_published' => true,
                'tags' => ['part-time', 'wages', 'worker', 'payroll'],
                'body' => <<<'HTML'
<h3>Separate treatment</h3>
<p>Part-time staff are tracked with a part-time worker status. They are paid from the part-time wages page rather than the main payroll run.</p>
<h4>Attendance</h4>
<p>Part-time sessions are not auto-clocked-out and are excluded from full-time schedule flagging, so the system does not make assumptions about their work hours.</p>
HTML,
            ],

            // ─────────────────────────── Quotations & contracts ───────────────────────────
            [
                'slug' => 'creating-a-quotation',
                'title' => 'Creating a quotation',
                'category' => 'how-to',
                'module' => 'quotations',
                'summary' => 'How to build a quotation with services, SST, and retention.',
                'sort_order' => 310,
                'is_published' => true,
                'tags' => ['quotation', 'quote', 'sst', 'service', 'finance'],
                'body' => <<<'HTML'
<h3>Build a quotation</h3>
<ol>
<li>Open <strong>Quotations</strong> from the finance section.</li>
<li>Select the client and project.</li>
<li>Add service lines with quantities and rates.</li>
<li>Review the subtotal, the 8% SST, and any retention percentage.</li>
<li>Issue the quotation to the client.</li>
</ol>
<h4>Numbering</h4>
<p>Quotation numbers are generated by the system — never type them manually.</p>
HTML,
            ],
            [
                'slug' => 'converting-quotation-to-contract',
                'title' => 'Converting a quotation to a contract',
                'category' => 'how-to',
                'module' => 'contracts',
                'summary' => 'How an accepted quotation becomes a contract.',
                'sort_order' => 320,
                'is_published' => true,
                'tags' => ['contract', 'quotation', 'convert', 'milestone'],
                'body' => <<<'HTML'
<h3>From quote to contract</h3>
<ol>
<li>Open the accepted quotation.</li>
<li>Choose to create a contract from it.</li>
<li>The contract carries the services, amounts, SST, and retention across.</li>
<li>Add milestones and any variations.</li>
</ol>
<h4>Milestones</h4>
<p>Milestones define billing points in the project. Progress invoicing draws on the contract's milestones.</p>
HTML,
            ],
            [
                'slug' => 'contract-milestones-and-variations',
                'title' => 'Contract milestones and variations',
                'category' => 'reference',
                'module' => 'contracts',
                'summary' => 'How billing milestones and scope variations work on contracts.',
                'sort_order' => 330,
                'is_published' => true,
                'tags' => ['contract', 'milestone', 'variation', 'billing'],
                'body' => <<<'HTML'
<h3>Milestones</h3>
<p>Each contract can define billing milestones — a percentage or amount billed at specific project stages. Invoices are raised against these milestones for progress billing.</p>
<h4>Variations</h4>
<p>A variation records a change in scope or cost after the contract is signed. Variations adjust the contract value and are tracked for the full contract history.</p>
HTML,
            ],

            // ─────────────────────────── Invoices ───────────────────────────
            [
                'slug' => 'creating-an-invoice',
                'title' => 'Creating an invoice',
                'category' => 'how-to',
                'module' => 'invoices',
                'summary' => 'How to raise an invoice from a project or contract.',
                'sort_order' => 340,
                'is_published' => true,
                'tags' => ['invoice', 'create', 'bill', 'finance'],
                'body' => <<<'HTML'
<h3>Raise an invoice</h3>
<ol>
<li>Open <strong>Invoices</strong> from the finance section.</li>
<li>Create a new invoice for a client and project.</li>
<li>Add service lines, or generate from a project's completed work.</li>
<li>Review subtotal, SST, and retention.</li>
<li>Issue the invoice — issuing creates the accounts receivable journal entry.</li>
</ol>
<h4>Invoice statuses</h4>
<p>Invoices move through issued, partially paid, and paid as payments are recorded against them.</p>
HTML,
            ],
            [
                'slug' => 'record-a-payment',
                'title' => 'Record a payment on an invoice',
                'category' => 'how-to',
                'module' => 'invoices',
                'summary' => 'How to record client payments and how they update the accounting journal.',
                'sort_order' => 350,
                'is_published' => true,
                'tags' => ['invoice', 'payment', 'payment hub', 'journal'],
                'body' => <<<'HTML'
<h3>Record a payment</h3>
<ol>
<li>Open an invoice from the <strong>Invoices</strong> page.</li>
<li>Tap <strong>Record Payment</strong>.</li>
<li>Enter the amount and payment date.</li>
<li>Save. The invoice updates to paid or partially paid.</li>
</ol>
<h4>Journal effect</h4>
<p>Recording a payment creates a journal entry (debit bank, credit accounts receivable). This keeps your accounting in sync automatically.</p>
HTML,
            ],
            [
                'slug' => 'what-is-sst',
                'title' => 'What is SST and how is it applied?',
                'category' => 'statutory',
                'module' => 'invoices',
                'summary' => 'Malaysian 8% Service Tax applied to quotations and invoices.',
                'sort_order' => 360,
                'is_published' => true,
                'tags' => ['sst', 'service tax', 'tax', 'statutory', 'malaysia'],
                'body' => <<<'HTML'
<h3>Service Tax (SST)</h3>
<p>Malaysia charges an <strong>8% Service Tax</strong> on taxable services. AVSB-ERP applies this automatically to quotations and invoices.</p>
<h4>Retention</h4>
<p>Retention is a configurable percentage held back from payments as security, tracked separately from SST.</p>
<blockquote>If you are unsure whether a service is taxable, confirm with your finance team before issuing a quotation.</blockquote>
HTML,
            ],
            [
                'slug' => 'retention-tracking-explained',
                'title' => 'Retention tracking',
                'category' => 'reference',
                'module' => 'invoices',
                'summary' => 'How retention percentages are held and tracked on invoices.',
                'sort_order' => 370,
                'is_published' => true,
                'tags' => ['retention', 'invoice', 'withholding', 'security'],
                'body' => <<<'HTML'
<h3>What retention is</h3>
<p>Retention is a percentage of the invoice amount held back as security until the work is accepted. It is configurable per project or contract.</p>
<h4>How it shows</h4>
<p>On an invoice, the retention appears separately from SST and the subtotal. The amount payable is the total minus retention until release.</p>
HTML,
            ],
            [
                'slug' => 'credit-notes-and-reversion',
                'title' => 'Credit notes and reverting invoices',
                'category' => 'how-to',
                'module' => 'invoices',
                'summary' => 'How to correct an invoice with a credit note or a revert.',
                'sort_order' => 380,
                'is_published' => true,
                'tags' => ['credit note', 'revert', 'invoice', 'correction'],
                'body' => <<<'HTML'
<h3>Credit note</h3>
<p>Use a credit note to reduce or cancel an invoice amount while keeping the invoice history. The credit note is tracked against the original invoice.</p>
<h4>Revert</h4>
<p>Reverting an invoice reverses its issue, removing the accounts receivable entry. Revert only works on invoices that have no recorded payments.</p>
<blockquote>Legacy imported invoices cannot be reverted or credited — only corrected through the payment flow.</blockquote>
HTML,
            ],
            [
                'slug' => 'legacy-invoice-import',
                'title' => 'Legacy invoice import',
                'category' => 'reference',
                'module' => 'invoices',
                'summary' => 'Bringing existing invoices into the system with payment history.',
                'sort_order' => 390,
                'is_published' => true,
                'tags' => ['legacy', 'import', 'invoice', 'migration', 'csv'],
                'body' => <<<'HTML'
<h3>Importing existing invoices</h3>
<p>Invoices from a previous system can be imported with their number, amount, status, dates, and payment history.</p>
<h4>What an import does</h4>
<ul>
<li>Creates the invoice marked as <strong>legacy</strong>.</li>
<li>Records the payment history if the invoice was paid.</li>
<li>Creates matching journal entries for the issue and payment.</li>
</ul>
<h4>Limits</h4>
<p>Legacy invoices cannot be submitted as e-invoices or reverted. They support payment recording like normal invoices.</p>
HTML,
            ],
            [
                'slug' => 'shared-invoices-across-projects',
                'title' => 'Shared invoices across projects',
                'category' => 'reference',
                'module' => 'invoices',
                'summary' => 'How one invoice can cover work from multiple projects.',
                'sort_order' => 400,
                'is_published' => true,
                'tags' => ['shared invoice', 'invoice', 'multi-project', 'pivot'],
                'body' => <<<'HTML'
<h3>When invoices are shared</h3>
<p>Some clients issue one purchase order covering several projects. In those cases a single invoice can be linked to multiple projects.</p>
<h4>How it works</h4>
<ul>
<li>The invoice has a primary owning project and can be shared with others.</li>
<li>Each project sees the shared invoice in its invoice list.</li>
<li>A payment on a shared invoice is a single full-total payment.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'issuing-invoices-from-projects',
                'title' => 'Issuing an invoice from a project',
                'category' => 'how-to',
                'module' => 'invoices',
                'summary' => 'Generating an invoice directly from a project\'s completed work.',
                'sort_order' => 410,
                'is_published' => true,
                'tags' => ['invoice', 'project', 'generate', 'finance'],
                'body' => <<<'HTML'
<h3>Generate from a project</h3>
<ol>
<li>Open the project.</li>
<li>Use the invoice action in the project menu.</li>
<li>Review the services and amounts pulled from the project.</li>
<li>Issue the invoice.</li>
</ol>
<h4>Requires manual action</h4>
<p>Invoices are never created automatically at project completion. Creating an invoice always requires a person to trigger it, so nothing is billed without review.</p>
HTML,
            ],
            [
                'slug' => 'e-invoice-submission',
                'title' => 'Submitting e-invoices (LHDN)',
                'category' => 'how-to',
                'module' => 'invoices',
                'summary' => 'How to submit invoices to LHDN and validate them through the e-invoice flow.',
                'sort_order' => 420,
                'is_published' => true,
                'tags' => ['einvoice', 'lhdn', 'invoice', 'submission'],
                'body' => <<<'HTML'
<h3>Submit an e-invoice</h3>
<ol>
<li>Open an issued invoice.</li>
<li>Tap <strong>Submit E-Invoice</strong>.</li>
<li>Review the submission details and confirm.</li>
<li>Track the status until it is validated.</li>
</ol>
<blockquote>Legacy imported invoices cannot be submitted as e-invoices. Only invoices raised within the system can be submitted.</blockquote>
HTML,
            ],
            [
                'slug' => 'self-billed-invoices-explained',
                'title' => 'Self-billed invoices for subcontractors',
                'category' => 'how-to',
                'module' => 'self-billed',
                'summary' => 'How to issue self-billed invoices when paying subcontractors.',
                'sort_order' => 430,
                'is_published' => true,
                'tags' => ['self-billed', 'subcontractor', 'invoice', 'finance'],
                'body' => <<<'HTML'
<h3>When to self-bill</h3>
<p>Self-billed invoices are issued when you are the payer — for example, paying a subcontractor or supplier who does not issue their own invoice.</p>
<h4>Create one</h4>
<ol>
<li>Open <strong>Self-Billed</strong> from the finance section.</li>
<li>Select the supplier or subcontractor.</li>
<li>Add the work and amount.</li>
<li>Issue and then record the payment.</li>
</ol>
HTML,
            ],

            // ─────────────────────────── Purchasing ───────────────────────────
            [
                'slug' => 'creating-a-purchase-order',
                'title' => 'Creating a purchase order',
                'category' => 'how-to',
                'module' => 'purchase-orders',
                'summary' => 'How to create and track purchase orders against projects.',
                'sort_order' => 440,
                'is_published' => true,
                'tags' => ['purchase order', 'po', 'procurement', 'finance'],
                'body' => <<<'HTML'
<h3>Create a PO</h3>
<ol>
<li>Open <strong>Purchase Orders</strong>.</li>
<li>Select the vendor and project.</li>
<li>Add line items with quantities and prices.</li>
<li>Submit the PO for approval and issue it.</li>
</ol>
<h4>Tracking</h4>
<p>POs are linked to projects so you can see committed spend against each project's budget.</p>
HTML,
            ],
            [
                'slug' => 'bills-and-bill-payments',
                'title' => 'Bills and bill payments',
                'category' => 'how-to',
                'module' => 'bills',
                'summary' => 'How supplier bills are received, approved, and paid.',
                'sort_order' => 450,
                'is_published' => true,
                'tags' => ['bill', 'bill payment', 'supplier', 'accounts payable'],
                'body' => <<<'HTML'
<h3>Receive a bill</h3>
<ol>
<li>Open <strong>Bills</strong>.</li>
<li>Create a bill from the vendor's invoice.</li>
<li>Match line items to the purchase order where possible.</li>
<li>Record the bill — receiving creates an accounts payable entry.</li>
</ol>
<h4>Pay a bill</h4>
<p>Record the payment against the bill. The payment clears the payable and updates the bank ledger.</p>
HTML,
            ],
            [
                'slug' => 'inventory-management',
                'title' => 'Inventory management',
                'category' => 'how-to',
                'module' => 'inventory',
                'summary' => 'Track materials, stock levels, and inventory transactions.',
                'sort_order' => 460,
                'is_published' => true,
                'tags' => ['inventory', 'stock', 'materials', 'items'],
                'body' => <<<'HTML'
<h3>Inventory items</h3>
<p>Open <strong>Inventory</strong> to manage stock items: descriptions, units, and quantities.</p>
<h4>Transactions</h4>
<p>Stock movements are recorded as transactions (in, out, adjustment). The item's quantity updates with each transaction, keeping a full history.</p>
<blockquote>Materials used on projects can also be tracked and tied to the project cost.</blockquote>
HTML,
            ],

            // ─────────────────────────── Payments & accounting ───────────────────────────
            [
                'slug' => 'payment-hub-explained',
                'title' => 'The Payment Hub',
                'category' => 'reference',
                'module' => 'payments',
                'summary' => 'One place to handle expense, project, subcontractor, and payroll payments.',
                'sort_order' => 470,
                'is_published' => true,
                'tags' => ['payment hub', 'payments', 'expense', 'payroll', 'finance'],
                'body' => <<<'HTML'
<h3>What it does</h3>
<p>The Payment Hub groups every money-out flow: expense claims, project claims, subcontractor claims, and payroll disbursement.</p>
<h4>Role access</h4>
<ul>
<li>Expense and payroll payments — HR and finance.</li>
<li>Project and subcontractor payments — finance.</li>
</ul>
<p>Each payment creates journal entries automatically, keeping accounts receivable and bank ledgers accurate.</p>
HTML,
            ],
            [
                'slug' => 'accounting-overview',
                'title' => 'Accounting overview',
                'category' => 'reference',
                'module' => 'accounting',
                'summary' => 'How the accounting module ties invoices, payments, and payroll together.',
                'sort_order' => 480,
                'is_published' => true,
                'tags' => ['accounting', 'journal', 'ledger', 'finance'],
                'body' => <<<'HTML'
<h3>Automatic entries</h3>
<p>Invoices, payments, bills, payroll, and claims all create journal entries automatically. The accounting module is where you review and report on those entries.</p>
<h4>Sections</h4>
<ul>
<li><strong>Journal</strong> — every entry, filterable by date and account.</li>
<li><strong>Chart of Accounts</strong> — the account list used by entries.</li>
<li><strong>Fiscal Periods</strong> — accounting periods and closing.</li>
<li><strong>Reports</strong> — trial balance, profit and loss, balance sheet, and aging.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'journal-entries-explained',
                'title' => 'Journal entries explained',
                'category' => 'reference',
                'module' => 'accounting',
                'summary' => 'How debits and credits are recorded for every business event.',
                'sort_order' => 490,
                'is_published' => true,
                'tags' => ['journal', 'journal entry', 'debit', 'credit', 'accounting'],
                'body' => <<<'HTML'
<h3>What a journal entry is</h3>
<p>Every business event — an invoice issue, a payment, a bill — writes a journal entry with at least one debit and one credit line. Debits and credits always balance.</p>
<h4>Examples</h4>
<ul>
<li>Issuing an invoice — debit accounts receivable, credit revenue.</li>
<li>Receiving a payment — debit bank, credit accounts receivable.</li>
<li>Receiving a bill — debit expense, credit accounts payable.</li>
</ul>
<h4>Manual entries</h4>
<p>Finance can post manual journal entries for adjustments that no automated flow covers.</p>
HTML,
            ],
            [
                'slug' => 'fiscal-periods-and-closing',
                'title' => 'Fiscal periods and closing',
                'category' => 'reference',
                'module' => 'accounting',
                'summary' => 'How accounting periods work and what closing a period does.',
                'sort_order' => 500,
                'is_published' => true,
                'tags' => ['fiscal period', 'closing', 'accounting', 'period'],
                'body' => <<<'HTML'
<h3>Fiscal periods</h3>
<p>Accounting is organised into fiscal periods, usually months. Each period has an open or closed state.</p>
<h4>Closing a period</h4>
<p>Closing a period locks it against new entries. Closing periods on schedule keeps reports stable and prevents accidental postings to past months.</p>
<blockquote>Only finance should close periods, and only after confirming the period's reports are correct.</blockquote>
HTML,
            ],
            [
                'slug' => 'chart-of-accounts-explained',
                'title' => 'The chart of accounts',
                'category' => 'reference',
                'module' => 'accounting',
                'summary' => 'How accounts are structured and used by journal entries.',
                'sort_order' => 510,
                'is_published' => true,
                'tags' => ['chart of accounts', 'accounts', 'coa', 'ledger'],
                'body' => <<<'HTML'
<h3>Structure</h3>
<p>The chart of accounts is the full list of accounts, grouped into assets, liabilities, equity, revenue, and expenses. Each account has a code and a name.</p>
<h4>How entries use it</h4>
<p>Journal lines reference accounts by code. Keeping the chart tidy makes reports and aging accurate.</p>
HTML,
            ],
            [
                'slug' => 'accounting-reports-explained',
                'title' => 'Accounting reports',
                'category' => 'reference',
                'module' => 'accounting',
                'summary' => 'Trial balance, profit and loss, balance sheet, and aging reports.',
                'sort_order' => 520,
                'is_published' => true,
                'tags' => ['reports', 'trial balance', 'profit and loss', 'balance sheet', 'aging'],
                'body' => <<<'HTML'
<h3>Available reports</h3>
<ul>
<li><strong>Trial balance</strong> — all account balances for a period.</li>
<li><strong>General ledger</strong> — every entry for a selected account.</li>
<li><strong>Profit and loss</strong> — revenue and expenses for the period.</li>
<li><strong>Balance sheet</strong> — assets, liabilities, and equity.</li>
<li><strong>AR / AP aging</strong> — outstanding receivables and payables by age.</li>
</ul>
<p>Reports filter by fiscal period and account. Export them for external reporting.</p>
HTML,
            ],

            // ─────────────────────────── Projects ───────────────────────────
            [
                'slug' => 'creating-a-project',
                'title' => 'Creating a project',
                'category' => 'how-to',
                'module' => 'projects',
                'summary' => 'How to set up a project with client, phases, and services.',
                'sort_order' => 530,
                'is_published' => true,
                'tags' => ['project', 'create', 'phases', 'client'],
                'body' => <<<'HTML'
<h3>Create a project</h3>
<ol>
<li>Open <strong>Projects</strong> and tap <strong>New Project</strong>.</li>
<li>Select the client — the project code is generated from the client code.</li>
<li>Add the project name, dates, and service lines.</li>
<li>Choose a project type and group.</li>
<li>Create the project. Standard phases are added automatically.</li>
</ol>
<h4>Project code</h4>
<p>The system generates a code like <strong>AV-CLT-2608-0001</strong>. Never type or edit project codes manually.</p>
HTML,
            ],
            [
                'slug' => 'project-numbering-explained',
                'title' => 'Project numbering explained',
                'category' => 'reference',
                'module' => 'projects',
                'summary' => 'How project codes are built from the client and the date.',
                'sort_order' => 540,
                'is_published' => true,
                'tags' => ['project code', 'numbering', 'code', 'reference'],
                'body' => <<<'HTML'
<h3>Format</h3>
<p>With a linked client, the code is <strong>AV-{client code}-{YY}{MM}-{sequence}</strong>, for example <strong>AV-TNB-0001-2608-0001</strong>. Without a client, it falls back to <strong>AV-{YY}-{MM}-{sequence}</strong>.</p>
<h4>Sequence</h4>
<p>The sequence is global and increments across all projects — it does not reset per client or per month.</p>
HTML,
            ],
            [
                'slug' => 'project-phases-and-gates',
                'title' => 'Project phases and phase gates',
                'category' => 'reference',
                'module' => 'projects',
                'summary' => 'How standard phases and mandatory checklist sign-offs control project completion.',
                'sort_order' => 550,
                'is_published' => true,
                'tags' => ['phases', 'phase gate', 'checklist', 'task', 'completion'],
                'body' => <<<'HTML'
<h3>Standard phases</h3>
<p>Projects get a standard phase set automatically: PO Confirmation, Site Visit, Implementation, Coring Test, Lab Report, Road Marking, Joint Measurement Sheet, LKS, Service Entry, Invoice Submission, and Payment Settlement.</p>
<h4>Phase gates</h4>
<p>Tasks inside a phase may require mandatory checklist sign-offs and valid pause reasons (rain, machine breakdown, weather) before moving on. The system enforces these gates so completed work is properly documented.</p>
<h4>Completion</h4>
<p>When all phases complete, the project is marked completed. Invoicing is a separate, manual step — it is never automatic.</p>
HTML,
            ],
            [
                'slug' => 'assigning-staff-to-projects',
                'title' => 'Assigning staff to projects',
                'category' => 'how-to',
                'module' => 'projects',
                'summary' => 'How to add staff as project PICs and phase workers.',
                'sort_order' => 560,
                'is_published' => true,
                'tags' => ['staff', 'assign', 'project pic', 'phase', 'team'],
                'body' => <<<'HTML'
<h3>Project PICs</h3>
<p>Open the project and use the staff picker to add project persons-in-charge. These staff see the project in their own views.</p>
<h4>Phase workers</h4>
<p>Tasks and phases can have staff assigned for execution. Assigned staff see their tasks and can start, pause, and complete them with the required sign-offs.</p>
HTML,
            ],
            [
                'slug' => 'project-documents',
                'title' => 'Project documents',
                'category' => 'how-to',
                'module' => 'projects',
                'summary' => 'Uploading and organising documents on a project.',
                'sort_order' => 570,
                'is_published' => true,
                'tags' => ['document', 'upload', 'project', 'file'],
                'body' => <<<'HTML'
<h3>Upload documents</h3>
<ol>
<li>Open the project's documents section.</li>
<li>Tap <strong>Upload</strong> and choose the file.</li>
<li>Add a title and category.</li>
<li>Save. The file is stored with the project.</li>
</ol>
<p>Documents can be downloaded by anyone with project access. Files are stored in secure storage and served through signed links.</p>
HTML,
            ],
            [
                'slug' => 'project-claims',
                'title' => 'Project claims',
                'category' => 'how-to',
                'module' => 'projects',
                'summary' => 'How project claims are submitted and approved.',
                'sort_order' => 580,
                'is_published' => true,
                'tags' => ['project claim', 'claim', 'approval', 'cost'],
                'body' => <<<'HTML'
<h3>Submit a project claim</h3>
<ol>
<li>Open the project's claims section.</li>
<li>Create a claim with a title and amount.</li>
<li>Attach supporting documents.</li>
<li>Submit for approval.</li>
</ol>
<h4>Approval and payment</h4>
<p>Finance approves project claims and pays them through the payment hub, with journal entries created automatically.</p>
HTML,
            ],
            [
                'slug' => 'project-groups-and-types',
                'title' => 'Project groups and types',
                'category' => 'reference',
                'module' => 'projects',
                'summary' => 'How projects are classified and organised.',
                'sort_order' => 590,
                'is_published' => true,
                'tags' => ['project group', 'project type', 'classification'],
                'body' => <<<'HTML'
<h3>Project types</h3>
<p>Project types classify the work, for example milling, paving, road marking, or crack sealing. Types can drive service templates.</p>
<h4>Project groups</h4>
<p>Groups organise projects for reporting — for example by station or region. A project can belong to multiple groups.</p>
HTML,
            ],

            // ─────────────────────────── Clients & team ───────────────────────────
            [
                'slug' => 'managing-clients',
                'title' => 'Managing client records',
                'category' => 'how-to',
                'module' => 'clients',
                'summary' => 'Client master data: company details, tax IDs, and persons in charge.',
                'sort_order' => 600,
                'is_published' => true,
                'tags' => ['client', 'company', 'tin', 'pic', 'master data'],
                'body' => <<<'HTML'
<h3>Client master data</h3>
<p>Open <strong>Clients</strong> to manage company records: registration numbers, tax IDs (TIN), addresses, and billing details.</p>
<h4>Persons in charge</h4>
<p>Each client can have multiple PICs — the contacts who approve quotations and invoices. Client codes feed into project numbering.</p>
HTML,
            ],
            [
                'slug' => 'staff-directory-and-roles',
                'title' => 'The staff directory and roles',
                'category' => 'how-to',
                'module' => 'team',
                'summary' => 'How HR manages staff profiles, roles, and status.',
                'sort_order' => 610,
                'is_published' => true,
                'tags' => ['staff', 'directory', 'team', 'role', 'profile', 'hr'],
                'body' => <<<'HTML'
<h3>Staff directory</h3>
<p>Open <strong>Team</strong> for the full staff list. Each profile holds personal details, bank information, statutory numbers (EPF, SOCSO), and work hours.</p>
<h4>Roles</h4>
<p>A staff member's user account carries the role that controls system access. HR updates roles when responsibilities change.</p>
<h4>Statutory flags</h4>
<p>EPF and SOCSO contributing flags live on the profile and control whether contributions are calculated in payroll.</p>
HTML,
            ],
            [
                'slug' => 'staff-status-and-resignation',
                'title' => 'Staff status and resignation',
                'category' => 'how-to',
                'module' => 'team',
                'summary' => 'How staff are activated, deactivated, or marked resigned.',
                'sort_order' => 620,
                'is_published' => true,
                'tags' => ['staff', 'resign', 'status', 'inactive', 'hr'],
                'body' => <<<'HTML'
<h3>Status changes</h3>
<p>HR updates a staff member's status from the profile. Inactive staff stop appearing in payroll runs.</p>
<h4>Resignation</h4>
<p>Marking a staff member as resigned records the resignation date and removes them from active payroll and attendance counts.</p>
<blockquote>Deactivating a user does not delete their history — past records stay intact.</blockquote>
HTML,
            ],

            // ─────────────────────────── Subcontractors, vendors, assets ───────────────────────────
            [
                'slug' => 'managing-subcontractors',
                'title' => 'Managing subcontractors',
                'category' => 'how-to',
                'module' => 'subcontractors',
                'summary' => 'Subcontractor master data, project links, and PICs.',
                'sort_order' => 630,
                'is_published' => true,
                'tags' => ['subcontractor', 'subcon', 'master data', 'project'],
                'body' => <<<'HTML'
<h3>Subcontractor records</h3>
<p>Open <strong>Subcontractors</strong> to manage subcontractor companies, their PICs, and the projects they work on.</p>
<h4>Linking to projects</h4>
<p>Each project can list its subcontractors. Subcontractor claims and self-billed invoices reference these records.</p>
HTML,
            ],
            [
                'slug' => 'subcontractor-claims',
                'title' => 'Subcontractor claims',
                'category' => 'how-to',
                'module' => 'subcontractors',
                'summary' => 'How subcontractor claims are submitted, approved, and paid.',
                'sort_order' => 640,
                'is_published' => true,
                'tags' => ['subcontractor', 'claim', 'approval', 'payment'],
                'body' => <<<'HTML'
<h3>Claim flow</h3>
<ol>
<li>Create a claim against the subcontractor with the work done and amount.</li>
<li>Attach the supporting invoice or documents.</li>
<li>Finance approves the claim.</li>
<li>Payment is recorded and journal entries are created.</li>
</ol>
<h4>Work percentage</h4>
<p>Claims can record the percentage of work completed, so outstanding work stays visible on the subcontractor's record.</p>
HTML,
            ],
            [
                'slug' => 'managing-vendors',
                'title' => 'Managing vendors',
                'category' => 'how-to',
                'module' => 'vendors',
                'summary' => 'Vendor master data used by purchase orders and bills.',
                'sort_order' => 650,
                'is_published' => true,
                'tags' => ['vendor', 'supplier', 'master data', 'purchase order'],
                'body' => <<<'HTML'
<h3>Vendor records</h3>
<p>Open <strong>Vendors</strong> to manage suppliers. Each vendor has a code, contact details, and payment information.</p>
<h4>Usage</h4>
<p>Purchase orders and bills reference vendors. Keeping vendor data accurate makes procurement and accounts payable smoother.</p>
HTML,
            ],
            [
                'slug' => 'asset-management',
                'title' => 'Asset management',
                'category' => 'how-to',
                'module' => 'assets',
                'summary' => 'Track equipment: licenses, movements, and services.',
                'sort_order' => 660,
                'is_published' => true,
                'tags' => ['asset', 'equipment', 'license', 'maintenance'],
                'body' => <<<'HTML'
<h3>Asset records</h3>
<p>Open <strong>Assets</strong> to manage equipment. Each asset has a code, category, and status.</p>
<h4>Tabs</h4>
<ul>
<li><strong>Overview</strong> — asset details and status.</li>
<li><strong>Licenses</strong> — road permits and operational licenses with expiry.</li>
<li><strong>Movements</strong> — where the asset has been and its current location.</li>
<li><strong>Services</strong> — maintenance and service history.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'asset-qr-codes',
                'title' => 'Asset QR codes',
                'category' => 'how-to',
                'module' => 'assets',
                'summary' => 'How to use QR codes to open an asset record on site.',
                'sort_order' => 670,
                'is_published' => true,
                'tags' => ['asset', 'qr code', 'scan', 'site'],
                'body' => <<<'HTML'
<h3>Scan to view</h3>
<p>Each asset has a QR code. Scanning it opens the asset detail page directly — useful for checking status or logging a movement while on site.</p>
<h4>Printing codes</h4>
<p>Print the asset's QR code and attach it to the equipment so field staff can scan it anytime.</p>
HTML,
            ],

            // ─────────────────────────── Config & settings ───────────────────────────
            [
                'slug' => 'company-settings-explained',
                'title' => 'Company settings explained',
                'category' => 'reference',
                'module' => 'company-settings',
                'summary' => 'Company profile, statutory defaults, work hours, and enforcement toggles.',
                'sort_order' => 680,
                'is_published' => true,
                'tags' => ['company settings', 'settings', 'work hours', 'geofence', 'skbbk'],
                'body' => <<<'HTML'
<h3>What lives here</h3>
<ul>
<li><strong>Company info</strong> — name, logo, and registration details.</li>
<li><strong>Statutory defaults</strong> — default work hours and the active SKBBK phase.</li>
<li><strong>Geofence enforcement</strong> — switch geofenced punching on or off.</li>
<li><strong>Auto clock-out</strong> — enable automatic clock-out and set the grace period.</li>
</ul>
<h4>Who changes it</h4>
<p>Company settings are managed by admins. Staff-specific overrides, like individual work hours, live on each staff profile.</p>
HTML,
            ],
            [
                'slug' => 'notification-preferences',
                'title' => 'Notification preferences',
                'category' => 'how-to',
                'module' => 'notifications',
                'summary' => 'Control which events send you in-app and push notifications.',
                'sort_order' => 690,
                'is_published' => true,
                'tags' => ['notification', 'preferences', 'push', 'alerts'],
                'body' => <<<'HTML'
<h3>Choose your notifications</h3>
<ol>
<li>Open <strong>Notifications</strong> in settings.</li>
<li>Review the event list — leave, claims, approvals, payroll, attendance.</li>
<li>Toggle each event on or off for your account.</li>
</ol>
<h4>How notifications arrive</h4>
<p>Notifications appear in the bell menu. Push notifications can be enabled in the browser for events you care about.</p>
HTML,
            ],
            [
                'slug' => 'notification-events-explained',
                'title' => 'Notification events explained',
                'category' => 'reference',
                'module' => 'notifications',
                'summary' => 'The events that generate notifications and who receives them.',
                'sort_order' => 700,
                'is_published' => true,
                'tags' => ['notification', 'events', 'leave', 'claim', 'payroll'],
                'body' => <<<'HTML'
<h3>What triggers a notification</h3>
<ul>
<li><strong>Leave</strong> — submitted, approved, rejected, withdrawn, cancelled.</li>
<li><strong>Claims</strong> — submitted, approved, rejected.</li>
<li><strong>Payments</strong> — recorded or approved.</li>
<li><strong>Attendance</strong> — auto clock-out events.</li>
<li><strong>Project</strong> — completion and phase events.</li>
</ul>
<h4>Deduplication</h4>
<p>Notifications are deduplicated by event type, recipient, and record, so the same event never floods the queue.</p>
HTML,
            ],
            [
                'slug' => 'managing-public-holidays',
                'title' => 'Managing public holidays',
                'category' => 'how-to',
                'module' => 'public-holidays',
                'summary' => 'Add recurring and one-off Malaysian holidays to the calendar.',
                'sort_order' => 710,
                'is_published' => true,
                'tags' => ['public holiday', 'holiday', 'calendar', 'recurring'],
                'body' => <<<'HTML'
<h3>Add a holiday</h3>
<ol>
<li>Open <strong>Public Holidays</strong>.</li>
<li>Add the holiday name and date.</li>
<li>Mark it recurring if it happens every year on the same date.</li>
</ol>
<h4>Effect</h4>
<p>Holidays are excluded from leave day counts and block all-holiday leave requests.</p>
HTML,
            ],
            [
                'slug' => 'geofence-sites-management',
                'title' => 'Managing geofence sites',
                'category' => 'how-to',
                'module' => 'geofences',
                'summary' => 'Create and edit geofenced sites for punch enforcement.',
                'sort_order' => 720,
                'is_published' => true,
                'tags' => ['geofence', 'site', 'location', 'radius', 'punch'],
                'body' => <<<'HTML'
<h3>Create a site</h3>
<ol>
<li>Open <strong>Geofence Sites</strong>.</li>
<li>Place the marker on the map at the site location.</li>
<li>Set the radius in metres.</li>
<li>Save. The site becomes active for punching.</li>
</ol>
<h4>Project-linked sites</h4>
<p>Projects with coordinates generate geofences automatically. For those, only the radius and description can be edited.</p>
HTML,
            ],
            [
                'slug' => 'service-catalog-explained',
                'title' => 'The service catalog',
                'category' => 'how-to',
                'module' => 'service-items',
                'summary' => 'Standard services and rates used in quotations and invoices.',
                'sort_order' => 730,
                'is_published' => true,
                'tags' => ['service catalog', 'service item', 'rates', 'quotation'],
                'body' => <<<'HTML'
<h3>What it is</h3>
<p>The service catalog stores the standard services and rates your company offers, for example milling per square metre.</p>
<h4>Usage</h4>
<p>Quotations and invoices pick services from the catalog, keeping pricing consistent across projects.</p>
HTML,
            ],
            [
                'slug' => 'phase-templates-explained',
                'title' => 'Phase templates explained',
                'category' => 'reference',
                'module' => 'phase-templates',
                'summary' => 'How standard project phases are defined and reused.',
                'sort_order' => 740,
                'is_published' => true,
                'tags' => ['phase template', 'phases', 'project', 'template'],
                'body' => <<<'HTML'
<h3>What a template defines</h3>
<p>Phase templates define the standard phase sequence a new project receives. Templates can be applied per project type.</p>
<h4>Editing templates</h4>
<p>Changing a template affects projects created after the change. Existing projects keep their current phases.</p>
HTML,
            ],
            [
                'slug' => 'project-types-explained',
                'title' => 'Project types explained',
                'category' => 'reference',
                'module' => 'project-types',
                'summary' => 'How project types classify work and drive templates.',
                'sort_order' => 750,
                'is_published' => true,
                'tags' => ['project type', 'classification', 'milling', 'paving'],
                'body' => <<<'HTML'
<h3>Classification</h3>
<p>Project types classify the work performed — milling, paving, road marking, crack sealing, and others. Each type can link to a phase template and service defaults.</p>
<h4>Why it matters</h4>
<p>Choosing the right type on a new project sets up the correct phases and services automatically.</p>
HTML,
            ],

            // ─────────────────────────── Admin & system ───────────────────────────
            [
                'slug' => 'user-management',
                'title' => 'User management (super admin)',
                'category' => 'how-to',
                'module' => 'users',
                'summary' => 'Create user accounts, assign roles, and reset passwords.',
                'sort_order' => 760,
                'is_published' => true,
                'tags' => ['user', 'account', 'role', 'reset password', 'super admin'],
                'body' => <<<'HTML'
<h3>Create a user</h3>
<ol>
<li>Open <strong>User Management</strong>.</li>
<li>Create a user with a name, email, and password.</li>
<li>Assign one or more roles.</li>
<li>Save. The user can now log in.</li>
</ol>
<h4>Reset passwords</h4>
<p>Reset a user's password from their record when they are locked out or have forgotten it.</p>
<blockquote>User management is super-admin-only. Regular admins cannot create or change user accounts.</blockquote>
HTML,
            ],
            [
                'slug' => 'system-health-and-diagnostics',
                'title' => 'System health and diagnostics',
                'category' => 'reference',
                'module' => 'system',
                'summary' => 'How to check the system is healthy and test mail and push.',
                'sort_order' => 770,
                'is_published' => true,
                'tags' => ['system health', 'diagnostics', 'mail test', 'push test'],
                'body' => <<<'HTML'
<h3>Health checks</h3>
<p>The System Health page shows the status of core services — database, storage, and queues.</p>
<h4>Diagnostics</h4>
<p>Run diagnostics to see detailed service state. Test mail and test push buttons send sample messages to verify delivery.</p>
HTML,
            ],
            [
                'slug' => 'audit-log-explained',
                'title' => 'The audit log',
                'category' => 'reference',
                'module' => 'audit-logs',
                'summary' => 'What gets recorded, who can view it, and how to search it.',
                'sort_order' => 780,
                'is_published' => true,
                'tags' => ['audit log', 'activity log', 'history', 'who did what'],
                'body' => <<<'HTML'
<h3>What is recorded</h3>
<p>The audit log records creates, updates, and deletes across the system, plus login events. Each entry shows who did it, when, and what changed.</p>
<h4>Login events</h4>
<p>Successful logins, failed attempts, and account lockouts are recorded with the email, IP address, and browser.</p>
<h4>Search and export</h4>
<p>Filter by event type, date range, or search text. Export the current view as CSV for review.</p>
<blockquote>The audit log is viewable by admin and super admin.</blockquote>
HTML,
            ],

            // ─────────────────────────── Board & timecards ───────────────────────────
            [
                'slug' => 'board-overview-explained',
                'title' => 'The board overview',
                'category' => 'reference',
                'module' => 'board',
                'summary' => 'Project health, schedules, and risk at a glance.',
                'sort_order' => 790,
                'is_published' => true,
                'tags' => ['board', 'gantt', 'risk', 'health', 'project'],
                'body' => <<<'HTML'
<h3>What the board shows</h3>
<p>The board overview brings project health, schedules, and risk into one view — useful for managers reviewing many projects.</p>
<h4>Gantt and risk</h4>
<p>Open a project from the board to see its Gantt schedule and risk items.</p>
HTML,
            ],
            [
                'slug' => 'timecards-explained',
                'title' => 'Timecards explained',
                'category' => 'how-to',
                'module' => 'timecards',
                'summary' => 'How hours worked are captured and used for billing and payroll.',
                'sort_order' => 800,
                'is_published' => true,
                'tags' => ['timecard', 'hours', 'time', 'project', 'billing'],
                'body' => <<<'HTML'
<h3>What a timecard is</h3>
<p>A timecard records hours worked against a project. Timecards feed project cost and, where configured, payroll.</p>
<h4>Approval</h4>
<p>Timecards may require manager approval before the hours are locked for billing.</p>
HTML,
            ],
        ];

        foreach ($articles as $a) {
            KnowledgeArticle::firstOrCreate(
                ['slug' => $a['slug']],
                $a
            );
        }

        echo '  Seeded '.count($articles)." knowledge articles.\n";
    }
}
