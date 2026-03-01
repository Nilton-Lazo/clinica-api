<?php

return [
    'idle_minutes' => (int) env('SESSION_IDLE_MINUTES', 15),
    // Permite decimales para pruebas (ej: 0.02 ~= 72s).
    'max_hours' => (float) env('SESSION_MAX_HOURS', 8),
];
