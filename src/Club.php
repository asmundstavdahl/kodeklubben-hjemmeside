<?php

class Club extends AppEntity {
    protected static $fields = [
        "id" => \integer::class,
        "name" => \string::class,
        "subdomain" => \string::class,
        "email" => \string::class,
        "region" => \string::class,
        "facebook" => \string::class,
    ];

    public static function getCurrentClub()
    {
        return new Club([
            "id" => 1,
            "name" => "Kodeklubben Trondheim",
            "email" => "kodeklubbentrondheim@gmail.com",
            "region" => "Trondheim",
            "facebook" => "://facebook.com/",
        ]);
    }

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSubdomain()
    {
        return $this->subdomain;
    }
    public function setSubdomain($subdomain)
    {
        $this->subdomain = $subdomain;
    }

    public function getEmail()
    {
        return $this->email;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getRegion()
    {
        return $this->region;
    }
    public function setRegion($region)
    {
        $this->region = $region;
    }

    public function getFacebook()
    {
        return $this->facebook;
    }
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;
    }

}
