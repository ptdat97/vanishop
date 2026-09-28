<?php

/*
|--------------------------------------------------------------------------
| VaniShop modules
|--------------------------------------------------------------------------
|
| Danh sách module Core theo thứ tự phụ thuộc (upstream trước). Mỗi module
| có modules/<Name>/<Name>ServiceProvider.php. Xem docs/02-architecture.
|
*/

return [
    'Shared',
    'Tenancy',
    'Brand',
    'Channel',
    'Identity',
    'Extension',
    'Catalog',
    'Pricing',
    'Inventory',
    'Cart',
    'Storefront',
];
