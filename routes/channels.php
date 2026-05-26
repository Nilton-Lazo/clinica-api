<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('system', function ($user) {
    return $user !== null;
});

Broadcast::channel('module.{module}', function ($user, string $module) {
    $allowed = ['ficheros', 'admision', 'emergencia', 'caja', 'facturacion'];

    return $user !== null && in_array($module, $allowed, true);
});
