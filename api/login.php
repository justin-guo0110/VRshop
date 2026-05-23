<?php

header("Content-Type: application/json");

$host = "localhost";
$dbname = "vr_mall";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error)
{
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}

$email = $_POST["email"];
$password = $_POST["password"];

$sql = "SELECT member_id, password_hash
        FROM members
        WHERE email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc())
{
    $hash = $row["password_hash"];

    $valid = false;

    // BCrypt
    if (str_starts_with($hash, '$2y$'))
    {
        $valid = password_verify($password, $hash);
    }
    else
    {
        // 舊明文密碼
        $valid = ($password == $hash);
    }

    if ($valid)
    {
        echo json_encode([
            "success" => true,
            "member_id" => $row["member_id"]
        ]);
    }
    else
    {
        echo json_encode([
            "success" => false,
            "message" => "Wrong password"
        ]);
    }
}
else
{
    echo json_encode([
        "success" => false,
        "message" => "Account not found"
    ]);
}

$conn->close();

?>