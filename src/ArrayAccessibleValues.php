<?php

trait ArrayAccessibleValues {
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->values[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->values[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->values[$offset]) ? $this->values[$offset] : null;
    }
}
