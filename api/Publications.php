<?php namespace Bree7e\Cris\Api;

use Backend\Classes\Controller;

/**
 * Publications Back-end API Controller
 */
class Publications extends Controller
{
    public $implement = [
        'Mohsin.Rest.Behaviors.RestController'
    ];

    public $restConfig = 'config_rest.yaml';

}
