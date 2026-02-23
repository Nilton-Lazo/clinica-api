<?php

return [
    'idle_minutes' => (int) env('SESSION_IDLE_MINUTES', 15),
    'max_hours' => (int) env('SESSION_MAX_HOURS', 8),
];
