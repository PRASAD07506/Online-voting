<?php

$configuredCode = getenv('ADMIN_REGISTRATION_CODE') ?: 'Admin123';
define('ADMIN_REGISTRATION_CODE', $configuredCode);
