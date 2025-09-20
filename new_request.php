<?php
// FILE: views/new_request.php
// Form for submitting a new financial request.

$error = $error ?? '';

// Compute Annual leave available days for the current user/year
$annualAvailableDays = 0;
try {
    if (isset($pdo, $user) && isset($user['user_id'])) {
        if (function_exists('ensure_leave_caps')) {
            ensure_leave_caps($pdo, (int)$user['user_id'], (int)date('Y'));
        }
        $stmt = $pdo->prepare("SELECT balance_days FROM leave_balances WHERE user_id = ? AND leave_type = 'Annual leave' AND year = ?");
        $stmt->execute([(int)$user['user_id'], (int)date('Y')]);
        $val = $stmt->fetchColumn();
        if ($val !== false) {
            $annualAvailableDays = (int)$val;
        }
    }
} catch (Throwable $e) {
    // fail soft; keep default 0
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request | Chamber Request System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/ui.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes floatOrb { 0%{transform:translateY(0)} 50%{transform:translateY(-12px)} 100%{transform:translateY(0)} }
        .orb-bg { position:absolute; inset:-40px; filter:blur(70px); opacity:.25; pointer-events:none; }
        .btn-gradient { background:linear-gradient(90deg,#0b5ed7,#d4af37); color:#fff; }
        .glass { background: linear-gradient(180deg, rgba(11,20,40,.65), rgba(5,10,30,.55)); backdrop-filter: blur(8px); border:1px solid rgba(255,255,255,.08); }
        .btn-gradient:hover { filter:brightness(1.05); }
        .chip { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:rgba(13, 25, 45, .3); border:1px solid rgba(11,94,215,.35); color:#e2e8f0; font-weight:600; }
    </style>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Top Nav -->
        <header class="glass shadow p-4 flex items-center justify-between sticky top-0 z-40">
            <h1 class="text-xl font-semibold">New Request</h1>
            <div class="flex items-center space-x-4">
                <p>
                    Welcome, <span class="font-medium text-gray-800"><?= e($user['fullname']) ?></span>
                </p>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-6">
            <div class="relative">
                <div aria-hidden="true" class="orb-bg" style="background:radial-gradient(closest-side,#0b5ed7,transparent 70%),radial-gradient(closest-side,#d4af37,transparent 70%) 70% 10%/40% 40% no-repeat,radial-gradient(closest-side,#0b5ed7,transparent 70%) 30% 90%/35% 35% no-repeat; animation:floatOrb 16s ease-in-out infinite;"></div>
            </div>
            <div class="glass p-6 rounded-2xl shadow-lg max-w-4xl mx-auto" style="color:#e2e8f0;">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-2xl font-bold">Submit a New Request <span id="type_chip" class="chip" style="display:none;"><span id="type_emoji">📝</span><span id="type_label"></span></span></h2>
                    <div class="text-sm text-slate-400 font-semibold tracking-wider">🏢 TCCIA ✨</div>
                </div>
                <p class="text-sm text-slate-300 mb-6">To be efficient, sustainable and most preferred role model business member based organization in Tanzania and Africa.</p>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form action="index.php?action=new_request" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label for="request_type" class="block text-slate-200 font-medium mb-2">Request Type <span class="text-sm text-slate-400">(pick one)</span></label>
                        <select id="request_type" name="request_type" class="w-full px-4 py-3 border-2 rounded-lg bg-white border-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-lg" required style="font-size: 16px; line-height: 1.5; background-color: #ffffff !important; border-color: #ffffff !important;">
                            <option value="" style="color: #6b7280; font-style: italic;">-- Select Request Type --</option>
                            <optgroup label="💼 Financial Requests" style="font-weight: bold; color: #1f2937; background-color: #f3f4f6; padding: 8px;">
                                <option value="Impest request" style="color: #000000; font-weight: 600; padding: 8px 12px;">💰 Impest request</option>
                                <option value="TCCIA retirement request" style="color: #000000; font-weight: 600; padding: 8px 12px;">📑 TCCIA retirement request</option>
                                <option value="Salary advance" style="color: #000000; font-weight: 600; padding: 8px 12px;">💵 Salary advance</option>
                                <option value="Reimbursement request" style="color: #000000; font-weight: 600; padding: 8px 12px;">↩️ Reimbursement request</option>
                                <option value="Travel form" style="color: #000000; font-weight: 600; padding: 8px 12px;">✈️ Travel form</option>
                            </optgroup>
                            <optgroup label="👥 HRM Requests" style="font-weight: bold; color: #1f2937; background-color: #f3f4f6; padding: 8px;">
                                <option value="Annual leave" style="color: #1e3a8a; font-weight: 600; padding: 8px 12px;">🏖️ Annual leave</option>
                                <option value="Compassionate leave" style="color: #1e3a8a; font-weight: 600; padding: 8px 12px;">🤝 Compassionate leave</option>
                                <option value="Paternity leave" style="color: #1e3a8a; font-weight: 600; padding: 8px 12px;">👶 Paternity leave</option>
                                <option value="Maternity leave" style="color: #1e3a8a; font-weight: 600; padding: 8px 12px;">🤱 Maternity leave</option>
                                <option value="Sick leave" style="color: #1e3a8a; font-weight: 600; padding: 8px 12px;">🤒 Sick leave</option>
                                <option value="Staff clearance form" style="color: #1e3a8a; font-weight: 600; padding: 8px 12px;">📋 Staff clearance form</option>
                            </optgroup>
                        </select>
                        <p class="text-xs text-slate-400 mt-1" id="type_hint">Amount will be required for financial requests only.</p>
                    </div>
                    <!-- Dynamic fields container -->
                    <div id="dynamic_fields" class="space-y-4 p-4 rounded-lg border border-slate-700 bg-slate-900/40"></div>
                    </div>
                    <div id="amount_group" class="max-w-lg mx-auto">
                        <label for="amount" class="block text-slate-200 font-medium mb-2">Amount</label>
                        <input type="number" id="amount" name="amount" step="0.01" class="w-full px-4 py-2 border rounded-lg bg-slate-800 border-slate-700 text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="max-w-lg mx-auto">
                        <label for="attachment" class="block text-slate-200 font-medium mb-2">Attachment (PDF, JPG, PNG)</label>
                        <input type="file" id="attachment" name="attachment" class="w-full px-4 py-2 border rounded-lg bg-slate-800 border-slate-700 text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <!-- Hidden fields for title and description -->
                    <input type="hidden" id="title" name="title" value="">
                    <input type="hidden" id="description" name="description" value="">
                    <!-- Hidden available annual leave days for UI logic -->
                    <input type="hidden" id="annual_available" value="<?= e($annualAvailableDays) ?>">
                    <input type="hidden" id="details_json" name="details_json" value="{}">
                    <div class="flex items-center gap-3 justify-end">
                        <button type="submit" class="btn-gradient font-bold py-3 px-6 rounded-lg transition">Submit Request</button>
                        <button type="button" onclick="window.print()" class="bg-slate-800 border border-slate-700 text-slate-200 px-6 py-3 rounded-lg shadow-sm hover:bg-slate-700">🖨️ Print</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
<script>
    (function(){
        const select = document.getElementById('request_type');
        const amountGroup = document.getElementById('amount_group');
        const amountInput = document.getElementById('amount');
        const title = document.getElementById('title');
        const hint = document.getElementById('type_hint');
        const dynamic = document.getElementById('dynamic_fields');
        const detailsInput = document.getElementById('details_json');
        const chip = document.getElementById('type_chip');
        const chipEmoji = document.getElementById('type_emoji');
        const chipLabel = document.getElementById('type_label');
        const annualAvailableInput = document.getElementById('annual_available');
        const annualAvailable = annualAvailableInput ? parseInt(annualAvailableInput.value || '0', 10) : 0;
        const financial = new Set([
            'Impest request','TCCIA retirement request','Salary advance','Reimbursement request','Travel form'
        ]);
        const leaveTypes = new Set([
            'Annual leave','Compassionate leave','Paternity leave','Maternity leave','Sick leave'
        ]);
        const emojiMap = new Map([
            ['Impest request','💰'],
            ['Reimbursement request','↩️'],
            ['TCCIA retirement request','📑'],
            ['Salary advance','💵'],
            ['Travel form','✈️'],
            ['Annual leave','🏖️'],
            ['Compassionate leave','🤝'],
            ['Paternity leave','👶'],
            ['Maternity leave','🤱'],
            ['Sick leave','🤒'],
            ['Staff clearance form','📋']
        ]);
        function group(title, inner){
            return `<div class=\"bg-gray-50 border rounded p-3\"><div class=\"font-medium mb-2\">${title}</div>${inner}</div>`;
        }
        function h(label, id, type='text', attrs=''){
            return `<div><label class=\"block text-white font-medium mb-2\" for=\"${id}\">${label}</label><input ${attrs} id=\"${id}\" name=\"${id}\" type=\"${type}\" class=\"w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500\" style=\"color: #000000 !important; background-color: #ffffff !important;\"></div>`;
        }
        function ta(label, id, attrs=''){
            return `<div><label class=\"block text-white font-medium mb-2\" for=\"${id}\">${label}</label><textarea ${attrs} id=\"${id}\" name=\"${id}\" rows=\"3\" class=\"w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500\" style=\"color: #000000 !important; background-color: #ffffff !important;\"></textarea></div>`;
        }
        function renderFields(v){
            dynamic.innerHTML = '';
            // HRM leave forms
            if (leaveTypes.has(v)) {
                // Common fields
                dynamic.insertAdjacentHTML('beforeend', h('Name', 'leave_name', 'text', 'required'));
                dynamic.insertAdjacentHTML('beforeend', h('PF No', 'pf_no', 'text', 'required'));
                dynamic.insertAdjacentHTML('beforeend', h('Department', 'leave_department', 'text', 'required'));
                if (v !== 'Sick leave') {
                    if (v === 'Annual leave') {
                        // Show available and remaining info for Annual leave
                        dynamic.insertAdjacentHTML('beforeend', `<div id="annual_info" class="rounded-lg border border-slate-700 bg-slate-900/40 p-3 text-slate-200">
                            <div class="flex items-center justify-between">
                                <span>Available: <span id="annual_available_days" class="font-semibold">${annualAvailable}</span> days</span>
                                <span>Remaining: <span id="annual_remaining_days" class="font-semibold">${annualAvailable}</span> days</span>
                            </div>
                        </div>`);
                    }
                    dynamic.insertAdjacentHTML('beforeend', h('Days Applied', 'days_applied', 'number', 'min=1 required'));
                    dynamic.insertAdjacentHTML('beforeend', h('Start Date', 'start_date', 'date', 'required'));
                    dynamic.insertAdjacentHTML('beforeend', h('End Date', 'end_date', 'date', 'required'));
                } else {
                    // Sick leave specific fields
                    dynamic.insertAdjacentHTML('beforeend', h('Date of Illness', 'illness_date', 'date', 'required'));
                    dynamic.insertAdjacentHTML('beforeend', ta('Hospital/Doctor Remarks (optional)', 'sick_note'));
                }
                // Hook up dynamic remaining calculation for Annual leave
                if (v === 'Annual leave') {
                    const daysInput = dynamic.querySelector('#days_applied');
                    const remainingEl = dynamic.querySelector('#annual_remaining_days');
                    const availEl = dynamic.querySelector('#annual_available_days');
                    if (remainingEl && availEl) {
                        const updateRemaining = () => {
                            const applied = parseInt(daysInput && daysInput.value ? daysInput.value : '0', 10) || 0;
                            const remaining = Math.max(annualAvailable - applied, 0);
                            remainingEl.textContent = remaining.toString();
                            // Color hint when over limit
                            const parent = remainingEl.closest('#annual_info');
                            if (parent) {
                                if (applied > annualAvailable) {
                                    parent.classList.add('ring-2','ring-red-500');
                                } else {
                                    parent.classList.remove('ring-2','ring-red-500');
                                }
                            }
                        };
                        if (daysInput) {
                            daysInput.addEventListener('input', updateRemaining);
                            updateRemaining();
                        }
                    }
                }
            } else if (v === 'Staff clearance form') {
                dynamic.insertAdjacentHTML('beforeend', `<div><label class="block text-white font-medium mb-2" for="clear_name">Name</label><input required id="clear_name" name="clear_name" type="text" class="w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" style="color: #000000 !important; background-color: #ffffff !important;"></div>`);
                dynamic.insertAdjacentHTML('beforeend', `<div><label class="block text-white font-medium mb-2" for="last_day">Last Day of Work</label><input required id="last_day" name="last_day" type="date" class="w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" style="color: #000000 !important; background-color: #ffffff !important;"></div>`);
                dynamic.insertAdjacentHTML('beforeend', `<div><label class="block text-white font-medium mb-2" for="clear_department">Department</label><input required id="clear_department" name="clear_department" type="text" class="w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" style="color: #000000 !important; background-color: #ffffff !important;"></div>`);
                // Clearance areas with black text for field labels
                function hClearance(label, id, type='text', attrs=''){
                    return `<div><label class=\"block text-black font-medium mb-2\" for=\"${id}\">${label}</label><input ${attrs} id=\"${id}\" name=\"${id}\" type=\"${type}\" class=\"w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500\" style=\"color: #000000 !important; background-color: #ffffff !important;\"></div>`;
                }
                function taClearance(label, id, attrs=''){
                    return `<div><label class=\"block text-black font-medium mb-2\" for=\"${id}\">${label}</label><textarea ${attrs} id=\"${id}\" name=\"${id}\" rows=\"3\" class=\"w-full px-4 py-2 border rounded-lg bg-white border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500\" style=\"color: #000000 !important; background-color: #ffffff !important;\"></textarea></div>`;
                }
                
                dynamic.insertAdjacentHTML('beforeend', `<div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-4"><label class=\"block text-gray-900 font-bold text-lg mb-3\">📁 Own Department Clearance</label>
                    ${taClearance('Manuals / Work / Files / Keys / Car', 'dept_items')}</div>`);
                dynamic.insertAdjacentHTML('beforeend', `<div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 mb-4"><label class=\"block text-gray-900 font-bold text-lg mb-3\">💻 IT Clearance</label>
                    ${hClearance('Mobile Phone', 'it_mobile')}
                    ${hClearance('Laptop', 'it_laptop')}
                    ${hClearance('Other IT Equipment', 'it_other')}</div>`);
                dynamic.insertAdjacentHTML('beforeend', `<div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-4 mb-4"><label class=\"block text-gray-900 font-bold text-lg mb-3\">💰 Finance Clearance</label>
                    ${hClearance('Outstanding Floats', 'fin_floats')}
                    ${hClearance('Salary Advances', 'fin_advances')}
                    ${hClearance('Staff Loans', 'fin_loans')}
                    ${hClearance('Imprest Retirements', 'fin_imprest')}
                    ${hClearance('Personal Bills Settlement', 'fin_bills')}</div>`);
                dynamic.insertAdjacentHTML('beforeend', `<div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-4 mb-4"><label class=\"block text-gray-900 font-bold text-lg mb-3\">👥 HR Clearance</label>
                    ${hClearance('Exit Interview Completion', 'hr_exit_interview')}
                    ${hClearance('Company Mobile Phones', 'hr_mobile_phones')}
                    ${hClearance('Earphones/Headsets', 'hr_earphones')}
                    ${hClearance('Staff ID Cards', 'hr_id_cards')}
                    ${hClearance('Business Cards', 'hr_business_cards')}
                    ${hClearance('NHIF Cards', 'hr_nhif_cards')}</div>`);
            } else {
                // Financial forms
                const finImprestLike = new Set(['Impest request','Reimbursement request']);
                if (finImprestLike.has(v)) {
                    let inner = '';
                    inner += h('Name', 'fin_name', 'text', 'required');
                    inner += h('Position', 'fin_position', 'text', 'required');
                    inner += h('Activity Date', 'activity_date', 'date', 'required');
                    inner += h('Activity', 'activity', 'text', 'required');
                    inner += h('I request an imprest of TShs', 'imprest_amount', 'number', 'min=0 step=0.01 required');
                    inner += ta('Description', 'fin_description', 'required');
                    dynamic.insertAdjacentHTML('beforeend', group(v + ' Details', inner));
                } else if (v === 'TCCIA retirement request') {
                    let inner = '';
                    inner += h('Name', 'fin_name', 'text', 'required');
                    inner += h('Position', 'fin_position', 'text', 'required');
                    inner += h('Activity Date', 'activity_date', 'date', 'required');
                    inner += h('Activity', 'activity', 'text', 'required');
                    inner += h('Imprest taken (TShs)', 'imprest_taken', 'number', 'min=0 step=0.01 required');
                    inner += h('Amount used (TShs)', 'amount_used', 'number', 'min=0 step=0.01 required');
                    inner += h('Claim/Refund from/to the office (TShs)', 'claim_refund', 'number', 'min=0 step=0.01 required');
                    inner += h('Balance to return to the office (TShs)', 'balance_return', 'number', 'min=0 step=0.01 required');
                    inner += ta('Description', 'fin_description', '');
                    dynamic.insertAdjacentHTML('beforeend', group('Retirement Details', inner));
                } else if (v === 'Salary advance') {
                    let inner = '';
                    inner += h('Name', 'fin_name', 'text', 'required');
                    inner += h('Position', 'fin_position', 'text', 'required');
                    inner += h('Department', 'fin_department', 'text', 'required');
                    inner += h('Date', 'fin_date', 'date', 'required');
                    inner += h('Salary advance amount (TShs)', 'salary_advance_amount', 'number', 'min=0 step=0.01 required');
                    dynamic.insertAdjacentHTML('beforeend', group('Salary Advance Details', inner));
                } else if (v === 'Travel form') {
                    let inner = '';
                    inner += h('Name', 'fin_name', 'text', 'required');
                    inner += h('Position', 'fin_position', 'text', 'required');
                    inner += h('Department', 'fin_department', 'text', 'required');
                    inner += h('Date', 'fin_date', 'date', 'required');
                    inner += h('Activity', 'activity', 'text', 'required');
                    inner += ta('Description', 'fin_description', 'required');
                    dynamic.insertAdjacentHTML('beforeend', group('Travel Details', inner));
                }
            }
        }
        function collectDetails(){
            const fields = dynamic.querySelectorAll('input,textarea,select');
            const data = {};
            fields.forEach(el => {
                if (!el.name) return;
                data[el.name] = el.value;
            });
            detailsInput.value = JSON.stringify(data);
        }
        function updateUI(){
            const v = select.value;
            const isFinancial = financial.has(v);
            amountGroup.style.display = isFinancial ? '' : 'none';
            amountInput.required = isFinancial;
            hint.textContent = isFinancial ? 'Amount is required for financial requests.' : 'No amount needed for HRM requests.';
            // Auto-generate title and description based on request type
            if (v) {
                const titleField = document.getElementById('title');
                const descriptionField = document.getElementById('description');
                if (titleField && !titleField.value) {
                    titleField.value = v;
                }
                if (descriptionField && !descriptionField.value) {
                    descriptionField.value = `Request for ${v}`;
                }
            }
            // Make attachment optional for all forms including sick leave
            const att = document.getElementById('attachment');
            if (att) att.required = false;
            renderFields(v);
            collectDetails();
            // Update header chip with emoji and label
            if (v) {
                chip.style.display = 'inline-flex';
                chipEmoji.textContent = emojiMap.get(v) || '📝';
                chipLabel.textContent = v;
            } else {
                chip.style.display = 'none';
            }
        }
        select.addEventListener('change', updateUI);
        const form = document.forms[0];
        form.addEventListener('input', collectDetails);
        // Block submission if Annual leave exceeds available
        form.addEventListener('submit', function(e){
            const v = select.value;
            if (v === 'Annual leave') {
                const daysInput = document.getElementById('days_applied');
                const applied = parseInt(daysInput && daysInput.value ? daysInput.value : '0', 10) || 0;
                if (applied <= 0 || applied > annualAvailable) {
                    e.preventDefault();
                    alert('Days applied must be between 1 and ' + annualAvailable + '.');
                    const parent = document.getElementById('annual_info');
                    if (parent) parent.classList.add('ring-2','ring-red-500');
                }
            }
        });
        updateUI();
    })();
</script>
</body>
</html>
