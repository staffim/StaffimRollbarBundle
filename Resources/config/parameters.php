<?php

$container->setParameter('staffim_rollbar.rollbar.arguments', array(
    'host' => php_uname('n'),
    'access_token' => '%staffim_rollbar.rollbar.access_token%',
    'max_errno' => -1,
    'environment' => '%staffim_rollbar.rollbar.environment%',
));
