<?php
session_start();
session_unset();
session_destroy();
header("Location: /elearning_htttql/auth/login.php");
exit();