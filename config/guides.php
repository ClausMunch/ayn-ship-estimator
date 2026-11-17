<?php

return [
    // Null placements produce no markup or whitespace. Set a placement to an
    // array to show a labelled creative. Keep provider scripts in the component.
    'placements' => [
        'index_top' => null,
        'article_top' => null,
        'article_bottom' => null,
        // Example:
        // 'article_bottom' => [
        //     'type' => 'sponsor', // sponsor or advertisement
        //     'name' => 'Partner name',
        //     'description' => 'A short, clearly disclosed partner message.',
        //     'url' => 'https://partner.example',
        //     'cta' => 'Visit partner',
        // ],
    ],
];
