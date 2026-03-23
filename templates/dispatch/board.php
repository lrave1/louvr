<?php
use App\Middleware\CsrfMiddleware;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// Status colours for event blocks
$statusColors = [
    'Booked'  => 'bg-blue-600 border-blue-500',
    'Quoted'  => 'bg-orange-600 border-orange-500',
    'Won'     => 'bg-green-600 border-green-500',
    'Lost'    => 'bg-gray-600 border-gray-500',
    'Cancelled' => 'bg-gray-600 border-gray-500',
];
$defaultColor = 'bg-blue-600 border-blue-500';
?>

<style>
    .dispatch-grid { display: grid; overflow-x: auto; }
    .time-slot { min-width: 50px; min-height: 44px; position: relative; }
    .time-slot.drop-hover { background: rgba(59, 130, 246, 0.15) !important; }
    .event-block { cursor: grab; transition: opacity 0.15s, transform 0.15s; }
    .event-block.dragging { opacity: 0.5; transform: scale(0.95); }
    .event-block:hover { filter: brightness(1.15); }
    .sidebar-lead { cursor: grab; transition: all 0.15s; }
    .sidebar-lead.dragging { opacity: 0.4; }
    .sidebar-lead:hover { background: rgba(255,255,255,0.05); }
    .current-time-line { position: absolute; top: 0; bottom: 0; width: 2px; background: #ef4444; z-index: 20; pointer-events: none; }
    .current-time-dot { position: absolute; top: -4px; width: 10px; height: 10px; border-radius: 50%; background: #ef4444; transform: translateX(-4px); }
    .week-day-cell { min-height: 60px; cursor: pointer; transition: background 0.15s; }
    .week-day-cell:hover { background: rgba(255,255,255,0.03); }
    .week-day-cell.drop-hover { background: rgba(59, 130, 246, 0.15) !important; }
    #dispatchLoading { transition: opacity 0.2s; }
</style>

<div class="flex gap-4 h-[calc(100vh-5rem)]" id="dispatchApp">
    <!-- Unassigned Leads Sidebar -->
    <div class="w-72 flex-shrink-0 bg-gray-900 rounded-xl border border-gray-800 flex flex-col">
        <div class="p-3 border-b border-gray-800">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-white">Unassigned Leads</h3>
                <span id="unassignedCount" class="bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
            </div>
            <input type="text" id="sidebarSearch" placeholder="Search leads..."
                   class="w-full px-3 py-1.5 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500">
        </div>
        <div id="unassignedList" class="flex-1 overflow-y-auto p-2 space-y-1.5">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- Main Board Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Controls -->
        <div class="flex items-center justify-between mb-3 flex-shrink-0">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-white">Dispatch Board</h2>
                <div class="flex bg-gray-800 rounded-lg overflow-hidden border border-gray-700">
                    <button id="btnDayView" onclick="switchView('day')" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600">Day</button>
                    <button id="btnWeekView" onclick="switchView('week')" class="px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white">Week</button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="navigateDate(-1)" class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <input type="date" id="datePicker" class="px-3 py-1.5 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                       onchange="loadEvents()">
                <button onclick="navigateDate(1)" class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button onclick="goToToday()" class="px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white bg-gray-800 border border-gray-700 rounded-lg transition-colors">Today</button>
                <select id="statusFilter" onchange="loadEvents()" class="px-3 py-1.5 text-xs bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <?php foreach ($options['statuses'] as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Date label -->
        <div class="mb-2 flex-shrink-0">
            <span id="dateLabel" class="text-sm text-gray-400"></span>
        </div>

        <!-- Loading spinner -->
        <div id="dispatchLoading" class="hidden items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </div>

        <!-- Day View Grid -->
        <div id="dayView" class="flex-1 overflow-auto bg-gray-900 rounded-xl border border-gray-800">
            <!-- Populated by JS -->
        </div>

        <!-- Week View Grid -->
        <div id="weekView" class="flex-1 overflow-auto bg-gray-900 rounded-xl border border-gray-800 hidden">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<!-- Book Modal (for clicking empty slot) -->
<div id="bookModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
    <div class="bg-gray-900 rounded-xl border border-gray-700 p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-white mb-4">Book Appointment</h3>
        <div class="space-y-3">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Lead</label>
                <select id="bookLeadSelect" class="w-full px-3 py-2 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500">
                    <option value="">Select a lead...</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Rep</label>
                    <select id="bookRepSelect" class="w-full px-3 py-2 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500">
                        <?php foreach ($reps as $rep): ?>
                        <option value="<?= $rep['id'] ?>"><?= $e($rep['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Duration (min)</label>
                    <select id="bookDuration" class="w-full px-3 py-2 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500">
                        <option value="30">30 min</option>
                        <option value="60" <?= $defaultDuration == 60 ? 'selected' : '' ?>>60 min</option>
                        <option value="90" <?= $defaultDuration == 90 ? 'selected' : '' ?>>90 min</option>
                        <option value="120" <?= $defaultDuration == 120 ? 'selected' : '' ?>>120 min</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Date</label>
                    <input type="date" id="bookDate" class="w-full px-3 py-2 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Time</label>
                    <input type="time" id="bookTime" class="w-full px-3 py-2 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500">
                </div>
            </div>
        </div>
        <div id="bookConflictWarning" class="hidden mt-3 p-2 bg-yellow-900/50 border border-yellow-700 rounded-lg text-yellow-300 text-xs"></div>
        <div class="flex justify-end gap-3 mt-5">
            <button onclick="closeBookModal()" class="px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors">Cancel</button>
            <button onclick="submitBooking()" id="bookSubmitBtn" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">Book</button>
        </div>
    </div>
</div>

<script>
// Config
const CSRF_TOKEN = '<?= $e(CsrfMiddleware::generateToken()) ?>';
const REPS = <?= json_encode(array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name']], $reps)) ?>;
const DEFAULT_DURATION = <?= $defaultDuration ?>;
const START_HOUR = 7;
const END_HOUR = 18;
const SLOT_MINUTES = 30;
const STATUS_COLORS = {
    'Booked': 'bg-blue-600 border-blue-500', 'booked': 'bg-blue-600 border-blue-500',
    'Quoted': 'bg-orange-600 border-orange-500', 'quoted': 'bg-orange-600 border-orange-500',
    'Won': 'bg-green-600 border-green-500', 'won': 'bg-green-600 border-green-500',
    'Lost': 'bg-gray-600 border-gray-500', 'lost': 'bg-gray-600 border-gray-500',
    'Cancelled': 'bg-gray-600 border-gray-500', 'cancelled': 'bg-gray-600 border-gray-500',
    'Assigned': 'bg-blue-600 border-blue-500', 'assigned': 'bg-blue-600 border-blue-500',
    'New': 'bg-blue-600 border-blue-500', 'new': 'bg-blue-600 border-blue-500',
};
const DEFAULT_COLOR = 'bg-blue-600 border-blue-500';

let currentView = 'day';
let currentDate = new Date().toISOString().split('T')[0];
let eventsData = [];
let unassignedData = [];
let dragData = null;

// Init
document.getElementById('datePicker').value = currentDate;
loadEvents();
loadUnassigned();

// Update current time indicator every minute
setInterval(updateCurrentTimeLine, 60000);

function switchView(view) {
    currentView = view;
    document.getElementById('btnDayView').className = view === 'day'
        ? 'px-3 py-1.5 text-xs font-medium text-white bg-blue-600'
        : 'px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white';
    document.getElementById('btnWeekView').className = view === 'week'
        ? 'px-3 py-1.5 text-xs font-medium text-white bg-blue-600'
        : 'px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white';
    document.getElementById('dayView').classList.toggle('hidden', view !== 'day');
    document.getElementById('weekView').classList.toggle('hidden', view !== 'week');
    loadEvents();
}

function navigateDate(offset) {
    const d = new Date(currentDate);
    if (currentView === 'week') {
        d.setDate(d.getDate() + (offset * 7));
    } else {
        d.setDate(d.getDate() + offset);
    }
    currentDate = d.toISOString().split('T')[0];
    document.getElementById('datePicker').value = currentDate;
    loadEvents();
}

function goToToday() {
    currentDate = new Date().toISOString().split('T')[0];
    document.getElementById('datePicker').value = currentDate;
    loadEvents();
}

function updateDateLabel() {
    const d = new Date(currentDate + 'T12:00:00');
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    if (currentView === 'week') {
        const mon = getMonday(d);
        const sun = new Date(mon);
        sun.setDate(sun.getDate() + 6);
        document.getElementById('dateLabel').textContent =
            mon.toLocaleDateString('en-AU', { month: 'short', day: 'numeric' }) + ' — ' +
            sun.toLocaleDateString('en-AU', { month: 'short', day: 'numeric', year: 'numeric' });
    } else {
        document.getElementById('dateLabel').textContent = d.toLocaleDateString('en-AU', opts);
    }
}

function getMonday(d) {
    d = new Date(d);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

// ===== DATA LOADING =====

async function loadEvents() {
    updateDateLabel();
    const loading = document.getElementById('dispatchLoading');
    loading.classList.remove('hidden');
    loading.classList.add('flex');

    try {
        const statusFilter = document.getElementById('statusFilter')?.value || '';
        const filterParam = statusFilter ? `&status=${encodeURIComponent(statusFilter)}` : '';
        const resp = await fetch(`/api/dispatch/events?date=${currentDate}&view=${currentView}${filterParam}`);
        const data = await resp.json();
        eventsData = data.events || [];
    } catch (e) {
        eventsData = [];
    }

    loading.classList.add('hidden');
    loading.classList.remove('flex');

    if (currentView === 'day') renderDayView();
    else renderWeekView();
}

async function loadUnassigned(search = '') {
    try {
        const resp = await fetch(`/api/dispatch/unassigned?search=${encodeURIComponent(search)}`);
        const data = await resp.json();
        unassignedData = data.leads || [];
    } catch (e) {
        unassignedData = [];
    }
    renderUnassigned();
}

// Sidebar search
let searchTimer = null;
document.getElementById('sidebarSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadUnassigned(this.value), 300);
});

// ===== RENDER UNASSIGNED SIDEBAR =====

function renderUnassigned() {
    const list = document.getElementById('unassignedList');
    document.getElementById('unassignedCount').textContent = unassignedData.length;

    if (unassignedData.length === 0) {
        list.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">No unassigned leads</p>';
        return;
    }

    list.innerHTML = unassignedData.map(lead => `
        <div class="sidebar-lead p-2.5 bg-gray-800/50 rounded-lg border border-gray-700/50"
             draggable="true"
             data-lead-id="${lead.id}"
             data-lead-name="${esc(lead.customer_name)}"
             ondragstart="onDragStartSidebar(event, ${lead.id})"
             ondragend="onDragEnd(event)">
            <p class="text-sm font-medium text-white truncate">${esc(lead.customer_name)}</p>
            <p class="text-xs text-gray-400 truncate">${esc(lead.suburb || '')}${lead.source ? ' &middot; ' + esc(lead.source) : ''}</p>
            <p class="text-xs text-gray-500 truncate mt-0.5">${esc(lead.products_interested || '')}</p>
            <p class="text-xs text-gray-600 mt-0.5">${formatDate(lead.created_at)}</p>
        </div>
    `).join('');
}

// ===== RENDER DAY VIEW =====

function renderDayView() {
    const container = document.getElementById('dayView');
    const slots = [];
    for (let h = START_HOUR; h < END_HOUR; h++) {
        for (let m = 0; m < 60; m += SLOT_MINUTES) {
            slots.push(String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0'));
        }
    }

    let html = '<div class="overflow-x-auto"><table class="w-full border-collapse min-w-[800px]">';

    // Header row with time slots
    html += '<thead><tr><th class="sticky left-0 z-10 bg-gray-900 px-3 py-2 text-left text-xs font-medium text-gray-400 border-b border-r border-gray-800 w-36 min-w-[144px]">Rep</th>';
    slots.forEach(slot => {
        html += `<th class="px-1 py-2 text-center text-xs font-medium text-gray-500 border-b border-gray-800 min-w-[80px]">${slot}</th>`;
    });
    html += '</tr></thead><tbody>';

    // Rep rows
    REPS.forEach((rep, ri) => {
        const rowBg = ri % 2 === 0 ? 'bg-gray-900' : 'bg-gray-900/70';
        html += `<tr class="${rowBg}">`;
        html += `<td class="sticky left-0 z-10 ${rowBg} px-3 py-2 text-sm font-medium text-gray-300 border-r border-gray-800 whitespace-nowrap">${esc(rep.name)}</td>`;

        slots.forEach(slot => {
            // Find events starting at this slot for this rep
            const event = eventsData.find(ev =>
                ev.assigned_to == rep.id &&
                ev.appointment_time &&
                ev.appointment_time.substring(0, 5) === slot
            );

            const slotId = `slot-${rep.id}-${slot}`;
            html += `<td id="${slotId}" class="time-slot border-b border-gray-800/50 border-r border-gray-800/30 p-0.5 align-top relative"
                         data-rep-id="${rep.id}" data-time="${slot}" data-date="${currentDate}"
                         ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event)"
                         onclick="onSlotClick(${rep.id}, '${currentDate}', '${slot}')">`;

            if (event) {
                const duration = parseInt(event.appointment_duration) || 60;
                const slotsSpan = Math.max(1, Math.ceil(duration / SLOT_MINUTES));
                const colorClass = STATUS_COLORS[event.status] || DEFAULT_COLOR;
                const widthPercent = slotsSpan * 100;

                html += `<div class="event-block ${colorClass} border-l-2 rounded px-1.5 py-1 text-xs cursor-grab"
                              style="width: ${widthPercent}%; min-width: 100%;"
                              draggable="true"
                              data-lead-id="${event.id}"
                              data-event="1"
                              ondragstart="onDragStartEvent(event, ${event.id})"
                              ondragend="onDragEnd(event)"
                              onclick="event.stopPropagation(); window.location='/leads/${event.id}'">
                    <p class="font-medium text-white truncate">${esc(event.customer_name)}</p>
                    <p class="text-white/70 truncate">${esc(event.suburb || '')}${event.products_interested ? ' &middot; ' + esc(truncate(event.products_interested, 20)) : ''}</p>
                </div>`;
            }
            html += '</td>';
        });
        html += '</tr>';
    });

    html += '</tbody></table>';

    // Current time indicator
    html += '<div id="currentTimeLine" class="current-time-line hidden"><div class="current-time-dot"></div></div>';
    html += '</div>';

    container.innerHTML = html;
    updateCurrentTimeLine();
}

// ===== RENDER WEEK VIEW =====

function renderWeekView() {
    const container = document.getElementById('weekView');
    const mon = getMonday(new Date(currentDate + 'T12:00:00'));
    const days = [];
    const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    for (let i = 0; i < 7; i++) {
        const d = new Date(mon);
        d.setDate(d.getDate() + i);
        days.push({
            date: d.toISOString().split('T')[0],
            label: dayNames[i] + ' ' + d.getDate() + '/' + (d.getMonth() + 1),
            isToday: d.toISOString().split('T')[0] === new Date().toISOString().split('T')[0]
        });
    }

    let html = '<div class="overflow-x-auto"><table class="w-full border-collapse">';

    // Header
    html += '<thead><tr><th class="sticky left-0 z-10 bg-gray-900 px-3 py-2 text-left text-xs font-medium text-gray-400 border-b border-r border-gray-800 w-36">Rep</th>';
    days.forEach(day => {
        const todayClass = day.isToday ? 'text-blue-400' : 'text-gray-400';
        html += `<th class="px-2 py-2 text-center text-xs font-medium ${todayClass} border-b border-gray-800 min-w-[120px]">${day.label}</th>`;
    });
    html += '</tr></thead><tbody>';

    REPS.forEach((rep, ri) => {
        const rowBg = ri % 2 === 0 ? 'bg-gray-900' : 'bg-gray-900/70';
        html += `<tr class="${rowBg}">`;
        html += `<td class="sticky left-0 z-10 ${rowBg} px-3 py-2 text-sm font-medium text-gray-300 border-r border-gray-800 whitespace-nowrap">${esc(rep.name)}</td>`;

        days.forEach(day => {
            const dayEvents = eventsData.filter(ev => ev.assigned_to == rep.id && ev.appointment_date === day.date);
            const todayBorder = day.isToday ? 'border-l-2 border-l-blue-500' : '';

            html += `<td class="week-day-cell ${todayBorder} border-b border-gray-800/50 border-r border-gray-800/30 p-1.5 align-top"
                         data-rep-id="${rep.id}" data-date="${day.date}"
                         ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDropWeek(event)"
                         onclick="onWeekCellClick('${day.date}')">`;

            if (dayEvents.length > 0) {
                html += `<span class="inline-block bg-blue-600/30 text-blue-300 text-xs font-medium px-1.5 py-0.5 rounded mb-1">${dayEvents.length} appt${dayEvents.length > 1 ? 's' : ''}</span>`;
                dayEvents.slice(0, 3).forEach(ev => {
                    const colorClass = STATUS_COLORS[ev.status] || DEFAULT_COLOR;
                    html += `<div class="text-xs truncate py-0.5 px-1 rounded ${colorClass} bg-opacity-30 text-white/80 mb-0.5 cursor-pointer hover:brightness-125"
                                  onclick="event.stopPropagation(); window.location='/leads/${ev.id}'">${ev.appointment_time ? ev.appointment_time.substring(0,5) : ''} ${esc(ev.customer_name)}</div>`;
                });
                if (dayEvents.length > 3) {
                    html += `<p class="text-xs text-gray-500">+${dayEvents.length - 3} more</p>`;
                }
            }
            html += '</td>';
        });
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// ===== DRAG AND DROP =====

function onDragStartSidebar(e, leadId) {
    const lead = unassignedData.find(l => l.id === leadId);
    dragData = { type: 'sidebar', leadId, lead };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', leadId);
    e.target.classList.add('dragging');
}

function onDragStartEvent(e, leadId) {
    dragData = { type: 'event', leadId };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', leadId);
    e.target.classList.add('dragging');
    e.stopPropagation();
}

function onDragEnd(e) {
    e.target.classList.remove('dragging');
    document.querySelectorAll('.drop-hover').forEach(el => el.classList.remove('drop-hover'));
    dragData = null;
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('drop-hover');
}

function onDragLeave(e) {
    e.currentTarget.classList.remove('drop-hover');
}

async function onDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drop-hover');
    if (!dragData) return;

    const repId = parseInt(e.currentTarget.dataset.repId);
    const time = e.currentTarget.dataset.time;
    const date = e.currentTarget.dataset.date || currentDate;

    if (dragData.type === 'sidebar') {
        await bookAppointment(dragData.leadId, repId, date, time, DEFAULT_DURATION);
    } else if (dragData.type === 'event') {
        await moveAppointment(dragData.leadId, repId, date, time);
    }
    dragData = null;
}

async function onDropWeek(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drop-hover');
    if (!dragData) return;

    const repId = parseInt(e.currentTarget.dataset.repId);
    const date = e.currentTarget.dataset.date;

    if (dragData.type === 'sidebar') {
        // Open book modal with pre-filled rep and date
        openBookModal(repId, date, '09:00');
        document.getElementById('bookLeadSelect').value = dragData.leadId;
    } else if (dragData.type === 'event') {
        await moveAppointment(dragData.leadId, repId, date, '09:00');
    }
    dragData = null;
}

function onWeekCellClick(date) {
    currentDate = date;
    document.getElementById('datePicker').value = date;
    switchView('day');
}

function onSlotClick(repId, date, time) {
    openBookModal(repId, date, time);
}

// ===== BOOKING MODAL =====

function openBookModal(repId, date, time) {
    // Populate lead select with unassigned leads
    const sel = document.getElementById('bookLeadSelect');
    sel.innerHTML = '<option value="">Select a lead...</option>' +
        unassignedData.map(l => `<option value="${l.id}">${esc(l.customer_name)} — ${esc(l.suburb || 'No suburb')}</option>`).join('');

    document.getElementById('bookRepSelect').value = repId;
    document.getElementById('bookDate').value = date;
    document.getElementById('bookTime').value = time;
    document.getElementById('bookConflictWarning').classList.add('hidden');
    document.getElementById('bookDuration').value = DEFAULT_DURATION;

    const modal = document.getElementById('bookModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeBookModal() {
    const modal = document.getElementById('bookModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function submitBooking() {
    const leadId = document.getElementById('bookLeadSelect').value;
    const repId = document.getElementById('bookRepSelect').value;
    const date = document.getElementById('bookDate').value;
    const time = document.getElementById('bookTime').value;
    const duration = document.getElementById('bookDuration').value;

    if (!leadId || !repId || !date || !time) {
        showToast('Please fill in all fields', 'error');
        return;
    }

    await bookAppointment(parseInt(leadId), parseInt(repId), date, time, parseInt(duration));
    closeBookModal();
}

// ===== API CALLS =====

async function bookAppointment(leadId, repId, date, time, duration) {
    const btn = document.getElementById('bookSubmitBtn');
    if (btn) btn.disabled = true;

    try {
        const resp = await fetch('/api/dispatch/book', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead_id: leadId, rep_id: repId, date, time, duration, _csrf_token: CSRF_TOKEN })
        });
        const data = await resp.json();

        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showToast(data.message + ' (Warning: overlaps with ' + data.conflicts.length + ' appointment(s))', 'info');
            } else {
                showToast(data.message, 'success');
            }
            loadEvents();
            loadUnassigned(document.getElementById('sidebarSearch').value);
        } else {
            showToast(data.error || 'Failed to book', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }

    if (btn) btn.disabled = false;
}

async function moveAppointment(leadId, newRepId, newDate, newTime) {
    try {
        const resp = await fetch('/api/dispatch/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead_id: leadId, new_rep_id: newRepId, new_date: newDate, new_time: newTime, _csrf_token: CSRF_TOKEN })
        });
        const data = await resp.json();

        if (data.success) {
            if (data.conflicts && data.conflicts.length > 0) {
                showToast(data.message + ' (Warning: overlaps with ' + data.conflicts.length + ' appointment(s))', 'info');
            } else {
                showToast(data.message, 'success');
            }
            loadEvents();
        } else {
            showToast(data.error || 'Failed to move', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

// ===== CURRENT TIME LINE =====

function updateCurrentTimeLine() {
    const line = document.getElementById('currentTimeLine');
    if (!line || currentView !== 'day') return;

    const now = new Date();
    const todayStr = now.toISOString().split('T')[0];
    if (currentDate !== todayStr) { line.classList.add('hidden'); return; }

    const totalMinutes = now.getHours() * 60 + now.getMinutes();
    const startMin = START_HOUR * 60;
    const endMin = END_HOUR * 60;
    if (totalMinutes < startMin || totalMinutes > endMin) { line.classList.add('hidden'); return; }

    const table = document.querySelector('#dayView table');
    if (!table) return;

    const headerCells = table.querySelectorAll('thead th');
    if (headerCells.length < 2) return;

    const firstSlotRect = headerCells[1].getBoundingClientRect();
    const lastSlotRect = headerCells[headerCells.length - 1].getBoundingClientRect();
    const tableRect = table.getBoundingClientRect();

    const totalWidth = lastSlotRect.right - firstSlotRect.left;
    const fraction = (totalMinutes - startMin) / (endMin - startMin);
    const leftPos = (firstSlotRect.left - tableRect.left) + (fraction * totalWidth);

    line.style.left = leftPos + 'px';
    line.classList.remove('hidden');
}

// ===== UTILS =====

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-AU', { day: 'numeric', month: 'short' });
}

function showToast(message, type) {
    const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-blue-600' };
    const bg = colors[type] || 'bg-gray-600';
    const toast = document.createElement('div');
    toast.className = `toast fixed top-4 right-4 z-50 ${bg} text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>
