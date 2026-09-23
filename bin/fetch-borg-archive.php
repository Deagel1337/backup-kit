#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application as SymfonyApplication;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../src');
$dotenv->load();

