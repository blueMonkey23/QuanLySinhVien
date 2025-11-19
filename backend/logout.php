<?php
require 'config.php';
session_destroy();
echo json_encode(['success' => true]);
?>