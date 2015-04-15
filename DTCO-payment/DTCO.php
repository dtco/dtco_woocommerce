<?php

if(!function_exists('curl_init')) {
    throw new Exception('The DTCO client library requires the CURL PHP extension.');
}

require_once(dirname(__FILE__) . '/DTCO/DTCO.php');
