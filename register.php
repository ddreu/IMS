<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include_once 'connection/conn.php';
    $conn = con();

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Retrieve form data and sanitize input
    $school_name = $conn->real_escape_string($_POST['school_name']);
    $school_code = $conn->real_escape_string($_POST['school_code']);
    $address = $conn->real_escape_string($_POST['address']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password hashing

    // Check if school name or email already exists
    $checkSchool = "SELECT * FROM schools WHERE school_name = '$school_name'";
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";

    $schoolResult = $conn->query($checkSchool);
    $emailResult = $conn->query($checkEmail);

    if ($schoolResult->num_rows > 0) {
        $_SESSION['error_message'] = "School name already exists.";
    } elseif ($emailResult->num_rows > 0) {
        $_SESSION['error_message'] = "Email is already registered.";
    } else {
        // Insert into schools table
        $sql_school = "INSERT INTO schools (school_name, school_code, address) VALUES ('$school_name', '$school_code', '$address')";
        if ($conn->query($sql_school) === TRUE) {
            // Retrieve the last inserted school_id
            $school_id = $conn->insert_id;

            // Insert into users table
            $sql_user = "INSERT INTO users (email, password, role, school_id) VALUES ('$email', '$password', 'School Admin', $school_id)";
            if ($conn->query($sql_user) === TRUE) {
                // Retrieve the last inserted user_id
                $user_id = $conn->insert_id;

                // Insert departments into departments table
                if (isset($_POST['departments'])) {
                    $departments = $_POST['departments'];
                    foreach ($departments as $department) {
                        $department = $conn->real_escape_string($department); // Sanitize department input
                        $sql_department = "INSERT INTO departments (department_name, school_id) VALUES ('$department', $school_id)";
                        $conn->query($sql_department);
                    }
                }

                header("Location: schooladmin/schooladmindashboard.php");  // Redirect to dashboard
                exit();
            } else {
                echo "Error: " . $sql_user . "<br>" . $conn->error;
            }
        } else {
            echo "Error: " . $sql_school . "<br>" . $conn->error;
        }
    }

    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="register.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Register</title>
</head>

<body>

    <a href="index.php" class="back-button">Back to Homepage</a>

    <!--<div class="container">-->
    <div class="image-section"></div>

    <div class="form-section">
        <h1>Get Started!</h1>
        <p>Already have an account? <a href="login.php" style="color: #007bff;">Sign in</a></p>
        <form method="POST" action="register.php">
            <div class="form-group">
                <div class="field">
                    <label for="school-name">School Name:</label>
                    <input type="text" id="school-name" name="school_name" placeholder="School Name" required>
                </div>
                <div class="field">
                    <label for="school-code">School Code:</label>
                    <input type="text" id="school-code" name="school_code" placeholder="School Code" required>
                </div>
            </div>
            <div class="form-group">
                <div class="field">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" placeholder="Address" required>
                </div>
                <div class="field">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="departments">Departments Offered:</label><br>
                <input type="checkbox" id="elementary" name="departments[]" value="Elementary">
                <label for="elementary">Elementary</label><br>
                <input type="checkbox" id="jhs" name="departments[]" value="JHS">
                <label for="jhs">Junior High School (JHS)</label><br>
                <input type="checkbox" id="shs" name="departments[]" value="SHS">
                <label for="shs">Senior High School (SHS)</label><br>
                <input type="checkbox" id="college" name="departments[]" value="College">
                <label for="college">College</label>
            </div>

            <div class="form-group">
                <div class="field">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <div class="field">
                    <label for="confirm-password">Confirm Password:</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
            </div>
            <button type="submit">Sign Up</button>
        </form>

        <p style="font-size: 0.8em; color: #888888;">
            By signing up, you agree to the <a href="#" style="color: #007bff;">Terms of Service</a> and <a href="#" style="color: #007bff;">Privacy Policy</a>.
        </p>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: '<?php echo htmlspecialchars($_SESSION['error_message']); ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
            });
            <?php unset($_SESSION['error_message']); // Clear the error message after displaying 
            ?>
        </script>
    <?php endif; ?>

</body>

</html>