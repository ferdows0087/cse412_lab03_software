<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$query = mysqli_query($conn, "SELECT Id, file_name, email, uploaded_at, img_dir, folder_name FROM uploads_files ORDER BY uploaded_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | Uploaded Files</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">
  <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-lg p-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-800">📂 Uploaded Files</h1>
      <div class="space-x-4">
        <a href="homepage.php" class="text-sm text-indigo-600 hover:underline">🏠 Homepage</a>
        <a href="logout.php" class="text-sm text-red-500 hover:underline">🚪 Logout</a>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full table-auto border border-gray-200 rounded-md">
        <thead class="bg-gray-100 text-gray-700 text-sm font-semibold">
          <tr>
            <th class="px-4 py-3 text-left">📄 File Name</th>
            <th class="px-4 py-3 text-left">📧 Email</th>
            <th class="px-4 py-3 text-left">📅 Uploaded At</th>
            <th class="px-4 py-3 text-left">📁 Folder Name</th>
            <th class="px-4 py-3 text-left">🔗 File</th>
            <th class="px-4 py-3 text-left">✏️ Rename</th>
          </tr>
        </thead>
        <tbody class="text-sm text-gray-700 divide-y divide-gray-200">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= htmlspecialchars($row['file_name']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($row['email']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($row['uploaded_at']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($row['folder_name']) ?></td>
                <td class="px-4 py-3">
                  <a href="<?= htmlspecialchars($row['img_dir']) ?>" target="_blank" class="text-blue-600 hover:underline">View</a>
                </td>
                <td class="px-4 py-3">
                  <form method="POST" action="rename.php" class="flex space-x-2">
                    <input type="hidden" name="id" value="<?= $row['Id'] ?>">
                    <input type="hidden" name="type" value="file">
                    <input type="text" name="new_name" placeholder="New folder name" required class="border rounded px-2 py-1 w-32 text-sm">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">Rename</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-gray-500 py-6">No files uploaded yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
