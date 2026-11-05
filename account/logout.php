<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); user_logout();
redirect(base_url(''));
