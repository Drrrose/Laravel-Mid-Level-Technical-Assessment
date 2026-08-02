<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'OpenAPI 3.0 Documentation for Authentication APIs using Sanctum',
    title: 'Laravel Technical Assessment API'
)]
#[OA\Server(
    description: 'Main API Server',
    url: '/api'
)]
#[OA\SecurityScheme(
    bearerFormat: 'Bearer',
    in: 'header',
    name: 'Authorization',
    scheme: 'bearer',
    securityScheme: 'bearerAuth',
    type: 'http'
)]
abstract class Controller
{
    //
}
