<?php

// Server strings (ADR-049): __('vani-pricing-campaign::texts.<key>').

return [
    'menu' => 'Pricing campaigns',
    'permission_view' => 'View pricing campaigns',
    'permission_manage' => 'Create and edit pricing campaigns',
    'price_list_name' => 'Pricing campaign: :name',
    'saved' => 'Campaign saved.',
    'deleted' => 'Campaign deleted.',
    'stale' => 'Someone else just changed this campaign — reload the page and save again.',
    'ends_after_starts' => 'The end time must be after the start time.',
    'hidden' => ' (hidden)',
    'unknown_product' => 'Product not found.',
    'items_required' => 'Add at least one product.',
    'percent_range' => 'Percent off must be between 1 and 99.',
    'recurrence_invalid' => 'Invalid weekly schedule: pick at least one day, times as HH:MM, at most 3 windows.',
    'not_below_list_price' => 'The discounted price is not below the list price (or the product has no list price).',
    'warning_quota_over_stock' => ':name: quota :quota is above sellable stock (:stock) — customers may be unable to order.',
    'warning_overlap' => 'Some products are in other campaigns running at the same time: :names (the higher priority price wins).',
    'block' => [
        'label' => 'Pricing campaign',
        'title' => 'Title (empty = campaign label)',
        'campaign' => 'Campaign',
        'auto' => 'Automatic — highest priority running campaign',
        'limit' => 'Number of products',
        'limit_help' => '1–24, default 8.',
    ],
    'storefront' => [
        'page_title' => 'Deals',
        'page_empty' => 'There are no deals right now.',
        'ends_in' => 'Ends in',
        'starts_in' => 'Starts in',
        'remaining' => ':count left',
        'remaining_short' => ':count left',
        'sold_out' => 'The deal price is sold out',
        'per_customer' => 'Up to :count items per customer at the deal price',
        'day' => 'd',
        'hour' => 'h',
        'minute' => 'm',
        'second' => 's',
    ],
];
