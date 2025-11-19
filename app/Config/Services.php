<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Event Handlers Service
     */
    public static function handlers(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('handlers');
        }

        return new \App\Services\EventHandlers();
    }

    /**
     * Messaging Service
     */
    public static function messaging(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('messaging');
        }

        return new \App\Services\MessagingService();
    }

    public static function slotgenerator(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('slotgenerator');
        }

        return new \App\Services\Appt\SlotGenerator(
            new \App\Models\Appt\AvailabilityModel(),
            new \App\Models\Appt\ExceptionModel(),
            new \App\Models\Appt\BookingModel()
        );
    }

    public static function apptpolicy(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('apptpolicy');
        }

        return new \App\Services\Appt\PolicyService();
    }

    public static function icsservice(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('icsservice');
        }

        return new \App\Services\Appt\IcsService();
    }

    public static function apptnotifications(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('apptnotifications');
        }

        return new \App\Services\Appt\NotificationService(
            new \App\Models\Appt\NotificationModel()
        );
    }

    public static function authcontext(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('authcontext');
        }

        return new \App\Services\AuthContext();
    }
}
