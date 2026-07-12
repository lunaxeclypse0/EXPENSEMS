<?php
require_once __DIR__ . '/../config/bootstrap.php';

requireLogin();
destroyCurrentSession();
redirectTo(BASE_URL . 'views/login.php');
