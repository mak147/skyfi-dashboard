<?php

declare(strict_types=1);

return [
    // RouterOS api-ssl uses TCP/TLS; retain peer verification in every non-local environment.
    'credential_encryption_key' => getenv('MIKROTIK_CREDENTIAL_ENCRYPTION_KEY') ?: '',
    'connect_timeout_seconds' => max(1, (int) (getenv('MIKROTIK_API_CONNECT_TIMEOUT_SECONDS') ?: 5)),
    'command_timeout_seconds' => max(1, (int) (getenv('MIKROTIK_API_COMMAND_TIMEOUT_SECONDS') ?: 10)),
    'max_retries' => max(0, min(3, (int) (getenv('MIKROTIK_API_MAX_RETRIES') ?: 2))),
    'max_connections_per_router' => max(1, min(4, (int) (getenv('MIKROTIK_API_MAX_CONNECTIONS_PER_ROUTER') ?: 1))),
    'tls_verify_peer' => filter_var(getenv('MIKROTIK_API_TLS_VERIFY_PEER') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'tls_ca_file' => getenv('MIKROTIK_API_TLS_CA_FILE') ?: null,
];
