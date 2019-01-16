<?php

class Message extends AppEntity {
    protected static $fields = [
        "id" => \integer::class,
        "body" => \string::class,
        "timestamp" => \integer::class,
    ];

    public static function findAllNonExpired()
    {
        return array_filter(self::findAll(), function($message){
            return strtotime($message->expireDate) > time();
        });
    }
}
