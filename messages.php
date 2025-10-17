<?php if (!empty($errors)): ?>
  <div class="mb-4 rounded-lg bg-red-50 border-l-4 border-red-500 p-3 text-sm text-red-700 shadow">
    <ul class="list-disc list-inside space-y-1">
      <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
  <div class="mb-4 rounded-lg bg-green-50 border-l-4 border-green-500 p-3 text-sm text-green-700 shadow">
    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
  </div>
<?php endif; ?>