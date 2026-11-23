<?php

use Illuminate\Filesystem\Filesystem;

return json_decode(
    (new Filesystem)->get(resource_path('js/config/company.json')),
    true,
    flags: JSON_THROW_ON_ERROR,
);
