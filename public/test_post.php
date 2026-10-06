<?php
header('Content-Type: text/plain; charset=utf-8');

echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ POST وصل!\n\n";
    echo "POST Data:\n";
    print_r($_POST);
    
    echo "\nRaw Input:\n";
    echo file_get_contents('php://input');
} else {
    echo "GET Request\n";
}
?>

<form method="POST" action="">
    <input type="text" name="username" placeholder="Username" value="admin">
    <input type="password" name="password" placeholder="Password" value="11223344">
    <button type="submit">إرسال</button>
</form>