<?php
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'gatitos_jane',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',

    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_user' => getenv('SMTP_USER') ?: 'est.olariaga.geraldine@latecnicalf.com.ar',
    'smtp_pass' => getenv('SMTP_PASS') ?: 'cnaj rmax nkyt secz',
    'smtp_port' => (int) (getenv('SMTP_PORT') ?: 587),
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'Gatitos Jane',
];
