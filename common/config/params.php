<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'telegram_bot_token' => null,
    'telegram_chat_id' => null,
    'message_thread_id' => null,
    // ISPmanager → Cloudflare DNS sync; tokens live in cloudflare.accounts.<label>.dnsApiKey (params-local).
    'dnsSync' => [
        'types' => ['A', 'AAAA', 'CNAME', 'MX', 'TXT'],
        'proxied' => ['A' => true, 'AAAA' => true, 'CNAME' => true],
        'proxiedExclude' => ['mail', 'smtp', 'pop', 'imap', 'ftp'], // A/AAAA only
        'ttl' => 1,
        'maxBatchOperations' => 200, // Free plan limit
    ],
];
