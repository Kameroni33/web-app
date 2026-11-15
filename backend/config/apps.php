<?php

/*
|--------------------------------------------------------------------------
| App catalog
|--------------------------------------------------------------------------
|
| The apps inside Kam Apps, keyed by slug. The frontend keeps a
| matching entry (route and icon) in src/apps/registry.ts; a slug the
| frontend doesn't know is not shown. Which apps a user can open is
| decided by User::availableApps().
|
*/

return [

    'gifts' => [
        'name' => 'Gifts',
        'description' => 'Wish lists, and who is buying what.',
    ],

    'food' => [
        'name' => 'Food',
        'description' => 'Recipes and grocery lists',
    ],

    'money' => [
        'name' => 'Money',
        'description' => 'Budgets, bills and spending.',
    ],

];
