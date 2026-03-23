<?php
use App\Auth;
use App\Middleware\CsrfMiddleware;

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$statusColors = [
    'Booked'  => 'bg-blue-600 border-blue-500',
    'Quoted'  => 'bg-orange-600 border-orange-500',
    'Won'     => 'bg-green-600 border-green-500',
    'Lost'    => 'bg-gray-600 border-gray-500',
    'Cancelled' => 'bg-gray-600 border-gray-500',
];
?>

<style>
    .cal-slot { min-height: 52px; position: relative; }
    .cal-event { transition: filter 0.15s; }
    .cal-event:hover { filter: brightness(1.15); }
    .current-time-line { position: absolute; top: 0; bottom: 0; width: 2px; background: #ef4444; z-index: 20; pointer-events: none; }
    .current-time-dot { position: absolute; top: -4px; width: 10px; height: 10px; border-radius: 50%; background: #ef4444; transform: translateX(-4px); }
</style>

<div class="max-w-6xl mx-auto">
    <!-- Controls -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-white">My Calendar</h2>
            <div class="flex bg-gray-800 rounded-lg overflow-hidden border border-gray-700">
                <button id="btnDay" onclick="switchCalView('day')" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600">Day</button>
                <button id="btnWeek" onclick="switchCalView('week')" class="px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white">Week</button>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="navDate(-1)" class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <input type="date" id="calDate" class="px-3 py-1.5 text-sm bg-gray-800 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                   onchange="loadCal()">
            <button onclick="navDate(1)" class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button onclick="goToday()" class="px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white bg-gray-800 border border-gray-700 rounded-lg transition-colors">Today</button>
        </div>
    </div>

    <div class="mb-2">
        <span id="calLabel" class="text-sm text-gray-400"></span>
    </div>

    <!-- Loading -->
    <div id="calLoading" class="hidden items-center justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-blue-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>

    <!-- Day View -->
    <div id="calDayView" class="bg-gray-900 rounded-xl border border-gray-800 overflow-x-auto">
    </div>

    <!-- Week View -->
    <div id="calWeekView" class="bg-gray-900 rounded-xl border border-gray-800 hidden">
    </div>
</div>

<script>
const MY_REP_ID = <?= Auth::id() ?>;
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
};
const DEFAULT_COLOR = 'bg-blue-600 border-blue-500';

let calView = 'day';
let calDate = new Date().toISOString().split('T')[0];
let calEvents = [];

document.getElementById('calDate').value = calDate;
loadCal();
setInterval(updateTimeLine, 60000);

function switchCalView(view) {
    calView = view;
    document.getElementById('btnDay').className = view === 'day'
        ? 'px-3 py-1.5 text-xs font-medium text-white bg-blue-600'
        : 'px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white';
    document.getElementById('btnWeek').className = view === 'week'
        ? 'px-3 py-1.5 text-xs font-medium text-white bg-blue-600'
        : 'px-3 py-1.5 text-xs font-medium text-gray-400 hover:text-white';
    document.getElementById('calDayView').classList.toggle('hidden', view !== 'day');
    document.getElementById('calWeekView').classList.toggle('hidden', view !== 'week');
    loadCal();
}

function navDate(offset) {
    const d = new Date(calDate);
    d.setDate(d.getDate() + (calView === 'week' ? offset * 7 : offset));
    calDate = d.toISOString().split('T')[0];
    document.getElementById('calDate').value = calDate;
    loadCal();
}

function goToday() {
    calDate = new Date().toISOString().split('T')[0];
    document.getElementById('calDate').value = calDate;
    loadCal();
}

function updateLabel() {
    const d = new Date(calDate + 'T12:00:00');
    if (calView === 'week') {
        const mon = getMonday(d);
        const sun = new Date(mon); sun.setDate(sun.getDate() + 6);
        document.getElementById('calLabel').textContent =
            mon.toLocaleDateString('en-AU', { month: 'short', day: 'numeric' }) + ' — ' +
            sun.toLocaleDateString('en-AU', { month: 'short', day: 'numeric', year: 'numeric' });
    } else {
        document.getElementById('calLabel').textContent = d.toLocaleDateString('en-AU', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
}

function getMonday(d) {
    d = new Date(d);
    const day = d.getDay();
    d.setDate(d.getDate() - day + (day === 0 ? -6 : 1));
    return d;
}

async function loadCal() {
    updateLabel();
    const loading = document.getElementById('calLoading');
    loading.classList.remove('hidden');
    loading.classList.add('flex');

    try {
        const resp = await fetch(`/api/dispatch/events?date=${calDate}&view=${calView}&rep_id=${MY_REP_ID}`);
        const data = await resp.json();
        calEvents = data.events || [];
    } catch (e) {
        calEvents = [];
    }

    loading.classList.add('hidden');
    loading.classList.remove('flex');

    if (calView === 'day') renderDay();
    else renderWeek();
}

function renderDay() {
    const container = document.getElementById('calDayView');
    const slots = [];
    for (let h = START_HOUR; h < END_HOUR; h++) {
        for (let m = 0; m < 60; m += SLOT_MINUTES) {
            slots.push(String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0'));
        }
    }

    let html = '<table class="w-full border-collapse">';
    html += '<thead><tr>';
    slots.forEach(slot => {
        html += `<th class="px-2 py-2.5 text-center text-xs font-medium text-gray-500 border-b border-gray-800 min-w-[100px]">${slot}</th>`;
    });
    html += '</tr></thead><tbody><tr>';

    slots.forEach(slot => {
        const event = calEvents.find(ev => ev.appointment_time && ev.appointment_time.substring(0, 5) === slot);
        html += `<td class="cal-slot border-b border-gray-800/50 border-r border-gray-800/30 p-1 align-top">`;

        if (event) {
            const duration = parseInt(event.appointment_duration) || 60;
            const slotsSpan = Math.max(1, Math.ceil(duration / SLOT_MINUTES));
            const colorClass = STATUS_COLORS[event.status] || DEFAULT_COLOR;

            html += `<a href="/leads/${event.id}" class="cal-event block ${colorClass} border-l-2 rounded px-2 py-1.5 text-xs no-underline"
                        style="width: ${slotsSpan * 100}%; min-width: 100%;">
                <p class="font-medium text-white truncate">${esc(event.customer_name)}</p>
                <p class="text-white/70 truncate">${esc(event.suburb || '')}</p>
                <p class="text-white/60 truncate mt-0.5">${esc(event.products_interested || '')}</p>
                <p class="text-white/50 mt-0.5">${event.appointment_time ? event.appointment_time.substring(0,5) : ''} — ${duration}min</p>
            </a>`;
        }
        html += '</td>';
    });

    html += '</tr></tbody></table>';

    // If no events
    if (calEvents.length === 0) {
        html += '<div class="text-center py-8 text-gray-500 text-sm">No appointments for this day</div>';
    }

    container.innerHTML = html;
    updateTimeLine();
}

function renderWeek() {
    const container = document.getElementById('calWeekView');
    const mon = getMonday(new Date(calDate + 'T12:00:00'));
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

    let html = '<div class="grid grid-cols-7 divide-x divide-gray-800">';

    days.forEach(day => {
        const dayEvents = calEvents.filter(ev => ev.appointment_date === day.date);
        const todayClass = day.isToday ? 'border-t-2 border-t-blue-500' : '';
        const todayText = day.isToday ? 'text-blue-400' : 'text-gray-400';

        html += `<div class="min-h-[200px] ${todayClass}">`;
        html += `<div class="px-2 py-2 border-b border-gray-800">
            <p class="text-xs font-medium ${todayText}">${day.label}</p>
        </div>`;
        html += '<div class="p-1.5 space-y-1">';

        if (dayEvents.length === 0) {
            html += '<p class="text-xs text-gray-600 text-center py-3">No appointments</p>';
        } else {
            dayEvents.forEach(ev => {
                const colorClass = STATUS_COLORS[ev.status] || DEFAULT_COLOR;
                html += `<a href="/leads/${ev.id}" class="block ${colorClass} border-l-2 rounded px-2 py-1.5 text-xs no-underline hover:brightness-110">
                    <p class="text-white/60">${ev.appointment_time ? ev.appointment_time.substring(0,5) : ''}</p>
                    <p class="font-medium text-white truncate">${esc(ev.customer_name)}</p>
                    <p class="text-white/70 truncate">${esc(ev.suburb || '')}</p>
                </a>`;
            });
        }

        html += '</div></div>';
    });

    html += '</div>';
    container.innerHTML = html;
}

function updateTimeLine() {
    // Simple time indicator for day view - just highlight current slot
    if (calView !== 'day') return;
    // Minimal implementation for rep view
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
