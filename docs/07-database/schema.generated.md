# Từ điển dữ liệu (sinh tự động)

> Sinh bởi `php artisan vani:docs:schema` từ schema mysql sau `migrate:fresh` — **không sửa tay**. CI chạy `--check`. Ý nghĩa nghiệp vụ của bảng: [database](database.md). Bảng plugin (`plg_*`) mô tả trong tài liệu của plugin.

| Bảng | Số cột |
|---|---|
| [`alert_states`](#alert_states) | 12 |
| [`attribute_translations`](#attribute_translations) | 4 |
| [`attribute_value_translations`](#attribute_value_translations) | 4 |
| [`attribute_values`](#attribute_values) | 6 |
| [`attributes`](#attributes) | 9 |
| [`audit_logs`](#audit_logs) | 11 |
| [`backup_drills`](#backup_drills) | 8 |
| [`brands`](#brands) | 14 |
| [`cache`](#cache) | 3 |
| [`cache_locks`](#cache_locks) | 3 |
| [`campaign_price_lists`](#campaign_price_lists) | 5 |
| [`campaigns`](#campaigns) | 11 |
| [`cart_lines`](#cart_lines) | 10 |
| [`carts`](#carts) | 12 |
| [`categories`](#categories) | 10 |
| [`category_style`](#category_style) | 3 |
| [`category_translations`](#category_translations) | 8 |
| [`collection_style`](#collection_style) | 3 |
| [`collection_translations`](#collection_translations) | 6 |
| [`collections`](#collections) | 6 |
| [`color_translations`](#color_translations) | 4 |
| [`colors`](#colors) | 7 |
| [`customer_addresses`](#customer_addresses) | 13 |
| [`customer_consent_events`](#customer_consent_events) | 9 |
| [`customer_consents`](#customer_consents) | 9 |
| [`customer_groups`](#customer_groups) | 7 |
| [`customer_identities`](#customer_identities) | 7 |
| [`customer_otps`](#customer_otps) | 10 |
| [`customer_tags`](#customer_tags) | 4 |
| [`customer_tokens`](#customer_tokens) | 7 |
| [`customers`](#customers) | 24 |
| [`external_references`](#external_references) | 7 |
| [`failed_jobs`](#failed_jobs) | 7 |
| [`idempotency_keys`](#idempotency_keys) | 10 |
| [`integration_client_keys`](#integration_client_keys) | 8 |
| [`integration_clients`](#integration_clients) | 9 |
| [`integration_events`](#integration_events) | 9 |
| [`integration_inbox`](#integration_inbox) | 13 |
| [`integration_mappings`](#integration_mappings) | 7 |
| [`integration_outbox`](#integration_outbox) | 18 |
| [`integration_reconciliations`](#integration_reconciliations) | 10 |
| [`integration_webhook_subscriptions`](#integration_webhook_subscriptions) | 9 |
| [`inventory_reconciliation_lines`](#inventory_reconciliation_lines) | 12 |
| [`inventory_reconciliations`](#inventory_reconciliations) | 10 |
| [`job_batches`](#job_batches) | 10 |
| [`jobs`](#jobs) | 7 |
| [`legal_entities`](#legal_entities) | 8 |
| [`link_type_translations`](#link_type_translations) | 4 |
| [`link_types`](#link_types) | 6 |
| [`locations`](#locations) | 15 |
| [`media`](#media) | 12 |
| [`media_folders`](#media_folders) | 4 |
| [`mediables`](#mediables) | 9 |
| [`migrations`](#migrations) | 3 |
| [`notification_logs`](#notification_logs) | 18 |
| [`notification_templates`](#notification_templates) | 11 |
| [`number_sequences`](#number_sequences) | 6 |
| [`order_adjustments`](#order_adjustments) | 10 |
| [`order_events`](#order_events) | 12 |
| [`order_lines`](#order_lines) | 23 |
| [`orders`](#orders) | 32 |
| [`password_reset_tokens`](#password_reset_tokens) | 3 |
| [`payment_reconciliation_lines`](#payment_reconciliation_lines) | 10 |
| [`payment_reconciliations`](#payment_reconciliations) | 7 |
| [`payment_transactions`](#payment_transactions) | 10 |
| [`payments`](#payments) | 15 |
| [`plugin_seeders`](#plugin_seeders) | 3 |
| [`plugins`](#plugins) | 7 |
| [`price_history`](#price_history) | 11 |
| [`price_lists`](#price_lists) | 13 |
| [`prices`](#prices) | 8 |
| [`promotion_rules`](#promotion_rules) | 6 |
| [`promotion_usages`](#promotion_usages) | 10 |
| [`promotions`](#promotions) | 18 |
| [`pulse_aggregates`](#pulse_aggregates) | 9 |
| [`pulse_entries`](#pulse_entries) | 6 |
| [`pulse_values`](#pulse_values) | 6 |
| [`refunds`](#refunds) | 12 |
| [`return_attachments`](#return_attachments) | 8 |
| [`return_events`](#return_events) | 9 |
| [`return_lines`](#return_lines) | 11 |
| [`return_reasons`](#return_reasons) | 8 |
| [`return_requests`](#return_requests) | 18 |
| [`role_permissions`](#role_permissions) | 2 |
| [`roles`](#roles) | 6 |
| [`sessions`](#sessions) | 6 |
| [`settings`](#settings) | 7 |
| [`shipment_events`](#shipment_events) | 9 |
| [`shipment_lines`](#shipment_lines) | 7 |
| [`shipments`](#shipments) | 18 |
| [`sizes`](#sizes) | 6 |
| [`staff_invite_tokens`](#staff_invite_tokens) | 3 |
| [`staff_password_reset_tokens`](#staff_password_reset_tokens) | 3 |
| [`staff_role_assignments`](#staff_role_assignments) | 7 |
| [`staff_users`](#staff_users) | 10 |
| [`stock_levels`](#stock_levels) | 9 |
| [`stock_movements`](#stock_movements) | 14 |
| [`stock_reservations`](#stock_reservations) | 10 |
| [`style_attribute_values`](#style_attribute_values) | 6 |
| [`style_colors`](#style_colors) | 6 |
| [`style_link_group_items`](#style_link_group_items) | 4 |
| [`style_link_groups`](#style_link_groups) | 5 |
| [`style_translations`](#style_translations) | 9 |
| [`style_video`](#style_video) | 3 |
| [`style_view_stats`](#style_view_stats) | 3 |
| [`styles`](#styles) | 14 |
| [`users`](#users) | 8 |
| [`variants`](#variants) | 12 |
| [`videos`](#videos) | 7 |
| [`vouchers`](#vouchers) | 9 |

## alert_states

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `key` | varchar(96) |  |  |
| `severity` | varchar(16) |  |  |
| `status` | varchar(16) |  |  |
| `title` | varchar(255) |  |  |
| `detail` | text |  |  |
| `fired_at` | timestamp |  |  |
| `last_notified_at` | timestamp | có |  |
| `notify_count` | int unsigned |  | `0` |
| `resolved_at` | timestamp | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (key); index (status); PK (id)

## attribute_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `attribute_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `name` | varchar(255) |  |  |

Index: unique (attribute_id, locale); PK (id)

Khoá ngoại: attribute_id → attributes(id) on delete cascade

## attribute_value_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `attribute_value_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `label` | varchar(255) |  |  |

Index: unique (attribute_value_id, locale); PK (id)

Khoá ngoại: attribute_value_id → attribute_values(id) on delete cascade

## attribute_values

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `attribute_id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `position` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (attribute_id, code); PK (id)

Khoá ngoại: attribute_id → attributes(id) on delete cascade

## attributes

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `kind` | varchar(16) |  |  |
| `input_type` | varchar(16) |  |  |
| `is_filterable` | tinyint(1) |  | `0` |
| `position` | int unsigned |  | `0` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); PK (id)

## audit_logs

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `actor_type` | varchar(16) |  |  |
| `actor_id` | bigint unsigned | có |  |
| `action` | varchar(128) |  |  |
| `subject_type` | varchar(64) | có |  |
| `subject_id` | varchar(64) | có |  |
| `changes` | json | có |  |
| `ip_address` | varchar(45) | có |  |
| `user_agent` | varchar(512) | có |  |
| `correlation_id` | varchar(64) | có |  |
| `created_at` | datetime(6) |  |  |

Index: index (actor_type, actor_id); index (subject_type, subject_id); PK (id)

## backup_drills

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `disk` | varchar(64) |  |  |
| `backup_path` | varchar(255) |  |  |
| `backup_created_at` | timestamp | có |  |
| `status` | varchar(16) |  |  |
| `duration_ms` | int unsigned |  |  |
| `details` | json |  |  |
| `created_at` | timestamp |  |  |

Index: index (status, created_at); PK (id)

## brands

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `slug` | varchar(64) |  |  |
| `name` | varchar(255) |  |  |
| `description` | text | có |  |
| `logo_path` | varchar(512) | có |  |
| `logo_media_id` | bigint unsigned | có |  |
| `meta_title` | varchar(255) | có |  |
| `meta_description` | varchar(512) | có |  |
| `status` | varchar(16) |  | `active` |
| `position` | int unsigned |  | `0` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); unique (slug); index (status, position); PK (id)

## cache

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `key` | varchar(255) |  |  |
| `value` | mediumtext |  |  |
| `expiration` | bigint |  |  |

Index: index (expiration); PK (key)

## cache_locks

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `key` | varchar(255) |  |  |
| `owner` | varchar(255) |  |  |
| `expiration` | bigint |  |  |

Index: index (expiration); PK (key)

## campaign_price_lists

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `campaign_id` | bigint unsigned |  |  |
| `price_list_id` | bigint unsigned |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (campaign_id); unique (price_list_id); PK (id)

Khoá ngoại: campaign_id → campaigns(id) on delete cascade

## campaigns

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `name` | varchar(255) |  |  |
| `description` | varchar(500) | có |  |
| `starts_at` | timestamp |  |  |
| `ends_at` | timestamp |  |  |
| `status` | varchar(16) |  | `draft` |
| `stopped_at` | timestamp | có |  |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); index (status, starts_at); PK (id)

## cart_lines

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `cart_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `options_hash` | char(16) |  | `` |
| `quantity` | int unsigned |  |  |
| `selected` | tinyint(1) |  | `1` |
| `unit_price_snapshot` | bigint | có |  |
| `meta` | json | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (cart_id, variant_id, options_hash); index (variant_id); PK (id)

Khoá ngoại: cart_id → carts(id) on delete cascade; variant_id → variants(id)

## carts

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `token_hash` | char(64) |  |  |
| `customer_id` | bigint unsigned | có |  |
| `currency_code` | char(3) |  |  |
| `status` | varchar(16) |  | `active` |
| `meta` | json | có |  |
| `lock_version` | int unsigned |  | `0` |
| `last_activity_at` | timestamp |  |  |
| `abandoned_notified_at` | timestamp | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (customer_id); unique (public_id); index (status, last_activity_at); PK (id)

## categories

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `parent_id` | bigint unsigned | có |  |
| `slug` | varchar(128) |  |  |
| `path` | varchar(512) |  |  |
| `depth` | tinyint unsigned |  |  |
| `position` | int unsigned |  | `0` |
| `status` | varchar(16) |  | `active` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (parent_id, position); index (path); unique (slug); PK (id)

Khoá ngoại: parent_id → categories(id)

## category_style

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `category_id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `position` | int unsigned |  | `0` |

Index: index (style_id); PK (category_id, style_id)

Khoá ngoại: category_id → categories(id) on delete cascade; style_id → styles(id) on delete cascade

## category_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `category_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `slug` | varchar(160) | có |  |
| `name` | varchar(255) |  |  |
| `description` | text | có |  |
| `meta_title` | varchar(255) | có |  |
| `meta_description` | varchar(512) | có |  |

Index: unique (category_id, locale); unique (locale, slug); PK (id)

Khoá ngoại: category_id → categories(id) on delete cascade

## collection_style

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `collection_id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `position` | int unsigned |  | `0` |

Index: index (style_id); PK (collection_id, style_id)

Khoá ngoại: collection_id → collections(id) on delete cascade; style_id → styles(id) on delete cascade

## collection_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `collection_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `slug` | varchar(160) | có |  |
| `name` | varchar(255) |  |  |
| `description` | text | có |  |

Index: unique (collection_id, locale); unique (locale, slug); PK (id)

Khoá ngoại: collection_id → collections(id) on delete cascade

## collections

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `slug` | varchar(128) |  |  |
| `status` | varchar(16) |  | `active` |
| `position` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (slug); PK (id)

## color_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `color_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `name` | varchar(255) |  |  |

Index: unique (color_id, locale); PK (id)

Khoá ngoại: color_id → colors(id) on delete cascade

## colors

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `color_family` | varchar(16) |  |  |
| `hex` | char(7) | có |  |
| `position` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); index (color_family); PK (id)

## customer_addresses

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned |  |  |
| `label` | varchar(32) | có |  |
| `full_name` | varchar(255) |  |  |
| `phone` | varchar(16) |  |  |
| `province_code` | varchar(8) |  |  |
| `province_name` | varchar(64) |  |  |
| `ward_code` | varchar(8) |  |  |
| `ward_name` | varchar(64) |  |  |
| `street_line` | varchar(255) |  |  |
| `is_default` | tinyint(1) |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (customer_id); PK (id)

Khoá ngoại: customer_id → customers(id) on delete cascade

## customer_consent_events

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned |  |  |
| `channel` | varchar(16) |  |  |
| `purpose` | varchar(32) |  |  |
| `action` | varchar(16) |  |  |
| `source` | varchar(64) |  |  |
| `ip` | varchar(45) | có |  |
| `correlation_id` | varchar(64) | có |  |
| `created_at` | timestamp |  |  |

Index: index (customer_id); PK (id)

## customer_consents

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned |  |  |
| `channel` | varchar(16) |  |  |
| `purpose` | varchar(32) |  |  |
| `granted_at` | timestamp | có |  |
| `revoked_at` | timestamp | có |  |
| `source` | varchar(64) |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (customer_id, channel, purpose); PK (id)

Khoá ngoại: customer_id → customers(id) on delete cascade

## customer_groups

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `name` | varchar(100) |  |  |
| `description` | varchar(255) | có |  |
| `position` | smallint unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); PK (id)

## customer_identities

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned |  |  |
| `provider` | varchar(64) |  |  |
| `subject` | varchar(191) |  |  |
| `last_used_at` | timestamp | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (customer_id); unique (provider, subject); PK (id)

Khoá ngoại: customer_id → customers(id) on delete cascade

## customer_otps

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `phone` | varchar(16) |  |  |
| `purpose` | varchar(32) |  |  |
| `code_hash` | varchar(64) |  |  |
| `channel` | varchar(16) |  |  |
| `attempts` | tinyint unsigned |  | `0` |
| `expires_at` | timestamp |  |  |
| `consumed_at` | timestamp | có |  |
| `ip` | varchar(45) | có |  |
| `created_at` | timestamp |  |  |

Index: index (phone, purpose, created_at); PK (id)

## customer_tags

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned |  |  |
| `tag` | varchar(40) |  |  |
| `created_at` | timestamp | có |  |

Index: unique (customer_id, tag); index (tag); PK (id)

Khoá ngoại: customer_id → customers(id) on delete cascade

## customer_tokens

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned |  |  |
| `token_hash` | char(64) |  |  |
| `name` | varchar(64) | có |  |
| `last_used_at` | timestamp | có |  |
| `expires_at` | timestamp |  |  |
| `created_at` | timestamp |  |  |

Index: index (customer_id); unique (token_hash); PK (id)

Khoá ngoại: customer_id → customers(id) on delete cascade

## customers

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `phone` | varchar(16) | có |  |
| `email` | varchar(255) | có |  |
| `full_name` | varchar(255) | có |  |
| `birth_date` | date | có |  |
| `gender` | varchar(8) | có |  |
| `preferred_locale` | varchar(8) | có |  |
| `status` | varchar(16) |  | `active` |
| `customer_group_id` | bigint unsigned | có |  |
| `registered_at` | timestamp | có |  |
| `phone_verified_at` | timestamp | có |  |
| `password` | varchar(255) | có |  |
| `merged_into_id` | bigint unsigned | có |  |
| `meta` | json | có |  |
| `last_login_at` | timestamp | có |  |
| `orders_count` | int unsigned |  | `0` |
| `total_spent` | bigint |  | `0` |
| `first_order_at` | timestamp | có |  |
| `last_order_at` | timestamp | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |
| `phone_active` | varchar(16) | có |  |
| `email_normalized` | varchar(255) | có |  |

Index: index (customer_group_id); unique (email_normalized); index (merged_into_id); unique (phone_active); unique (public_id); index (status, registered_at); PK (id)

Khoá ngoại: customer_group_id → customer_groups(id); merged_into_id → customers(id) on delete set null

## external_references

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `system` | varchar(64) |  |  |
| `entity_type` | varchar(32) |  |  |
| `internal_id` | varchar(64) |  |  |
| `external_id` | varchar(128) |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (system, entity_type, external_id); unique (system, entity_type, internal_id); PK (id)

## failed_jobs

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `uuid` | varchar(255) |  |  |
| `connection` | varchar(255) |  |  |
| `queue` | varchar(255) |  |  |
| `payload` | longtext |  |  |
| `exception` | longtext |  |  |
| `failed_at` | timestamp |  | `CURRENT_TIMESTAMP` |

Index: index (connection, queue, failed_at); unique (uuid); PK (id)

## idempotency_keys

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `scope` | varchar(64) |  |  |
| `key` | varchar(128) |  |  |
| `request_hash` | char(64) |  |  |
| `status` | varchar(16) |  |  |
| `response_status` | smallint unsigned | có |  |
| `response_body` | json | có |  |
| `expires_at` | timestamp |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (expires_at); unique (scope, key); PK (id)

## integration_client_keys

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `client_id` | bigint unsigned |  |  |
| `key_id` | varchar(40) |  |  |
| `secret` | text |  |  |
| `expires_at` | timestamp | có |  |
| `revoked_at` | timestamp | có |  |
| `last_used_at` | timestamp | có |  |
| `created_at` | timestamp | có |  |

Index: index (client_id); unique (key_id); PK (id)

Khoá ngoại: client_id → integration_clients(id) on delete cascade

## integration_clients

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `name` | varchar(255) |  |  |
| `status` | varchar(16) |  | `active` |
| `scopes` | json |  |  |
| `ip_allowlist` | json | có |  |
| `rate_limit` | int unsigned |  | `600` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); PK (id)

## integration_events

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `event_id` | char(36) |  |  |
| `event_type` | varchar(64) |  |  |
| `schema_version` | varchar(16) |  |  |
| `aggregate_type` | varchar(32) |  |  |
| `aggregate_id` | varchar(64) |  |  |
| `payload` | json |  |  |
| `correlation_id` | varchar(64) | có |  |
| `occurred_at` | timestamp |  |  |

Index: unique (event_id); index (event_type, id); PK (id)

## integration_inbox

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `system` | varchar(64) |  |  |
| `external_event_id` | varchar(128) |  |  |
| `message_type` | varchar(64) |  |  |
| `payload` | json |  |  |
| `status` | varchar(16) |  | `received` |
| `attempts` | smallint unsigned |  | `0` |
| `next_attempt_at` | timestamp | có |  |
| `locked_at` | timestamp | có |  |
| `correlation_id` | varchar(64) | có |  |
| `last_error` | text | có |  |
| `received_at` | timestamp |  |  |
| `processed_at` | timestamp | có |  |

Index: index (status, next_attempt_at); unique (system, external_event_id); PK (id)

## integration_mappings

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `system` | varchar(64) |  |  |
| `type` | varchar(32) |  |  |
| `internal_value` | varchar(128) |  |  |
| `external_value` | varchar(128) |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (system, type, internal_value); PK (id)

## integration_outbox

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `message_id` | char(36) |  |  |
| `event_id` | char(36) | có |  |
| `target` | varchar(96) |  |  |
| `message_type` | varchar(64) |  |  |
| `schema_version` | varchar(16) |  |  |
| `aggregate_type` | varchar(32) |  |  |
| `aggregate_id` | varchar(64) |  |  |
| `payload` | json |  |  |
| `status` | varchar(16) |  | `pending` |
| `attempts` | smallint unsigned |  | `0` |
| `next_attempt_at` | timestamp | có |  |
| `locked_at` | timestamp | có |  |
| `correlation_id` | varchar(64) | có |  |
| `last_error` | text | có |  |
| `external_id` | varchar(128) | có |  |
| `created_at` | timestamp | có |  |
| `sent_at` | timestamp | có |  |

Index: index (target, aggregate_type, aggregate_id, id); index (event_id); unique (message_id); index (status, next_attempt_at); PK (id)

## integration_reconciliations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `type` | varchar(32) |  |  |
| `window_from` | timestamp | có |  |
| `window_to` | timestamp | có |  |
| `checked` | int unsigned |  | `0` |
| `discrepancies` | int unsigned |  | `0` |
| `repaired` | int unsigned |  | `0` |
| `details` | json | có |  |
| `started_at` | timestamp |  |  |
| `finished_at` | timestamp | có |  |

Index: index (type, started_at); PK (id)

## integration_webhook_subscriptions

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `client_id` | bigint unsigned |  |  |
| `url` | varchar(500) |  |  |
| `event_types` | json |  |  |
| `secret` | text |  |  |
| `status` | varchar(16) |  | `active` |
| `failing_since` | timestamp | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (client_id); PK (id)

Khoá ngoại: client_id → integration_clients(id) on delete cascade

## inventory_reconciliation_lines

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `reconciliation_id` | bigint unsigned |  |  |
| `location_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `classification` | varchar(32) |  |  |
| `expected` | int |  |  |
| `actual` | int |  |  |
| `difference` | int |  |  |
| `note` | varchar(255) | có |  |
| `detected_at` | timestamp |  |  |
| `resolved_at` | timestamp | có |  |
| `resolution` | varchar(32) | có |  |

Index: unique (reconciliation_id, location_id, variant_id, classification); index (classification, resolved_at); index (location_id, variant_id); index (variant_id); PK (id)

Khoá ngoại: location_id → locations(id); reconciliation_id → inventory_reconciliations(id) on delete cascade; variant_id → variants(id)

## inventory_reconciliations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `source` | varchar(64) |  |  |
| `window_from` | timestamp | có |  |
| `window_to` | timestamp | có |  |
| `checked` | int unsigned |  | `0` |
| `discrepancies` | int unsigned |  | `0` |
| `repaired` | int unsigned |  | `0` |
| `details` | json | có |  |
| `started_at` | timestamp |  |  |
| `finished_at` | timestamp | có |  |

Index: index (source, started_at); PK (id)

## job_batches

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | varchar(255) |  |  |
| `name` | varchar(255) |  |  |
| `total_jobs` | int |  |  |
| `pending_jobs` | int |  |  |
| `failed_jobs` | int |  |  |
| `failed_job_ids` | longtext |  |  |
| `options` | mediumtext | có |  |
| `cancelled_at` | int | có |  |
| `created_at` | int |  |  |
| `finished_at` | int | có |  |

Index: PK (id)

## jobs

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `queue` | varchar(255) |  |  |
| `payload` | longtext |  |  |
| `attempts` | smallint unsigned |  |  |
| `reserved_at` | int unsigned | có |  |
| `available_at` | int unsigned |  |  |
| `created_at` | int unsigned |  |  |

Index: index (queue); PK (id)

## legal_entities

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `name` | varchar(255) |  |  |
| `tax_code` | varchar(20) |  |  |
| `registered_address` | varchar(255) | có |  |
| `status` | varchar(16) |  | `active` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); unique (tax_code); PK (id)

## link_type_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `link_type_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `name` | varchar(255) |  |  |

Index: unique (link_type_id, locale); PK (id)

Khoá ngoại: link_type_id → link_types(id) on delete cascade

## link_types

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `is_active` | tinyint(1) |  | `1` |
| `position` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); PK (id)

## locations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `name` | varchar(255) |  |  |
| `type` | varchar(16) |  |  |
| `address` | varchar(255) | có |  |
| `province_code` | varchar(16) | có |  |
| `ships_online_orders` | tinyint(1) |  | `1` |
| `allows_pickup` | tinyint(1) |  | `0` |
| `accepts_returns` | tinyint(1) |  | `0` |
| `stock_authority` | varchar(64) |  | `vanishop` |
| `priority` | int |  | `0` |
| `status` | varchar(16) |  | `active` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); PK (id)

## media

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `disk` | varchar(32) |  |  |
| `path` | varchar(512) |  |  |
| `folder` | varchar(191) |  | `` |
| `original_name` | varchar(255) |  |  |
| `mime_type` | varchar(64) |  |  |
| `size_bytes` | bigint unsigned |  |  |
| `width` | int unsigned | có |  |
| `height` | int unsigned | có |  |
| `checksum` | char(64) |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (checksum); index (folder); PK (id)

## media_folders

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `path` | varchar(191) |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (path); PK (id)

## mediables

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `media_id` | bigint unsigned |  |  |
| `mediable_type` | varchar(64) |  |  |
| `mediable_id` | bigint unsigned |  |  |
| `role` | varchar(32) |  |  |
| `position` | int unsigned |  | `0` |
| `alt` | varchar(255) | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (media_id); index (mediable_type, mediable_id, role, position); PK (id)

Khoá ngoại: media_id → media(id)

## migrations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | int unsigned |  |  |
| `migration` | varchar(255) |  |  |
| `batch` | int |  |  |

Index: PK (id)

## notification_logs

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `idempotency_key` | varchar(160) |  |  |
| `type` | varchar(64) |  |  |
| `category` | varchar(16) |  |  |
| `channel` | varchar(32) |  |  |
| `template_id` | bigint unsigned | có |  |
| `customer_id` | bigint unsigned | có |  |
| `recipient` | varchar(190) |  |  |
| `subject` | varchar(255) | có |  |
| `body` | text | có |  |
| `meta` | json | có |  |
| `status` | varchar(16) |  |  |
| `attempts` | smallint unsigned |  | `0` |
| `provider_message_id` | varchar(128) | có |  |
| `error` | varchar(500) | có |  |
| `correlation_id` | varchar(64) | có |  |
| `created_at` | timestamp |  |  |
| `sent_at` | timestamp | có |  |

Index: index (customer_id); unique (idempotency_key); index (status, created_at); index (type, created_at); PK (id)

## notification_templates

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `type` | varchar(64) |  |  |
| `channel` | varchar(32) |  |  |
| `locale` | varchar(8) |  | `vi` |
| `subject` | varchar(255) | có |  |
| `body` | text | có |  |
| `meta` | json | có |  |
| `active` | tinyint(1) |  | `1` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (type, channel, locale); PK (id)

## number_sequences

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `scope` | varchar(64) |  |  |
| `period` | varchar(16) |  |  |
| `last_value` | bigint unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (scope, period); PK (id)

## order_adjustments

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `order_id` | bigint unsigned |  |  |
| `type` | varchar(32) |  |  |
| `source` | varchar(64) |  |  |
| `code` | varchar(64) | có |  |
| `label` | varchar(255) |  |  |
| `amount` | bigint |  |  |
| `meta` | json | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (order_id); PK (id)

Khoá ngoại: order_id → orders(id) on delete cascade

## order_events

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `order_id` | bigint unsigned |  |  |
| `type` | varchar(64) |  |  |
| `from_status` | varchar(24) | có |  |
| `to_status` | varchar(24) | có |  |
| `reason` | varchar(255) | có |  |
| `actor_type` | varchar(32) | có |  |
| `actor_id` | bigint unsigned | có |  |
| `source` | varchar(64) |  |  |
| `correlation_id` | varchar(64) | có |  |
| `data` | json | có |  |
| `created_at` | timestamp(6) |  |  |

Index: index (order_id, id); PK (id)

Khoá ngoại: order_id → orders(id) on delete cascade

## order_lines

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `order_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `sku` | varchar(64) |  |  |
| `product_name` | varchar(255) |  |  |
| `brand_id` | bigint unsigned | có |  |
| `brand_name` | varchar(255) | có |  |
| `color_name` | varchar(255) | có |  |
| `size_code` | varchar(32) |  |  |
| `image_url` | varchar(255) | có |  |
| `quantity` | int unsigned |  |  |
| `cancelled_quantity` | int unsigned |  | `0` |
| `unit_amount` | bigint |  |  |
| `compare_at_amount` | bigint | có |  |
| `price_list_code` | varchar(64) | có |  |
| `subtotal_amount` | bigint |  |  |
| `discount_amount` | bigint |  |  |
| `total_amount` | bigint |  |  |
| `tax_rate_bp` | int unsigned |  |  |
| `tax_amount` | bigint |  |  |
| `meta` | json | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (brand_id); index (order_id); index (price_list_code); index (variant_id); PK (id)

Khoá ngoại: order_id → orders(id) on delete cascade

## orders

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `number` | varchar(32) |  |  |
| `source` | varchar(16) |  | `web` |
| `locale` | varchar(8) | có |  |
| `customer_id` | bigint unsigned | có |  |
| `currency_code` | char(3) |  |  |
| `order_status` | varchar(16) |  |  |
| `payment_status` | varchar(24) |  |  |
| `fulfillment_status` | varchar(24) |  |  |
| `delivery_issue` | varchar(24) | có |  |
| `return_status` | varchar(24) |  | `none` |
| `payment_method` | varchar(64) |  |  |
| `subtotal_amount` | bigint |  |  |
| `discount_amount` | bigint |  |  |
| `shipping_amount` | bigint |  |  |
| `tax_amount` | bigint |  |  |
| `total_amount` | bigint |  |  |
| `customer_snapshot` | json |  |  |
| `customer_phone` | varchar(16) | có |  |
| `access_token_hash` | char(64) | có |  |
| `shipping_address` | json |  |  |
| `shipping_method` | json |  |  |
| `note` | varchar(500) | có |  |
| `reservation_key` | varchar(64) |  |  |
| `source_cart_id` | varchar(26) | có |  |
| `parent_order_id` | bigint unsigned | có |  |
| `meta` | json | có |  |
| `lock_version` | int unsigned |  | `0` |
| `placed_at` | timestamp |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (customer_id); index (customer_phone); unique (number); index (order_status); index (parent_order_id); index (placed_at); unique (public_id); index (source_cart_id); index (source, placed_at); index (updated_at, id); PK (id)

Khoá ngoại: parent_order_id → orders(id)

## password_reset_tokens

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `email` | varchar(255) |  |  |
| `token` | varchar(255) |  |  |
| `created_at` | timestamp | có |  |

Index: PK (email)

## payment_reconciliation_lines

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `reconciliation_id` | bigint unsigned |  |  |
| `payment_id` | bigint unsigned |  |  |
| `gateway_code` | varchar(64) |  |  |
| `issue` | varchar(32) |  |  |
| `expected` | varchar(64) | có |  |
| `actual` | varchar(64) | có |  |
| `detected_at` | timestamp |  |  |
| `resolved_at` | timestamp | có |  |
| `resolution` | varchar(255) | có |  |

Index: index (payment_id, issue); index (reconciliation_id); PK (id)

Khoá ngoại: payment_id → payments(id); reconciliation_id → payment_reconciliations(id) on delete cascade

## payment_reconciliations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `window_from` | timestamp |  |  |
| `checked` | int unsigned |  | `0` |
| `skipped` | int unsigned |  | `0` |
| `issues` | int unsigned |  | `0` |
| `started_at` | timestamp |  |  |
| `finished_at` | timestamp | có |  |

Index: PK (id)

## payment_transactions

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `payment_id` | bigint unsigned |  |  |
| `gateway_code` | varchar(64) |  |  |
| `type` | varchar(16) |  |  |
| `gateway_transaction_id` | varchar(128) |  |  |
| `amount` | bigint | có |  |
| `status` | varchar(24) |  |  |
| `raw_payload_masked` | json | có |  |
| `correlation_id` | varchar(64) | có |  |
| `created_at` | timestamp(6) |  |  |

Index: index (payment_id); unique (gateway_code, gateway_transaction_id, type); PK (id)

Khoá ngoại: payment_id → payments(id)

## payments

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `order_id` | bigint unsigned |  |  |
| `gateway_code` | varchar(64) |  |  |
| `amount` | bigint |  |  |
| `refunded_amount` | bigint |  | `0` |
| `currency_code` | char(3) |  |  |
| `status` | varchar(24) |  |  |
| `gateway_reference` | varchar(128) | có |  |
| `expires_at` | timestamp | có |  |
| `paid_at` | timestamp | có |  |
| `meta` | json | có |  |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: index (order_id); unique (public_id); index (status, expires_at); index (status); PK (id)

Khoá ngoại: order_id → orders(id)

## plugin_seeders

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `plugin_id` | varchar(128) |  |  |
| `seeder` | varchar(255) |  |  |
| `ran_at` | datetime |  |  |

Index: PK (plugin_id, seeder)

## plugins

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | varchar(128) |  |  |
| `version` | varchar(32) |  |  |
| `status` | varchar(16) |  |  |
| `last_error` | text | có |  |
| `installed_at` | datetime |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id)

## price_history

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `price_list_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `old_amount` | bigint | có |  |
| `new_amount` | bigint | có |  |
| `old_compare_at_amount` | bigint | có |  |
| `new_compare_at_amount` | bigint | có |  |
| `actor_type` | varchar(16) | có |  |
| `actor_id` | bigint unsigned | có |  |
| `correlation_id` | varchar(64) | có |  |
| `created_at` | datetime(6) |  |  |

Index: index (price_list_id, created_at); index (variant_id, created_at); PK (id)

## price_lists

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `name` | varchar(255) |  |  |
| `currency_code` | char(3) |  | `VND` |
| `type` | varchar(16) |  |  |
| `customer_group_id` | bigint unsigned | có |  |
| `priority` | int |  | `0` |
| `starts_at` | datetime | có |  |
| `ends_at` | datetime | có |  |
| `status` | varchar(16) |  | `active` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (code); PK (id)

## prices

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `price_list_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `amount` | bigint |  |  |
| `compare_at_amount` | bigint | có |  |
| `min_qty` | int unsigned |  | `1` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: unique (price_list_id, variant_id, min_qty); index (variant_id); PK (id)

Khoá ngoại: price_list_id → price_lists(id) on delete cascade; variant_id → variants(id)

## promotion_rules

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `promotion_id` | bigint unsigned |  |  |
| `rule_type` | varchar(64) |  |  |
| `config` | json |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (promotion_id)

Khoá ngoại: promotion_id → promotions(id) on delete cascade

## promotion_usages

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `promotion_id` | bigint unsigned |  |  |
| `voucher_id` | bigint unsigned | có |  |
| `order_id` | bigint unsigned |  |  |
| `customer_id` | bigint unsigned | có |  |
| `discount_amount` | bigint |  |  |
| `currency_code` | char(3) |  |  |
| `status` | varchar(16) |  | `applied` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (order_id); unique (promotion_id, order_id); index (voucher_id)

Khoá ngoại: promotion_id → promotions(id); voucher_id → vouchers(id)

## promotions

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `campaign_id` | bigint unsigned | có |  |
| `name` | varchar(255) |  |  |
| `status` | varchar(16) |  | `active` |
| `starts_at` | datetime | có |  |
| `ends_at` | datetime | có |  |
| `priority` | int |  | `0` |
| `stacking` | varchar(16) |  | `combinable` |
| `requires_voucher` | tinyint(1) |  | `1` |
| `action_type` | varchar(64) |  |  |
| `action_config` | json |  |  |
| `usage_limit` | int unsigned | có |  |
| `usage_count` | int unsigned |  | `0` |
| `budget_amount` | bigint | có |  |
| `budget_used_amount` | bigint |  | `0` |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (campaign_id); index (status)

Khoá ngoại: campaign_id → campaigns(id) on delete set null

## pulse_aggregates

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `bucket` | int unsigned |  |  |
| `period` | mediumint unsigned |  |  |
| `type` | varchar(255) |  |  |
| `key` | mediumtext |  |  |
| `key_hash` | binary(16) | có |  |
| `aggregate` | varchar(255) |  |  |
| `value` | decimal(20,2) |  |  |
| `count` | int unsigned | có |  |

Index: PK (id); unique (bucket, period, type, aggregate, key_hash); index (period, bucket); index (period, type, aggregate, bucket); index (type)

## pulse_entries

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `timestamp` | int unsigned |  |  |
| `type` | varchar(255) |  |  |
| `key` | mediumtext |  |  |
| `key_hash` | binary(16) | có |  |
| `value` | bigint | có |  |

Index: PK (id); index (key_hash); index (timestamp); index (timestamp, type, key_hash, value); index (type)

## pulse_values

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `timestamp` | int unsigned |  |  |
| `type` | varchar(255) |  |  |
| `key` | mediumtext |  |  |
| `key_hash` | binary(16) | có |  |
| `value` | mediumtext |  |  |

Index: PK (id); index (timestamp); index (type); unique (type, key_hash)

## refunds

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `payment_id` | bigint unsigned |  |  |
| `amount` | bigint |  |  |
| `status` | varchar(16) |  |  |
| `reason` | varchar(255) |  |  |
| `idempotency_key` | varchar(128) |  |  |
| `gateway_reference` | varchar(128) | có |  |
| `requested_by_type` | varchar(32) | có |  |
| `requested_by_id` | bigint unsigned | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (idempotency_key); index (payment_id); unique (public_id)

Khoá ngoại: payment_id → payments(id)

## return_attachments

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `return_request_id` | bigint unsigned |  |  |
| `disk` | varchar(32) |  |  |
| `path` | varchar(255) |  |  |
| `mime_type` | varchar(64) |  |  |
| `size_bytes` | int unsigned |  |  |
| `source` | varchar(16) |  |  |
| `created_at` | timestamp |  |  |

Index: PK (id); index (return_request_id)

Khoá ngoại: return_request_id → return_requests(id) on delete cascade

## return_events

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `return_request_id` | bigint unsigned |  |  |
| `from_status` | varchar(16) | có |  |
| `to_status` | varchar(16) |  |  |
| `note` | varchar(255) | có |  |
| `actor_type` | varchar(32) | có |  |
| `actor_id` | bigint unsigned | có |  |
| `source` | varchar(16) |  |  |
| `created_at` | timestamp(6) |  |  |

Index: PK (id); index (return_request_id)

Khoá ngoại: return_request_id → return_requests(id) on delete cascade

## return_lines

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `return_request_id` | bigint unsigned |  |  |
| `order_line_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `exchange_variant_id` | bigint unsigned | có |  |
| `quantity` | int unsigned |  |  |
| `refund_amount` | bigint |  |  |
| `condition` | varchar(16) | có |  |
| `restock_location_id` | bigint unsigned | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (order_line_id); index (return_request_id)

Khoá ngoại: order_line_id → order_lines(id); return_request_id → return_requests(id) on delete cascade

## return_reasons

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(32) |  |  |
| `names` | json |  |  |
| `position` | int unsigned |  | `0` |
| `is_active` | tinyint(1) |  | `1` |
| `store_fault` | tinyint(1) |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (code)

## return_requests

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `number` | varchar(40) |  |  |
| `order_id` | bigint unsigned |  |  |
| `status` | varchar(16) |  |  |
| `reason_code` | varchar(32) |  |  |
| `resolution` | varchar(16) |  | `refund` |
| `customer_note` | varchar(500) | có |  |
| `return_tracking_number` | varchar(64) | có |  |
| `source` | varchar(16) |  |  |
| `refund_amount` | bigint |  |  |
| `refunded_amount` | bigint | có |  |
| `replacement_order_id` | bigint unsigned | có |  |
| `currency_code` | char(3) |  |  |
| `lock_version` | int unsigned |  | `0` |
| `resolved_at` | timestamp | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (number); index (order_id, status); unique (public_id); index (replacement_order_id); index (status)

Khoá ngoại: order_id → orders(id); replacement_order_id → orders(id)

## role_permissions

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `role_id` | bigint unsigned |  |  |
| `permission` | varchar(128) |  |  |

Index: PK (role_id, permission)

Khoá ngoại: role_id → roles(id) on delete cascade

## roles

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `name` | varchar(255) |  |  |
| `is_system` | tinyint(1) |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (code)

## sessions

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | varchar(255) |  |  |
| `user_id` | bigint unsigned | có |  |
| `ip_address` | varchar(45) | có |  |
| `user_agent` | text | có |  |
| `payload` | longtext |  |  |
| `last_activity` | int |  |  |

Index: PK (id); index (last_activity); index (user_id)

## settings

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `namespace` | varchar(64) |  |  |
| `key` | varchar(96) |  |  |
| `value` | text |  |  |
| `encrypted` | tinyint(1) |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (namespace, key)

## shipment_events

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `shipment_id` | bigint unsigned |  |  |
| `status` | varchar(24) |  |  |
| `event_id` | varchar(128) |  |  |
| `source` | varchar(64) |  |  |
| `description` | varchar(255) | có |  |
| `payload_masked` | json | có |  |
| `occurred_at` | timestamp |  |  |
| `created_at` | timestamp(6) |  |  |

Index: PK (id); unique (shipment_id, event_id)

Khoá ngoại: shipment_id → shipments(id) on delete cascade

## shipment_lines

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `shipment_id` | bigint unsigned |  |  |
| `order_line_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `quantity` | int unsigned |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (order_line_id); index (shipment_id)

Khoá ngoại: order_line_id → order_lines(id); shipment_id → shipments(id) on delete cascade

## shipments

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `public_id` | char(26) |  |  |
| `order_id` | bigint unsigned |  |  |
| `location_id` | bigint unsigned |  |  |
| `carrier_code` | varchar(64) |  |  |
| `service_code` | varchar(64) | có |  |
| `tracking_number` | varchar(64) | có |  |
| `label_url` | varchar(255) | có |  |
| `cod_amount` | bigint |  | `0` |
| `currency_code` | char(3) |  |  |
| `status` | varchar(24) |  |  |
| `booking_attempts` | smallint unsigned |  | `0` |
| `last_error` | varchar(255) | có |  |
| `shipped_at` | timestamp | có |  |
| `delivered_at` | timestamp | có |  |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (carrier_code, tracking_number); index (location_id); index (order_id); unique (public_id); index (status, delivered_at); index (status)

Khoá ngoại: location_id → locations(id); order_id → orders(id)

## sizes

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `size_system` | varchar(16) |  |  |
| `code` | varchar(16) |  |  |
| `sort_order` | int unsigned |  |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (size_system, code); index (size_system, sort_order)

## staff_invite_tokens

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `email` | varchar(255) |  |  |
| `token` | varchar(255) |  |  |
| `created_at` | timestamp | có |  |

Index: PK (email)

## staff_password_reset_tokens

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `email` | varchar(255) |  |  |
| `token` | varchar(255) |  |  |
| `created_at` | timestamp | có |  |

Index: PK (email)

## staff_role_assignments

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `staff_user_id` | bigint unsigned |  |  |
| `role_id` | bigint unsigned |  |  |
| `scope_type` | varchar(32) |  |  |
| `scope_id` | bigint unsigned | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (role_id); unique (staff_user_id, role_id, scope_type, scope_id)

Khoá ngoại: role_id → roles(id); staff_user_id → staff_users(id) on delete cascade

## staff_users

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `name` | varchar(255) |  |  |
| `email` | varchar(255) |  |  |
| `password` | varchar(255) |  |  |
| `status` | varchar(16) |  | `active` |
| `locale` | varchar(8) | có |  |
| `remember_token` | varchar(100) | có |  |
| `last_login_at` | datetime | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (email)

## stock_levels

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `location_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `on_hand` | int |  | `0` |
| `reserved` | int unsigned |  | `0` |
| `safety_stock` | int unsigned |  | `0` |
| `sync_version` | bigint unsigned | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (location_id, variant_id); index (variant_id)

Khoá ngoại: location_id → locations(id); variant_id → variants(id)

## stock_movements

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `location_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `type` | varchar(16) |  |  |
| `on_hand_delta` | int |  | `0` |
| `reserved_delta` | int |  | `0` |
| `on_hand_after` | int |  |  |
| `reserved_after` | int |  |  |
| `reason` | varchar(255) | có |  |
| `reference` | varchar(128) | có |  |
| `actor_type` | varchar(16) | có |  |
| `actor_id` | bigint unsigned | có |  |
| `correlation_id` | varchar(64) | có |  |
| `created_at` | datetime(6) |  |  |

Index: PK (id); index (location_id, created_at); index (reference); index (variant_id, created_at)

## stock_reservations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `reservation_key` | varchar(128) |  |  |
| `location_id` | bigint unsigned |  |  |
| `variant_id` | bigint unsigned |  |  |
| `quantity` | int unsigned |  |  |
| `status` | varchar(16) |  | `active` |
| `expires_at` | datetime | có |  |
| `release_reason` | varchar(64) | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (location_id); index (reservation_key, status); index (status, expires_at); index (variant_id)

Khoá ngoại: location_id → locations(id); variant_id → variants(id)

## style_attribute_values

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `attribute_id` | bigint unsigned |  |  |
| `attribute_value_id` | bigint unsigned | có |  |
| `value_text` | varchar(1000) | có |  |
| `value_bool` | tinyint(1) | có |  |

Index: PK (id); index (attribute_id, attribute_value_id); index (attribute_value_id); index (style_id, attribute_id)

Khoá ngoại: attribute_id → attributes(id) on delete cascade; attribute_value_id → attribute_values(id) on delete cascade; style_id → styles(id) on delete cascade

## style_colors

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `color_id` | bigint unsigned |  |  |
| `position` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (color_id); unique (style_id, color_id)

Khoá ngoại: color_id → colors(id); style_id → styles(id) on delete cascade

## style_link_group_items

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `style_link_group_id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `position` | int unsigned |  | `0` |

Index: PK (id); index (style_id); unique (style_link_group_id, style_id)

Khoá ngoại: style_id → styles(id) on delete cascade; style_link_group_id → style_link_groups(id) on delete cascade

## style_link_groups

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `link_type_id` | bigint unsigned |  |  |
| `root_style_id` | bigint unsigned | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (link_type_id, root_style_id); index (root_style_id)

Khoá ngoại: link_type_id → link_types(id); root_style_id → styles(id) on delete cascade

## style_translations

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `locale` | varchar(8) |  |  |
| `slug` | varchar(160) | có |  |
| `name` | varchar(255) |  |  |
| `description` | text | có |  |
| `care_instructions` | text | có |  |
| `meta_title` | varchar(255) | có |  |
| `meta_description` | varchar(512) | có |  |

Index: PK (id); unique (locale, slug); unique (style_id, locale)

Khoá ngoại: style_id → styles(id) on delete cascade

## style_video

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `style_id` | bigint unsigned |  |  |
| `video_id` | bigint unsigned |  |  |
| `position` | int unsigned |  | `0` |

Index: PK (style_id, video_id); index (video_id)

Khoá ngoại: style_id → styles(id) on delete cascade; video_id → videos(id) on delete cascade

## style_view_stats

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `style_id` | bigint unsigned |  |  |
| `hour` | datetime |  |  |
| `views` | int unsigned |  | `0` |

Index: PK (style_id, hour); index (hour)

## styles

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `brand_id` | bigint unsigned | có |  |
| `style_code` | varchar(64) |  |  |
| `slug` | varchar(160) |  |  |
| `status` | varchar(16) |  | `draft` |
| `published_from` | datetime | có |  |
| `published_to` | datetime | có |  |
| `primary_category_id` | bigint unsigned | có |  |
| `search_text` | text |  |  |
| `list_price_min` | bigint | có |  |
| `meta` | json | có |  |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); index (brand_id); index (list_price_min); index (primary_category_id); unique (slug); index (status, published_from); unique (style_code)

Khoá ngoại: brand_id → brands(id); primary_category_id → categories(id) on delete set null

## users

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `name` | varchar(255) |  |  |
| `email` | varchar(255) |  |  |
| `email_verified_at` | timestamp | có |  |
| `password` | varchar(255) |  |  |
| `remember_token` | varchar(100) | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (email)

## variants

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `style_id` | bigint unsigned |  |  |
| `style_color_id` | bigint unsigned |  |  |
| `size_id` | bigint unsigned |  |  |
| `sku` | varchar(64) |  |  |
| `barcode` | varchar(32) | có |  |
| `status` | varchar(16) |  | `active` |
| `weight_gram` | int unsigned | có |  |
| `meta` | json | có |  |
| `lock_version` | int unsigned |  | `0` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (barcode); index (size_id); unique (sku); unique (style_color_id, size_id); index (style_id, status)

Khoá ngoại: size_id → sizes(id); style_color_id → style_colors(id); style_id → styles(id)

## videos

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `driver` | varchar(32) |  |  |
| `reference` | varchar(255) |  |  |
| `title` | varchar(255) | có |  |
| `data` | json | có |  |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (driver, reference)

## vouchers

| Cột | Kiểu | Null | Mặc định |
|---|---|---|---|
| `id` | bigint unsigned |  |  |
| `promotion_id` | bigint unsigned |  |  |
| `code` | varchar(64) |  |  |
| `usage_limit` | int unsigned | có |  |
| `used_count` | int unsigned |  | `0` |
| `expires_at` | datetime | có |  |
| `status` | varchar(16) |  | `active` |
| `created_at` | timestamp | có |  |
| `updated_at` | timestamp | có |  |

Index: PK (id); unique (code); index (promotion_id)

Khoá ngoại: promotion_id → promotions(id) on delete cascade
