<?php
session_start();
session_destroy();
header("Location: /macjInventory/Landingpage/SignIn.php");
exit;
?>