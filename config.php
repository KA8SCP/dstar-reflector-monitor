<?php
declare(strict_types=1);

const CACHE_TTL = 15;          // seconds; prevents excessive polling
const HTTP_TIMEOUT = 10;      // seconds
const REFRESH_SECONDS = 15;   // browser live refresh
const HISTORY_RETENTION_DAYS = 90; // SQLite observation/event retention
const MAX_LAST_HEARD = 25;
const MAX_USERS = 50;
const MAX_PEERS = 25;

$REFLECTORS = [
    'REF038' => [
        'name' => 'REF038',
        'type' => 'DPLUS',
        'host' => 'ref038.dstargateway.org',
        'urls' => [
            'https://ref038.dstargateway.org/',
            'http://ref038.dstargateway.org/',
        ],
    ],
    'REF039' => [
        'name' => 'REF039',
        'type' => 'DPLUS',
        'host' => 'ref039.dstargateway.org',
        'urls' => [
            'https://ref039.dstargateway.org/',
            'http://ref039.dstargateway.org/',
        ],
    ],
    'REF040' => [
        'name' => 'REF040',
        'type' => 'DPLUS',
        'host' => 'ref040.dstargateway.org',
        'urls' => [
            'https://ref040.dstargateway.org/',
            'http://ref040.dstargateway.org/',
        ],
    ],
    'REF049' => [
        'name' => 'REF049',
        'type' => 'DPLUS',
        'host' => 'ref049.dstargateway.org',
        'urls' => [
            'https://ref049.dstargateway.org/',
            'http://ref049.dstargateway.org/',
        ],
    ],
    'DCS016' => [
        'name' => 'DCS016',
        'type' => 'DCS',
        'host' => 'dcs016.xreflector.net',
        'urls' => [
            'http://dcs016.xreflector.net/dcs/',
            'http://dcs016.xreflector.net/',
        ],
    ],
    'XLX038' => [
        'name' => 'XLX038',
        'type' => 'XLXD',
        'host' => 'xlx038.dyndns.org',
        'urls' => [
            'https://xlx038.dyndns.org/',
            'http://xlx038.dyndns.org/',
        ],
    ],
    'XLX049' => [
        'name' => 'XLX049',
        'type' => 'XLXD',
        'host' => 'xlx049.dyndns.org',
        'urls' => [
            'https://xlx049.dyndns.org/',
            'http://xlx049.dyndns.org/',
        ],
    ],
    'XLX139' => [
        'name' => 'XLX139',
        'type' => 'XLXD',
        'host' => 'xlx139.dyndns.org',
        'urls' => [
            'https://xlx139.dyndns.org/',
            'http://xlx139.dyndns.org/',
        ],
    ],
    'XLX351' => [
        'name' => 'XLX351',
        'type' => 'XLXD',
        'host' => 'xlx351.dyndns.org',
        'urls' => [
            'https://xlx351.dyndns.org/',
            'http://xlx351.dyndns.org/',
        ],
    ],
    'XLX978' => [
        'name' => 'XLX978',
        'type' => 'XLXD',
        'host' => 'xlx978.dyndns.org',
        'urls' => [
            'https://xlx978.dyndns.org/',
            'http://xlx978.dyndns.org/',
        ],
    ],
];
