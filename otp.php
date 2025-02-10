<?php
// otp.php - OTP Verification and Redirection to Dashboard
session_start();
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require 'db.php';

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $otp = preg_replace('/\s+/', '', trim($_POST['otp'])); // Remove spaces
    $now = date("Y-m-d H:i:s");
    
    $sql = "SELECT * FROM users WHERE email=? AND otp=? AND otp_expiry >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $otp, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Mark user as verified and clear OTP
        $updateQuery = "UPDATE users SET otp=NULL, otp_expiry=NULL, verified=1 WHERE email=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Start session and store user ID
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        $errorMessage = "Invalid or expired OTP!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-800 to-blue-900 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full animate-fade-in">
        <h2 class="text-2xl font-bold text-center text-indigo-800 mb-8">Enter OTP</h2>
        <p id="error-message" class="text-red-500 text-sm mb-4 hidden"></p>
        <form action="otp.php" method="POST" class="space-y-6" novalidate>
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            <div>
                <label for="otp" class="block text-indigo-900 font-semibold mb-2">OTP Code</label>
                <input type="text" id="otp" name="otp" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300">
            </div>
            <button type="submit" class="w-full bg-indigo-800 text-white py-3 rounded-lg font-semibold hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-800 focus:ring-offset-2 transition-all duration-300 transform hover:scale-[1.02]">Verify OTP</button>
        </form>
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
