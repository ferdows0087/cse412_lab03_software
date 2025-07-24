<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $type = $_POST['type']; // 'file' or 'folder'
    $new_name = trim($_POST['new_name']);

    if ($type === 'file') {
        // Get current file info
        $stmt = $conn->prepare("SELECT file_name, img_dir FROM uploads_files WHERE Id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $file = $result->fetch_assoc();

        if ($file) {
            $old_path = $file['img_dir'];
            $old_name = $file['file_name'];
            $upload_dir = dirname($old_path);
            $file_ext = pathinfo($old_name, PATHINFO_EXTENSION);

            $new_file_name = $new_name . "." . $file_ext;
            $new_path = $upload_dir . "/" . $new_file_name;

            // Rename the physical file
            if (file_exists($old_path)) {
                if (rename($old_path, $new_path)) {
                    // Update in DB
                    $update = $conn->prepare("UPDATE uploads_files SET file_name = ?, img_dir = ? WHERE Id = ?");
                    $update->bind_param("ssi", $new_file_name, $new_path, $id);
                    if ($update->execute()) {
                        echo "<script>alert('✅ File renamed successfully.'); window.location.href = 'dashboard.php';</script>";
                        exit();
                    } else {
                        echo "<script>alert('❌ DB update failed.'); window.location.href = 'dashboard.php';</script>";
                        exit();
                    }
                } else {
                    echo "<script>alert('❌ Failed to rename file on server.'); window.location.href = 'dashboard.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('❌ Original file not found.'); window.location.href = 'dashboard.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('❌ File not found in DB.'); window.location.href = 'dashboard.php';</script>";
            exit();
        }
    }

    // Folder rename (optional)
    elseif ($type === 'folder') {
        $update = $conn->prepare("UPDATE folders SET folder_name = ? WHERE Id = ?");
        $update->bind_param("si", $new_name, $id);

        if ($update->execute()) {
            echo "<script>alert('✅ Folder renamed.'); window.location.href = 'dashboard.php';</script>";
        } else {
            echo "<script>alert('❌ Folder rename failed.'); window.location.href = 'dashboard.php';</script>";
        }
        exit();
    }

    else {
        echo "<script>alert('Invalid type.'); window.location.href = 'dashboard.php';</script>";
        exit();
    }
}
?>
