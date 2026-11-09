<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Kolopay Banking API',
    description: 'API documentation for the Kolopay Core Banking platform.'
)]
#[OA\Server(
    url: '/',
    description: 'Kolopay API'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class OpenApi
{
}