<?php

return [
    'delivery_days' => (int) env('SHIPMENT_DELIVERY_DAYS', 14),
    'confirmation_follow_up_days' => (int) env('SHIPMENT_CONFIRMATION_FOLLOW_UP_DAYS', 2),
];
