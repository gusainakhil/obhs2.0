<?php
session_start();
session_destroy();
header('Location: https://obhs.beatleanalytics.in/');
exit;   