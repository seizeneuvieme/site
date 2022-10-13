<?php

echo "PREPARE DEPLOYING TO PRODUCTION...\n";

echo "Please enter a release name: ";
$handle = fopen ("php://stdin","r");
$release = fgets($handle);

echo "Creating $release branch...\n";
echo shell_exec('git switch main');
echo shell_exec('git pull');
echo shell_exec('git switch -c ' . $release);

// Connexion SSH et rsync
echo "Preparing project for production...\n";
echo shell_exec('rm -rf ./.github');
echo shell_exec('rm -rf ./.phpunit.cache');
echo shell_exec('rm -rf ./tests');
echo shell_exec('rm -rf ./var');
echo shell_exec('rm -rf ./.env.test');
echo shell_exec('rm -rf ./.gitignore');
echo shell_exec('rm -rf ./.php_cs.php');
echo shell_exec('rm -rf ./.phpunit.result.cache');
echo shell_exec('rm -rf ./docker-compose.yml');
echo shell_exec('rm -rf ./docker-compose-test.yml');
echo shell_exec('rm -rf ./Makefile');
echo shell_exec('rm -rf ./phpstan.neon');
echo shell_exec('rm -rf ./phpunit.xml');
echo shell_exec('rm -rf ./README.md');
echo shell_exec('rm -rf ./public/build');
echo shell_exec('yarn build');
echo shell_exec('rm -rf ./node_modules');
echo shell_exec('yarn install --production');
echo shell_exec('touch .env');
echo shell_exec('echo APP_ENV=PROD >> .env');
echo shell_exec('echo DEBUG=0 >> .env');
echo shell_exec('rm -rf ./vendor');
echo shell_exec('composer install --no-dev');
echo shell_exec('APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear');
echo shell_exec('touch .gitignore');
echo shell_exec('echo prepare-deploy.php > .gitignore');
echo shell_exec('rm -rf assets');
echo shell_exec('rm -rf var');
echo shell_exec('mv .env.prod .env');

echo "\n\n[OK] DEPLOYING TO PRODUCTION PREPARED!\n";
echo "What's next ? -> Make sure everything is ok then push your new branch\n";
echo "Make sure to run migrations and clear cache once you pulled your code on production server\n\n";