const API = '../member/tasks_api.php'; // بدون debug أو all
let tasks = [];
let currentFilter = 'all';

const elTotal = document.getElementById('totalTasks');
const elInProg = document.getElementById('inProgress');
const elCompleted = document.getElementById('completed');
const elPending = document.getElementById('pending');
const list = document.getElementById('TaskItem');
const filterWrap = document.getElementById('taskFilter');

function escapeHtml(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));}

function priorityStyle(p){
    const v = String(p||'').toLowerCase();
    if(v==='high') return {cls:'text-red-600 bg-red-100', label:'High'};
    if(v==='medium') return {cls:'text-yellow-600 bg-yellow-100', label:'Normal'};
    return {cls:'text-green-600 bg-green-100', label:'Low'};
}

function toApiStatus(camel){
    if(camel==='inProgress') return 'inProgress';
    if(camel==='completed') return 'completed';
    return 'pending';
}

function computeStats(arr){
    const s = {total:arr.length,pending:0,inProgress:0,completed:0};
    for(const t of arr){
        s[t.status]++;
    }
    return s;
}

async function load(){
    try{
        const r = await fetch(API,{credentials:'include'});
        if(!r.ok) throw new Error('GET failed '+r.status);
        const data = await r.json();
        tasks = Array.isArray(data.tasks)?data.tasks:[];
        const s = computeStats(tasks);

        elTotal.textContent = s.total;
        elPending.textContent = s.pending;
        elInProg.textContent = s.inProgress;
        elCompleted.textContent = s.completed;

        renderFilters(s);
        renderList();
    }catch(e){
        console.error(e);
        list.innerHTML = '<p class="text-red-600">Failed to load tasks.</p>';
    }
}

function renderFilters(stats){
    const items = [
        {key:'all', label:'All Tasks', count:stats.total},
        {key:'pending', label:'Pending', count:stats.pending},
        {key:'inProgress', label:'In Progress', count:stats.inProgress},
        {key:'completed', label:'Completed', count:stats.completed},
    ];
    filterWrap.innerHTML='';
    for(const it of items){
        const active = currentFilter===it.key;
        filterWrap.insertAdjacentHTML('beforeend',`
            <button data-filter="${it.key}" class="filter-btn w-1/4 px-4 py-2 rounded-lg border ${active?'bg-white text-blue-500':'text-black'}">
                ${it.label} (${it.count})
            </button>
        `);
    }
}

function renderList(){
    list.innerHTML='';
    let arr = tasks;
    if(currentFilter!=='all') arr = tasks.filter(t=>t.status===currentFilter);
    if(!arr.length){ list.innerHTML='<p class="text-gray-500">No tasks to show.</p>'; return; }

    for(const t of arr){
        const statusLabel = t.status==='inProgress'?'In Progress':t.status.charAt(0).toUpperCase()+t.status.slice(1);
        const statusCls = t.status==='completed'?'bg-green-100 text-green-600':t.status==='inProgress'?'bg-yellow-100 text-yellow-600':'bg-gray-200 text-black';
        const {cls: prioCls, label: prioLabel} = priorityStyle(t.priority);

        list.insertAdjacentHTML('beforeend',`
            <div class="border rounded-lg p-4 m-2">
                <div class="flex justify-between items-center">
                    <h4 class="font-semibold">${escapeHtml(t.title)}</h4>
                    <span class="p-1 rounded-lg ${prioCls}">${escapeHtml(prioLabel)}</span>
                </div>
                ${t.description?`<p class="text-gray-600 text-sm mt-1">${escapeHtml(t.description)}</p>`:''}
                <p class="text-sm text-gray-500 mt-2">
                    Due: <span class="text-red-500">${t.due_date?escapeHtml(t.due_date):'No due date'}</span>
                </p>
                <div class="flex items-center justify-between mt-3">
                    <div class="flex items-center">
                        <p class="text-black px-2 py-1 text-sm font-medium">Status:</p>
                        <span class="px-2 py-1 rounded text-sm font-medium ${statusCls}">${escapeHtml(statusLabel)}</span>
                    </div>
                    <div class="space-x-2">
                        <button class="px-2 py-1 border rounded text-sm" data-action="set" data-id="${t.id}" data-status="pending">Pending</button>
                        <button class="px-2 py-1 border rounded text-sm" data-action="set" data-id="${t.id}" data-status="inProgress">In&nbsp;Progress</button>
                        <button class="px-2 py-1 bg-green-500 text-white rounded text-sm" data-action="set" data-id="${t.id}" data-status="completed">Completed</button>
                    </div>
                </div>
            </div>
        `);
    }
}

filterWrap.addEventListener('click', e=>{
    const btn = e.target.closest('button[data-filter]');
    if(!btn) return;
    currentFilter = btn.dataset.filter;
    renderFilters(computeStats(tasks));
    renderList();
});

list.addEventListener('click', async e=>{
    const btn = e.target.closest('button[data-action="set"]');
    if(!btn) return;
    const id = Number(btn.dataset.id);
    const camel = btn.dataset.status;
    if(!id||!camel) return;
    await updateStatus(id,camel);
});

async function updateStatus(id,camelStatus){
    try{
        const apiStatus = toApiStatus(camelStatus);
        const body = `id=${encodeURIComponent(id)}&status=${encodeURIComponent(apiStatus)}`;
        const r = await fetch(API,{
            method:'PATCH',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body, credentials:'include'
        });
        if(!r.ok){ const msg = await r.text(); alert('Failed to update: '+msg); return; }
        await load();
    }catch(e){ console.error(e); alert('Network error.'); }
}

load();
