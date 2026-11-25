<?php

return [
    'type_label' => 'Abandoned cart reminder',
    'customer_fallback' => 'there',
    'default_subject' => 'You left items in your cart',
    'default_body' => "Hi {{ customer_name }},\n\nYour cart still has {{ item_count }} items, subtotal {{ subtotal }}. Stock is limited, finish your order here:\n{{ cart_url }}\n\nThanks for visiting.",
];
