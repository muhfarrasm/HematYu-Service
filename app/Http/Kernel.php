protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\ForceJsonResponse::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
