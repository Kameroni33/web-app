<?php

/*
|--------------------------------------------------------------------------
| App catalog
|--------------------------------------------------------------------------
|
| The tools inside Personal Tools, keyed by slug. The frontend keeps a
| matching entry (route and icon) in src/apps/registry.ts; a slug the
| frontend doesn't know is not shown. Which apps a user can open is
| decided by User::availableApps().
|
*/

return [

    'gifts' => [
        'name' => 'Gift Lists',
        'description' => 'Wish lists, and who is buying what.',
    ],

];
