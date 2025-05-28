<?php
function flash($type, $message) {
    $_SESSION['flash'][$type][] = $message;
} 