<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

if ($user = mysqli_fetch_assoc($query)) {
    $user_id = $user['Id'];
    $message = '';
} else {
    header("Location: login.php");
    exit();
}



// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $fileName = basename($_FILES['file']['name']);
    $selectedFolder = trim($_POST['folder_name']);
    $targetFolder = $selectedFolder !== "" ? $selectedFolder : "default";

    $targetDir = "uploads/" . $email . "/" . $targetFolder . "/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $targetFile = $targetDir . $fileName;

    // Check if file already exists in the same folder
    $checkQuery = $conn->prepare("SELECT file_name FROM uploads_files WHERE file_name = ? AND email = ? AND folder_name = ?");
    $checkQuery->bind_param("sss", $fileName, $email, $targetFolder);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        echo "<script>alert('⚠️ This file is already submitted in this folder.');</script>";
    } else {
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO uploads_files (file_name, email, uploaded_at, img_dir, folder_name) VALUES (?, ?, NOW(), ?, ?)");
            $stmt->bind_param("ssss", $fileName, $email, $targetFile, $targetFolder);
            if ($stmt->execute()) {
                $message = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">✅ File uploaded to folder: <b>' . htmlspecialchars($targetFolder) . '</b></div>';
            } else {
                $message = '<div class="bg-red-100 text-red-700 p-3 rounded mb-4">❌ DB insert failed.</div>';
            }
        } else {
            $message = '<div class="bg-red-100 text-red-700 p-3 rounded mb-4">❌ File upload failed.</div>';
        }
    }
}

// Check if new folder is provided
if (!empty($_POST['new_folder'])) {
    $newFolder = trim($_POST['new_folder']);

    // Insert new folder into database if it doesn't already exist
    $checkFolder = $conn->prepare("SELECT * FROM folders WHERE folder_name = ? AND user_id = ?");
    $checkFolder->bind_param("si", $newFolder, $user_id);
    $checkFolder->execute();
    $checkFolderResult = $checkFolder->get_result();

    if ($checkFolderResult->num_rows === 0) {
        $insertFolder = $conn->prepare("INSERT INTO folders (folder_name, user_id) VALUES (?, ?)");
        $insertFolder->bind_param("si", $newFolder, $user_id);
        $insertFolder->execute();
    }

    // Set the selected folder to new one
    $_POST['folder_name'] = $newFolder;
}




?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="text-center py-20 px-4">
        <p class="text-5xl font-bold">
            Hello <?= htmlspecialchars($user['firstName'].' '.$user['lastName']) ?> :)
        </p>

        <!-- Success or error message -->
        <div class="max-w-md mx-auto mt-6">
            <?= $message ?>
        </div>

        <!-- File Upload Form -->
        <div class="max-w-md mx-auto mt-6 bg-white p-6 rounded-lg shadow-md">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload File</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="file" type="file" class="sr-only" required>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PDF or TXT up to 5MB</p>
                        </div>
                    </div>
                </div>
                    <?php
                        $folderOptions = "";
                        $folderQuery = $conn->prepare("SELECT folder_name FROM folders WHERE user_id = ?");
                        $folderQuery->bind_param("i", $user_id);
                        $folderQuery->execute();
                        $foldersResult = $folderQuery->get_result();
                        while ($row = $foldersResult->fetch_assoc()) {
                            $folderName = htmlspecialchars($row['folder_name']);
                            $folderOptions .= "<option value=\"$folderName\">$folderName</option>";
                            }
                            ?>
                            <!-- Folder Dropdown -->
                            
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Folder</label>
                            <select name="folder_name" class="w-full border border-gray-300 rounded-md p-2">
                            <option value="">Default</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Image">Image</option>
                            <option value="Other">Other</option>
                            <?= $folderOptions ?>
                            </select>
                        </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Submit
                    </button>
                    <a href="dashboard.php" class="flex-1 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-center text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Dashboard
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-8">
            <a href="logout.php" class="text-red-600 hover:text-red-800 font-medium">Logout</a>
        </div>
    </div>
</body>
</html>