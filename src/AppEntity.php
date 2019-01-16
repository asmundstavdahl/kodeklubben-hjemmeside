<?php

class AppEntity extends \Rapd\PersistableEntity {
    use \Rapd\Prototype;
    #use ArrayAccessibleValues;

    public function validateFieldValue($field, $value, $throwOnInvalidValue = true){
        return true;
    }
}
