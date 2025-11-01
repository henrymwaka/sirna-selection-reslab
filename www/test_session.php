<?php
session_start();
if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = bin2hex(random_bytes(4));
    echo "First visit — session started, value saved: " . $_SESSION['test'];
} else {
    echo "Session persists! Value: " . $_SESSION['test'];
}
