<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invitación digital
    |--------------------------------------------------------------------------
    |
    | El arte animado de la invitación vive en Canva (incluye la música y las
    | transiciones que no se pueden exportar). Desde la puerta de apertura
    | (/i/{token}) se redirige a esta URL con el nombre de los invitados ya
    | mostrado. Se puede sobrescribir desde el panel de administración
    | (Configuración → Invitación digital) y por variable de entorno.
    |
    */

    'canva_url' => env('WEDDING_CANVA_URL', 'https://rrnb.my.canva.site/jose-eli-141126'),

    /*
    |--------------------------------------------------------------------------
    | Nombres de los novios
    |--------------------------------------------------------------------------
    |
    | Se muestran en la puerta de apertura de la invitación.
    |
    */

    'couple_names' => env('WEDDING_COUPLE_NAMES', 'José Rodríguez & Elizabeth Mendoza'),

];
