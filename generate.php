<?php
require_once __DIR__ . '/src/WMFDomainList.php';
echo "[" . date('Y-m-d H:i:s') . "]: Generating domains.json..." . PHP_EOL;
( new WMFDomainList() )->writeJSON( 'public/domains.json' );