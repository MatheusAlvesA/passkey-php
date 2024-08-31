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

host('104.248.230.198')
    ->set('remote_user', 'root')
    ->set('deploy_path', '/var/www/deploys');

// Tasks

task('build', function () {
    cd('{{release_path}}');
    run('composer install');
});

// Hooks

after('deploy:failed', 'deploy:unlock');
after('deploy:update_code', 'build');
