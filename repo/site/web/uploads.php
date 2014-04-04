<?php //-->
/*
 * This file is part a custom application package.
 * (c) 2011-2012 Openovate Labs
 */
require dirname(__FILE__).'/../uploads.php';

/* Get Application
-------------------------------*/
print uploads()

/* Set Autoload
-------------------------------*/
->setLoader(NULL, '/library')

/* Start Filters
-------------------------------*/
->setFilters()

/* Trigger Init Event
-------------------------------*/
->trigger('init')

/* Get the Response
-------------------------------*/
->getResponse();