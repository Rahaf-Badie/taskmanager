<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
  header('Location: ../index.php'); exit;
}
$name = $_SESSION['name'] ?? 'Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Task Manager — Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- Navbar -->
  <nav class="bg-white shadow px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="bg-blue-500 text-white p-2 rounded-lg">
        <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
      </div>
      <div class="leading-tight">
        <h1 class="font-semibold">Task Manager</h1>
        <span class="text-sm text-gray-500">Manager Dashboard</span>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <div class="text-right">
        <div class="text-sm font-medium"><?php echo htmlspecialchars($name); ?></div>
        <div class="text-xs text-gray-500">Manager</div>
      </div>
      <a class="text-sm underline" href="../member/logout.php">Logout</a>
    </div>
  </nav>

  <!-- Overlay -->
  <div id="overlay" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-30"></div>

  <!-- Stats -->
  <section class="max-w-6xl mx-auto mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 px-4">
    <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
      <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
        <svg viewBox="0 0 24 24" height="20" width="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path d="M22 5.18 10.59 16.6l-4.24-4.24 1.41-1.41 2.83 2.83 10-10z"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">Total Tasks</p>
        <p id="totalTasks" class="text-xl font-bold">0</p>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
      <div class="bg-yellow-100 text-yellow-600 p-2 rounded-lg">
        <svg viewBox="0 0 24 24" height="20" width="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 8v5h5v2H10V8z"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">In Progress</p>
        <p id="inProgress" class="text-xl font-bold">0</p>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
      <div class="bg-green-100 text-green-600 p-2 rounded-lg">
        <svg viewBox="0 0 24 24" height="20" width="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 16.2 4.8 12 3.4 13.4 9 19 21 7 19.6 5.6z"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">Completed</p>
        <p id="completed" class="text-xl font-bold">0</p>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
      <div class="bg-red-100 text-red-600 p-2 rounded-lg">
        <svg viewBox="0 0 24 24" height="20" width="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path d="M1 21h22L12 2 1 21zM12 16h-1v-4h2v4h-1zm0 2h1v2h-2v-2h1z"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">Pending</p>
        <p id="pending" class="text-xl font-bold">0</p>
      </div>
    </div>
  </section>

  <!-- Table + Add button -->
  <section class="max-w-6xl mx-auto mt-6 bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Team Tasks</h2>
      <button type="button" id="btnAddTask" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">+ Add New Task</button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100 text-left text-sm text-gray-600">
            <th class="p-3">Task</th>
            <th class="p-3">Assigned To</th>
            <th class="p-3">Status</th>
            <th class="p-3">Priority</th>
            <th class="p-3">Due Date</th>
            <th class="p-3">Actions</th>
          </tr>
        </thead>
        <tbody id="taskTable" class="divide-y divide-gray-200 text-sm"></tbody>
      </table>
    </div>
  </section>

  <!-- Add New Task modal -->
  <div id="windowAddTask" class="hidden z-40 fixed inset-0 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-xl shadow-xl p-6 relative">
      <button id="closeWindowAddTask" class="absolute right-3 top-3 text-gray-500 hover:text-black">✕</button>
      <h3 class="text-xl font-semibold mb-5">Add New Task</h3>

      <form id="addtaskForm" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Task Title *</label>
          <input id="taskTitle" type="text" required class="mt-1 w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"/>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description *</label>
          <textarea id="taskDescription" required class="mt-1 w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"></textarea>
          <div class="text-xs text-gray-500 mt-1"><span id="descCounter">0</span>/500</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Assign To *</label>
            <select id="taskAssigned" class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"></select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Priority</label>
            <select id="taskPriority" class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500">
              <option>Low</option>
              <option selected>Medium</option>
              <option>High</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select id="taskStatus" class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500">
              <option value="pending">Pending</option>
              <option value="inProgress">inProgress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Due Date *</label>
            <input id="taskDueDate" type="date" required class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"/>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" id="cancelBtn" class="px-4 py-2 rounded-lg border">Cancel</button>
          <button type="button" id="addTaskBtn" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Add Task</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Task modal -->
  <div id="windowEditTask" class="hidden z-40 fixed inset-0 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-xl shadow-xl p-6 relative">
      <button id="closeWindowEdirTask" class="absolute right-3 top-3 text-gray-500 hover:text-black">✕</button>
      <h3 class="text-xl font-semibold mb-5">Edit Task</h3>

      <form id="editTaskForm" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Task Title *</label>
          <input id="editTaskTitle" type="text" required class="mt-1 w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"/>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description *</label>
          <textarea id="editTaskDescription" required class="mt-1 w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Assign To *</label>
            <select id="editTaskAssigned" class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"></select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Priority</label>
            <select id="editTaskPriority" class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500">
              <option>Low</option>
              <option>Medium</option>
              <option>High</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select id="editTaskStatus" class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500">
              <option value="pending">Pending</option>
              <option value="inProgress">inProgress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Due Date *</label>
            <input id="editTaskDueDate" type="date" required class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:border-indigo-500"/>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" id="cancelEditBtn" class="px-4 py-2 rounded-lg border">Cancel</button>
          <button type="button" id="saveEditBtn" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/dashboardManager.js"></script>
</body>
</html>

