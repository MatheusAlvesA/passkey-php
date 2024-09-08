<?php

namespace Deployer;

require 'recipe/common.php';

// Config

set('repository', 'https://github.com/MatheusAlvesA/passkey-php.git');

add('shared_files', [
    '.env'
]);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('192.168.1.2')
    ->set('remote_user', 'ubuntu')
    ->set('deploy_path', '/var/www/deploys');

// Tasks

task('build', function () {
    cd('{{release_path}}');
    run('composer install');
});

task('reload_php_fpm_lb', function () {
    run('sudo systemctl reload php8.1-fpm');
});

// Hooks

after('deploy:failed', 'deploy:unlock');
after('deploy:update_code', 'build');
after('success', 'reload_php_fpm_lb');
