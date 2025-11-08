<?php
include "dbconnect.php";

if (isset($_POST['login'])) {

    if (empty($_POST['username']) || empty($_POST['password'])) {
        echo "<script>
                alert('Please fill in all fields.');
                window.location.href = 'login-main.php';
              </script>";
        exit;
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    // $password = md5($password); // uncomment if registration hashes passwords

    // --- Prepare statement for security ---
    if (!($stmt = $dbcnx->prepare("SELECT UserID, Username, UserPassword, Email FROM users WHERE Username = ? AND UserPassword = ? LIMIT 1"))) {
        die("Database error: " . $dbcnx->error);
    }
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        session_start();
        $_SESSION['sess_user'] = $row['Username'];
        $_SESSION['sess_user_id'] = $row['UserID'];
        $_SESSION['sess_user_email'] = $row['Email'];

        // ✅ If username is admin → redirect to admin page
        if (strtolower($row['Username']) === 'admin') {
            header("Location: adminMovie.php");
            exit;
        }

        // ✅ Otherwise normal user → go home
        header("Location: index.php");
        exit;
    } else {
        echo "<script>
                alert('Login failed. Please try again.');
                window.location.href = 'login-main.php';
              </script>";
        exit;
    }
}
?>
