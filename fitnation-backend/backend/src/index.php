<?php
// Database connection details (from environment variables)
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Create a connection to the PostgreSQL database using PDO
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}

// Process the form if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $unique_code = $_POST['unique_code'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    // Check if the user already exists in the 'users' table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR contact = :contact");
    $stmt->execute([':email' => $email, ':contact' => $contact]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        echo "<p>User already exists. Please use a different email or contact number.</p>";
    } else {
        // Read the promo_codes.csv file and load it into an array
        $file = fopen("/var/www/html/promo_codes.csv", "r");  // Ensure the path is correct
        if ($file !== false) {
            // Skip the header line
            fgetcsv($file);

            // Initialize a flag to check if the code exists
            $is_code_valid = false;
            $vCode = '';

            // Read each line of the CSV and check if the unique code matches the vCode
            while (($line = fgetcsv($file)) !== false) {
                $hexCode = $line[0];    // The hexCode from the CSV (not used for validation)
                $vCode = $line[1];      // The vCode from the CSV (which we compare with the user input)

                // Check if the user input matches any promo code (vCode) in the CSV
                if ($unique_code == $vCode) {
                    $is_code_valid = true;
                    break;
                }
            }
            fclose($file);

            // If the code is valid, insert the user data and mark the code as used
            if ($is_code_valid) {
                // Mark the code as used in the database
                $stmt = $pdo->prepare("UPDATE promo_codes SET used = TRUE WHERE code = :code");
                $stmt->execute([':code' => $vCode]);

                // Generate a voucher code
                $voucher_code = generateVoucherCode();

                // Insert the user's details into the 'users' table, including voucher_code and promo_code
                $stmt = $pdo->prepare("INSERT INTO users (name, email, contact, voucher_code, promo_code) 
                                       VALUES (:name, :email, :contact, :voucher_code, :promo_code)");
                $stmt->execute([':name' => $name, ':email' => $email, ':contact' => $contact, 
                                ':voucher_code' => $voucher_code, ':promo_code' => $unique_code]);

                // Display the voucher code
                echo "<p>Voucher Code: " . $voucher_code . "</p>";
            } else {
                echo "<p>Invalid code or the code has already been used.</p>";
            }
        } else {
            echo "<p>Error reading the CSV file.</p>";
        }
    }
}

// Function to generate a random voucher code
function generateVoucherCode() {
    return "FIT" . strtoupper(bin2hex(random_bytes(4)));  // Example: FIT8F4A9D
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitNation Promotional Code</title>
    <style>
        /* Basic styling here */
    </style>
</head>
<body>

    <div class="form-container">
        <h2>FitNation Promo Code Validation</h2>
        <form action="index.php" method="POST">
            <div class="input-group">
                <label for="unique_code">Enter Promo Code (from CSV vCode):</label>
                <input type="text" id="unique_code" name="unique_code" required>
            </div>
            <div class="input-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="contact">Contact Number:</label>
                <input type="text" id="contact" name="contact" required>
            </div>
            <div class="input-group">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>

</body>
</html>