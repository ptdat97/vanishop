<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

enum MovementType: string
{
    case Sync = 'sync';
    case Reserve = 'reserve';
    case Release = 'release';
    case Commit = 'commit';
    case Return = 'return';
    case Adjust = 'adjust';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case SafetyStock = 'safety_stock';
    /** Sửa `reserved` cho khớp hàng giữ active (vani:inventory:verify --repair-reserved). */
    case Reconcile = 'reconcile';
}
