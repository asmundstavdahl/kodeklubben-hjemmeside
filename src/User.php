<?php

class User extends AppEntity {
    protected static $fields = [
        "id" => \integer::class,
        "name" => \string::class,
        "email" => \string::class,
        "phone" => \string::class,
    ];

    public static function findCurrent()
    {
        return new User([
            "id" => 1337,
            "name" => "name",
            "email" => "email",
            "phone" => "phone",
        ]);
    }
}
