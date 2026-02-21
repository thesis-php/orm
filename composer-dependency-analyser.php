<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return new Configuration()
    ->ignoreErrorsOnPackage('thesis/transaction', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]);
