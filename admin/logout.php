<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); admin_logout();
redirect(base_url('admin/login.php'));
