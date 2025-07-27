<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db');
define('DB_USER', 'library');
define('DB_PASS', 'library');

// 邮件配置
define('SMTP_HOST', 'smtp.qq.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jianyingfanwen@qq.com');
define('SMTP_PASS', 'kbutaappnlerebae');
define('SMTP_ENCRYPT', 'ssl');
define('SMTP_FROM', 'jianyingfanwen@qq.com');
define('SMTP_FROM_NAME', '依依家的猫窝');

// 应用配置
define('CODE_EXPIRE', 300); // 验证码有效期(秒)
define('MIN_USERNAME_LEN', 3);
define('MAX_USERNAME_LEN', 20);
define('MIN_PASSWORD_LEN', 8);

// 是否开启调试模式
define('DEBUG_MODE', true); 
?>