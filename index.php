<?php
require_once 'server.php';  // استدعاء السيرفر مرّة واحدة فقط
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Task Manager - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50">
  <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
    <h1 class="text-2xl font-bold text-center">Task Manager</h1>
    <p class="text-gray-500 text-center mb-6">Organize your team's workflow</p>

    <div class="flex justify-evenly mb-6 bg-gray-200 rounded-lg p-2">
      <button type="button" id="loginTab" class="w-1/2 px-4 py-2 bg-white rounded-lg border border-gray-200 text-blue-600 font-medium border-b-2">Login</button>
      <button type="button" id="registerTab" class="w-1/2 px-4 py-2 text-gray-500 font-medium hover:text-blue-600 rounded-lg">Register</button>
    </div>

    <!-- Login -->
    <div id="loginForm">
      <?php
      $errors = $login_errors ?? [];
      include 'messages.php';
      ?>
      <form action="#" method="POST" class="space-y-4">
        <div>
          <label for="emailLogin" class="block text-sm font-medium text-gray-700">Email Address</label>
          <input type="email" id="emailLogin" name="email" placeholder="Enter your email" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:border-blue-500" />
          <div id="emailError" class="text-red-600 text-sm"></div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:border-blue-500" />
          <div id="passwordError" class="text-red-600 text-sm"></div>
        </div>

        <button id="loginBtn" type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg shadow" name="login">
          Sign In
        </button>
      </form>
    </div>

    <!-- Register -->
    <div id="registerForm" class="hidden">
      <?php
      $errors = $register_errors ?? [];
      include 'messages.php';
      ?>
      <form action="#" method="POST" class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Full Name</label>
          <input type="text" placeholder="Full Name" class="w-full p-2 border rounded-lg focus:border-blue-500 focus:outline-none" name="full-name">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Email Address</label>
          <input type="email" placeholder="Email" class="w-full p-2 border rounded-lg focus:border-blue-500 focus:outline-none" name="email">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Password</label>
          <input type="password" placeholder="Password" class="w-full p-2 border rounded-lg focus:border-blue-500 focus:outline-none" name="password">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <select class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none" name="role">
            <option class="font-bold" value="member">Team Member</option>
            <option class="font-bold" value="manager">Manager</option>
          </select>
        </div>
        <button type="submit" id="registerBtn" name="register" class="w-full mt-2 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">Create Account</button>
      </form>
    </div>
  </div>

  <script src="js/login.js"></script>
</body>
</html>