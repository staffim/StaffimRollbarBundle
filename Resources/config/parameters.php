<?php

$container->setParameter('staffim_rollbar.rollbar.arguments', array(
    'host'         => '%staffim_rollbar.rollbar.environment%',
    'access_token' => '%staffim_rollbar.rollbar.access_token%',
    'max_errno'    => -1,
    'environment' => php_uname('n'),
));
