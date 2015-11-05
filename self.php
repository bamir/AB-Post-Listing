<?php

    class A {

        private function __construct() {
            
        }

        public function which() {
            echo "I am an A!";
        }

        public function getClass() {
            $this->which();
        }

    }

    class B extends A {

        public function which() {
            echo "I am a B!";
        }

    }

    /*     * ********************************************************* */

    $Obj = new A();


    $Obj->getClass();
?>