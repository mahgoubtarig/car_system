<?php
$password = '1122334455';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Your hash is: <br>";
echo "<b>" . $hash . "</b>";
?>