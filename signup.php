<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-800 to-blue-900 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full animate-fade-in">
        <h2 class="text-2xl font-bold text-center text-indigo-800 mb-8">Create an Account</h2>
        <p id="error-message" class="text-red-500 text-sm mb-4 hidden"></p>
        <form id="registrationForm" action="signup.php" method="POST" class="space-y-6" novalidate>
            <div>
                <label for="username" class="block text-indigo-900 font-semibold mb-2">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-800 transition-all duration-300" 
                    placeholder="Enter your username" 
                    required
                >
            </div>

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
                Register
            </button>
        </form>
    </div>
</body>
</html>

<?php
// signup.php - Handle user registration with OTP email verification
require 'db.php';
require 'mail.php';

session_start();
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $otp = rand(100000, 999999);
    $otp_expiry = date("Y-m-d H:i:s", time() + (2 * 60));
    
    // Check if email already exists
    $checkQuery = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errorMessage = "Email already registered!";
    } else {
        $sql = "INSERT INTO users (username, email, password, otp, otp_expiry) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $password, $otp, $otp_expiry);

        
        if ($stmt->execute()) {
            // Send OTP using mail.php
            $subject = "Your OTP Code";
            $body = "Hello $username,\n\nYour OTP code is: $otp\n\nThis code is valid for 2 minutes. Do not share it with anyone.";
            if (sendMail($email, $subject, $body)) {
                // Redirect to OTP verification page
                header("Location: otp.php?email=" . urlencode($email));
                exit();
            } else {
                $errorMessage = "Error sending email.";
            }
        } else {
            $errorMessage = "Error: " . $conn->error;
        }
    }
    
    $stmt->close();
}
?>

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
