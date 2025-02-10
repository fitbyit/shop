<?php
// forgot-password.php - Password Reset Process
session_start();
require 'db.php';
require 'mail.php';

$errorMessage = "";
$step = 1; // Default step is email entry

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        $sql = "SELECT id, username FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate OTP for password reset
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", time() + (2 * 60)); // OTP expires in 2 minutes
            
            $updateQuery = "UPDATE users SET otp=?, otp_expiry=? WHERE email=?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sss", $otp, $otp_expiry, $email);
            $stmt->execute();
            
            // Send OTP via email
            $subject = "Your Password Reset OTP";
            $body = "Hello {$user['username']},\n\nYour OTP for password reset is: $otp\n\nThis code is valid for 2 minutes. Do not share it with anyone.";
            sendMail($email, $subject, $body);
            
            $_SESSION['reset_email'] = $email;
            $step = 2;
        } else {
            $errorMessage = "Email not found!";
        }
    } elseif (isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
        $email = $_SESSION['reset_email'];
        $now = date("Y-m-d H:i:s");
        
        $sql = "SELECT * FROM users WHERE email=? AND otp=? AND otp_expiry >= ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $otp, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $step = 3;
        } else {
            $errorMessage = "Invalid or expired OTP!";
        }
    } elseif (isset($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $email = $_SESSION['reset_email'];
        
        $sql = "UPDATE users SET password=?, otp=NULL, otp_expiry=NULL WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_password, $email);
        $stmt->execute();
        
        unset($_SESSION['reset_email']);
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-800 to-blue-900 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full animate-fade-in">
        <h2 class="text-2xl font-bold text-center text-indigo-800 mb-8">Forgot Password</h2>
        <p id="error-message" class="text-red-500 text-sm mb-4 hidden"></p>
        
        <?php if ($step == 1): ?>
        <form action="forgot-password.php" method="POST" class="space-y-6" novalidate>
            <div>
                <label for="email" class="block text-indigo-900 font-semibold mb-2">Email</label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="w-full bg-indigo-800 text-white py-3 rounded-lg font-semibold hover:bg-blue-900">Send OTP</button>
        </form>
        
        <?php elseif ($step == 2): ?>
        <form action="forgot-password.php" method="POST" class="space-y-6" novalidate>
            <div>
                <label for="otp" class="block text-indigo-900 font-semibold mb-2">Enter OTP</label>
                <input type="text" name="otp" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300" placeholder="Enter OTP" required>
            </div>
            <button type="submit" class="w-full bg-indigo-800 text-white py-3 rounded-lg font-semibold hover:bg-blue-900">Verify OTP</button>
        </form>
        
        <?php elseif ($step == 3): ?>
        <form action="forgot-password.php" method="POST" class="space-y-6" novalidate>
            <div>
                <label for="new_password" class="block text-indigo-900 font-semibold mb-2">New Password</label>
                <input type="password" name="new_password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300" placeholder="Enter new password" required>
            </div>
            <button type="submit" class="w-full bg-indigo-800 text-white py-3 rounded-lg font-semibold hover:bg-blue-900">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let errorMessage = "<?php echo $errorMessage; ?>";
        if (errorMessage) {
            let errorElement = document.getElementById("error-message");
            errorElement.innerText = errorMessage;
            errorElement.classList.remove("hidden");
        }
    });
</script>
