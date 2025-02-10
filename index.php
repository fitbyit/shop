<?php
// login.php - User Login and Redirection Logic
session_start();
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit();
}
require 'db.php';
require 'mail.php';

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $sql = "SELECT id, username, password, verified FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['verified'] = $user['verified'];
            if ($user['verified'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                // Generate and send new OTP
                $otp = rand(100000, 999999);
                $otp_expiry = date("Y-m-d H:i:s", time() + (2 * 60)); // OTP expires in 2 minutes
                
                $updateQuery = "UPDATE users SET otp=?, otp_expiry=? WHERE email=?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sss", $otp, $otp_expiry, $email);
                $stmt->execute();
                
                // Send new OTP via email
                $subject = "Your New OTP Code";
                $body = "Hello {$user['username']},\n\nYour new OTP code is: $otp\n\nThis code is valid for 2 minutes. Do not share it with anyone.";
                sendMail($email, $subject, $body);
                
                // Redirect to OTP page
                header("Location: otp.php?email=" . urlencode($email));
                exit();
            }
        } else {
            $errorMessage = "Invalid email or password!";
        }
    } else {
        $errorMessage = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-800 to-blue-900 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full animate-fade-in">
        <h2 class="text-2xl font-bold text-center text-indigo-800 mb-8">Sign in to Your Account</h2>
        <p id="error-message" class="text-red-500 text-sm mb-4 hidden"></p>
        <form action="index.php" method="POST" class="space-y-6" novalidate>
            <div>
                <label for="email" class="block text-indigo-900 font-semibold mb-2">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300" 
                    placeholder="Enter your email" 
                    required
                >
            </div>

            <div>
                <label for="password" class="block text-indigo-900 font-semibold mb-2">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300" 
                    placeholder="Enter your password" 
                    required
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-indigo-800 text-white py-3 rounded-lg font-semibold hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-800 focus:ring-offset-2 transition-all duration-300 transform hover:scale-[1.02]"
            >
                Login
            </button>
        </form>
        <div class="text-center mt-4">
            <a href="forgot-password.php" class="text-indigo-600 hover:text-indigo-400">Forgot Password?</a>
        </div>
        <div class="text-center mt-4">
            <p class="text-gray-600">Don't have an account? <a href="signup.php" class="text-indigo-600 hover:text-indigo-400">Sign Up</a></p>
        </div>
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
