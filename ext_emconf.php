<?php

########################################################################
# Extension Manager/Repository config file for ext "ecorss".
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Ecodev : feeds services (RSS / ATOM)',
    'description' => 'Generate easily RSS / ATOM feeds based on the latest content of any tables in the database. Can deal with flexform content and multilingual / multidomain websites.',
    'category' => 'fe',
    'version' => '1.2.0',
    'dependencies' => 'div2007',
    'state' => 'stable',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger, Fabien Udriot',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => array(
        'depends' => array(
            'php' => '7.4.0-8.4.99',
            'typo3' => '11.5.0-12.4.99',
            'div2007' => '1.17.0-0.0.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
            'typo3db_legacy' => '1.0.0-1.1.99',
        ),
    ),
);

