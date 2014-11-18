<?php
// 本类供测试用途
class IndexAction extends AuthAction {
    public function _initialize() {
        //initialize
        parent::_initialize();
    }
    public function index(){
        header("Content-Type: text/html; charset=UTF-8");
        dump($_SESSION);
    } 
}