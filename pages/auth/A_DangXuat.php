<?php

session_start();

$_SESSION = [];

session_destroy();

header("Location: ../main/B_homepage.html?logout=success");
exit;