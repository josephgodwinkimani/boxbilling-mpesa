<?php return array(
    'root' => array(
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'type' => 'project',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => '17d91537eb7a6b4b79c4a05e16788918f828e46b',
        'name' => 'boxbilling/mpesa',
        'dev' => true,
    ),
    'versions' => array(
        'boxbilling/mpesa' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'type' => 'project',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => '17d91537eb7a6b4b79c4a05e16788918f828e46b',
            'dev_requirement' => false,
        ),
        'monolog/monolog' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'type' => 'library',
            'install_path' => __DIR__ . '/../monolog/monolog',
            'aliases' => array(
                0 => '2.x-dev',
            ),
            'reference' => 'fb2c324c17941ffe805aa7c953895af96840d0c9',
            'dev_requirement' => true,
        ),
        'psr/log' => array(
            'pretty_version' => '1.1.4',
            'version' => '1.1.4.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/log',
            'aliases' => array(),
            'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11',
            'dev_requirement' => true,
        ),
        'psr/log-implementation' => array(
            'dev_requirement' => true,
            'provided' => array(
                0 => '1.0.0 || 2.0.0 || 3.0.0',
            ),
        ),
        'safaricom/mpesa' => array(
            'pretty_version' => '1.0.8',
            'version' => '1.0.8.0',
            'type' => 'package',
            'install_path' => __DIR__ . '/../safaricom/mpesa',
            'aliases' => array(),
            'reference' => 'c66895dcdec8df1f496e017708c557fc252340c5',
            'dev_requirement' => false,
        ),
        'squizlabs/php_codesniffer' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'library',
            'install_path' => __DIR__ . '/../squizlabs/php_codesniffer',
            'aliases' => array(
                0 => '3.x-dev',
            ),
            'reference' => '586a51f839039dd295aeab1921d2aa49c85b7b2b',
            'dev_requirement' => true,
        ),
    ),
);
