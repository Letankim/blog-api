<?php
namespace App\Enums;

/**
 * Enum các loại thông báo hệ thống
 */
enum NotificationType: string
{
    case ORDER_CREATED   = 'order_created';
    case ORDER_UPDATED   = 'order_updated';
    case ORDER_CANCELED  = 'order_canceled';

    case TABLE_UPDATED   = 'table_updated';
    case TABLE_CLEARED   = 'table_cleared';

    case NEW_ITEM_ADDED  = 'new_item_added';
    case ITEM_UPDATED    = 'item_updated';

    case ADMIN_PING      = 'admin_ping';

    case ADMIN_NEW_CONTACT      = 'admin_new_contact';


    case PING            = 'ping';
}
