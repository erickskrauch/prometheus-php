<?php
declare(strict_types=1);

use Ely\CS\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__);

return Config::create()
    ->setFinder($finder);
