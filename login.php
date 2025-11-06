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

    $username = $_POST['username'];
    $password = $_POST['password'];
    // $password = md5($password); // Uncomment once register.php hashes passwords

    $query = "SELECT * FROM users WHERE Username='" . $dbcnx->real_escape_string($username) . "' 
              AND UserPassword='" . $dbcnx->real_escape_string($password) . "'";

    $result = $dbcnx->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $dbusername = $row['Username'];
        $dbpassword = $row['UserPassword'];
        $dbuserID   = $row['UserID'];
        $dbuserEmail= $row['Email'];

        if ($username === $dbusername && $password === $dbpassword) {
            session_start();
            $_SESSION['sess_user'] = $username;
            $_SESSION['sess_user_id'] = $dbuserID;
            $_SESSION['sess_user_email'] = $dbuserEmail;

            header("Location: index.php");
            exit;
        } else {
            echo "<script>
                    alert('Login failed. Please try again.');
                    window.location.href = 'login-main.php';
                  </script>";
            exit;
        }
    } else {
        echo "<script>
                alert('Login failed. Please try again.');
                window.location.href = 'login-main.php';
              </script>";
        exit;
    }
}
?>
