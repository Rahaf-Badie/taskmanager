// dashboardManager.js
document.addEventListener('DOMContentLoaded', () => {

  // --------------------------
  // State
  // --------------------------
  let tasks = [];
  let members = [];
  let editingId = null;

  // --------------------------
  // Elements
  // --------------------------
  const btnAddTask = document.getElementById('btnAddTask');
  const windowAddTask = document.getElementById('windowAddTask');
  const closeWindowAddTask = document.getElementById('closeWindowAddTask');
  const cancelBtn = document.getElementById('cancelBtn');
  const addTaskBtn = document.getElementById('addTaskBtn');

  const windowEditTask = document.getElementById('windowEditTask');
  const closeWindowEdirTask = document.getElementById('closeWindowEdirTask');
  const cancelEditBtn = document.getElementById('cancelEditBtn');
  const saveEditBtn = document.getElementById('saveEditBtn');

  const taskTable = document.getElementById('taskTable');
  const taskAssigned = document.getElementById('taskAssigned');
  const editTaskAssigned = document.getElementById('editTaskAssigned');

  const taskTitle = document.getElementById('taskTitle');
  const taskDescription = document.getElementById('taskDescription');
  const taskPriority = document.getElementById('taskPriority');
  const taskStatus = document.getElementById('taskStatus');
  const taskDueDate = document.getElementById('taskDueDate');

  const editTaskTitle = document.getElementById('editTaskTitle');
  const editTaskDescription = document.getElementById('editTaskDescription');
  const editTaskPriority = document.getElementById('editTaskPriority');
  const editTaskStatus = document.getElementById('editTaskStatus');
  const editTaskDueDate = document.getElementById('editTaskDueDate');

  const totalTasks = document.getElementById('totalTasks');
  const inProgress = document.getElementById('inProgress');
  const completed = document.getElementById('completed');
  const pending = document.getElementById('pending');

  // --------------------------
  // Helper Functions
  // --------------------------
  function showAddModal() { windowAddTask.classList.remove('hidden'); }
  function closeAddModal() { 
    windowAddTask.classList.add('hidden'); 
    document.getElementById('addtaskForm').reset();
  }

  function showEditModal() { windowEditTask.classList.remove('hidden'); }
  function closeEditModal() { 
    windowEditTask.classList.add('hidden'); 
    editingId = null;
  }

  function updateStats(stats) {
    if (!stats) return;
    totalTasks.innerText = stats.total ?? 0;
    inProgress.innerText = stats.inProgress ?? 0;
    completed.innerText = stats.completed ?? 0;
    pending.innerText = stats.pending ?? 0;
  }

  function renderTable(tasksData) {
    if (!taskTable) return;
    taskTable.innerHTML = '';
    tasksData.forEach(t => {
      const row = document.createElement('tr');
      row.classList.add('divide-y', 'divide-gray-200', 'text-sm');
      row.innerHTML = `
        <td class="p-3 align-top whitespace-nowrap">${t.title}</td>
        <td class="p-3 align-top whitespace-nowrap">${t.assigned_name}</td>
        <td class="p-3 align-top whitespace-nowrap">${t.status}</td>
        <td class="p-3 align-top whitespace-nowrap">${t.priority}</td>
        <td class="p-3 align-top whitespace-nowrap">${t.due_date}</td>
        <td class="p-3 align-top whitespace-nowrap">
          <button class="editTask text-indigo-600 mr-2" data-id="${t.id}">Edit</button>
          <button class="deleteTask text-red-600" data-id="${t.id}">Delete</button>
        </td>
      `;
      taskTable.appendChild(row);
    });

    document.querySelectorAll('.editTask').forEach(btn => {
      btn.addEventListener('click', () => {
        const task = tasksData.find(x => x.id == btn.dataset.id);
        if (!task) return;
        editingId = task.id;
        editTaskTitle.value = task.title;
        editTaskDescription.value = task.description;
        editTaskPriority.value = task.priority;
        editTaskStatus.value = task.status;
        editTaskDueDate.value = task.due_date;
        editTaskAssigned.value = task.user_id;
        showEditModal();
      });
    });

    document.querySelectorAll('.deleteTask').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to delete this task?')) return;
        try {
          const res = await fetch('/taskmanager/manager/tasks_api.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${btn.dataset.id}`
          });
          const data = await res.json();
          if (data.ok) loadTasks();
        } catch(e) { console.error(e); }
      });
    });
  }

  function loadMembers() {
    if (!taskAssigned || !editTaskAssigned) return;
    fetch('/taskmanager/manager/tasks_api.php?mode=members')
      .then(res => res.json())
      .then(data => {
        console.log('Members from API:', data);
        members = data.members ?? [];

        // مسح الخيارات القديمة
        taskAssigned.innerHTML = '';
        editTaskAssigned.innerHTML = '';

        members.forEach(m => {
          const option1 = document.createElement('option');
          option1.value = m.id;
          option1.textContent = m.name;
          taskAssigned.appendChild(option1);

          const option2 = document.createElement('option');
          option2.value = m.id;
          option2.textContent = m.name;
          editTaskAssigned.appendChild(option2);
        });
      })
      .catch(err => console.error('Error loading members:', err));
  }

  async function loadTasks() {
    try {
      const res = await fetch('/taskmanager/manager/tasks_api.php');
      const data = await res.json();
      tasks = data.tasks ?? [];
      renderTable(tasks);
      updateStats(data.stats);
    } catch(e) { console.error(e); }
  }

  // --------------------------
  // Event Listeners
  // --------------------------
  if (btnAddTask) btnAddTask.addEventListener('click', showAddModal);
  if (closeWindowAddTask) closeWindowAddTask.addEventListener('click', closeAddModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeAddModal);

  if (closeWindowEdirTask) closeWindowEdirTask.addEventListener('click', closeEditModal);
  if (cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditModal);

  if (addTaskBtn) addTaskBtn.addEventListener('click', async () => {
    try {
      const formData = new URLSearchParams();
      formData.append('title', taskTitle.value);
      formData.append('description', taskDescription.value);
      formData.append('user_id', taskAssigned.value);
      formData.append('priority', taskPriority.value);
      formData.append('status', taskStatus.value);
      formData.append('due_date', taskDueDate.value);

      const res = await fetch('/taskmanager/manager/tasks_api.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.ok) {
        closeAddModal();
        loadTasks();
      }
    } catch(e){ console.error(e); }
  });

  if (saveEditBtn) saveEditBtn.addEventListener('click', async () => {
    if (!editingId) return;
    try {
      const formData = new URLSearchParams();
      formData.append('id', editingId);
      formData.append('title', editTaskTitle.value);
      formData.append('description', editTaskDescription.value);
      formData.append('user_id', editTaskAssigned.value);
      formData.append('priority', editTaskPriority.value);
      formData.append('status', editTaskStatus.value);
      formData.append('due_date', editTaskDueDate.value);

      const res = await fetch('/taskmanager/manager/tasks_api.php', {
        method: 'PUT',
        body: formData
      });
      const data = await res.json();
      if (data.ok) {
        closeEditModal();
        loadTasks();
      }
    } catch(e){ console.error(e); }
  });

  // --------------------------
  // Init
  // --------------------------
  loadMembers();
  loadTasks();
});
