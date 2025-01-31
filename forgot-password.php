<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <style>
input {
    width: 40%;
}

@media only screen and (max-width: 600px) {
    input {
        width: 80%;
    }
}
    </style>
</head>
<body>

    <h1>Forgot Password</h1>

    <form method="post" action="send-password-reset.php">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required placeholder="Enter your email">
        <button type="submit">Send</button>
    </form>

    <a href="login.php" class="back-button">Back to Login</a>

</body>
</html>
