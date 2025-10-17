<?php
session_start();
require_once '../config.php'; // تعريف $conn = new mysqli(...)

// حماية الصفحة: فقط للأعضاء
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'member') {
  header('Location: ../index.php'); 
  exit;
}

$name = $_SESSION['name'] ?? 'Member';
$userId = $_SESSION['id'];
$userEmail = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Task Manager — Member</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Navbar -->
  <nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-4">
      <div class="bg-green-500 text-white p-2 rounded-lg">👤</div>
      <div>
        <h2 class="font-semibold">Task Manager</h2>
        <span class="text-sm text-gray-500">Member Dashboard</span>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($name); ?></span>
      <a class="text-blue-600 hover:underline" href="logout.php">Logout</a>
    </div>
  </nav>

  <!-- Main -->
  <main class="max-w-5xl mx-auto py-8 px-4">
    <!-- Welcome -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <h2 class="text-xl font-semibold">Welcome back, <?php echo htmlspecialchars($name); ?>!</h2>
      <p class="text-gray-500 text-sm">Here are your assigned tasks.</p>
    </div>

    <!-- Counters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
        <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">✓</div>
        <div><p class="text-sm text-gray-500">Total Tasks</p><p id="totalTasks" class="text-xl font-bold">0</p></div>
      </div>
      <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
        <div class="bg-yellow-100 text-yellow-600 p-2 rounded-lg">⏳</div>
        <div><p class="text-sm text-gray-500">In Progress</p><p id="inProgress" class="text-xl font-bold">0</p></div>
      </div>
      <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
        <div class="bg-green-100 text-green-600 p-2 rounded-lg">✔</div>
        <div><p class="text-sm text-gray-500">Completed</p><p id="completed" class="text-xl font-bold">0</p></div>
      </div>
      <div class="bg-white shadow rounded-lg p-4 flex items-center gap-3">
        <div class="bg-red-100 text-red-600 p-2 rounded-lg">●</div>
        <div><p class="text-sm text-gray-500">Pending</p><p id="pending" class="text-xl font-bold">0</p></div>
      </div>
    </div>

    <!-- Filter + List -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-semibold mb-4">My Tasks</h3>

      <div id="taskFilter" class="bg-gray-200 rounded-lg flex p-3 gap-3 mb-4">
        <button data-filter="all" class="px-3 py-1 rounded bg-white">All Tasks</button>
        <button data-filter="pending" class="px-3 py-1 rounded">Pending</button>
        <button data-filter="inProgress" class="px-3 py-1 rounded">In Progress</button>
        <button data-filter="completed" class="px-3 py-1 rounded">Completed</button>
      </div>

      <div id="TaskItem"></div>
    </div>
  </main>
<script defer src="../js/dashboardMember.js?v=8"></script>
</body>   
</html>