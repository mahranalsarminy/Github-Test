<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['theme'])) {
        $_SESSION['theme'] = $_POST['theme'];
    }
    if (isset($_POST['lang'])) {
        $_SESSION['lang'] = $_POST['lang'];
    }
}
