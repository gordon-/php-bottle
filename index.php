<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

require_once('build/bottle.phar');

function authenticated($request) {
    return $request->getParam('password') == 'bottleisacoolframework';
}

/**
 * @route /
 */
function index() {
    return 'Welcome on the Bottle index page!';
}

/**
 * @route /hello/:name
 */
function hello($name) {
    return "<h1>Hello, {$name}!</h1>";
}

/**
 * @route /mul/:num
 * @view /views/mul.php
 */
function mul($num) {
    return ['result' => $num * $num];
}

/**
 * @route /restricted
 * @requires authenticated
 * @view /views/restricted.php
 */
function restricted() {
    return ['status' => 'OK'];
}

/**
 * @route /session/:name
 * @view /views/session.php
 */
function session($name) {
    global $request, $response;
    $request->setSession('name', htmlspecialchars($name));
    return $response->redirect('session2');
}

function session_has($request, $args) {
    return $request->getSession($args[0]) !== null;
}

/**
 * @route /session2
 * @view /views/session.php
 * @requires session_has name
 */
function session2() {
    global $request;
    return ['name' => $request->getSession('name')];
}

/**
 * @route /session-destroy
 * @requires session_has name
 */
function session_kill() {
    global $request;
    $request->destroySession();
    return 'OK';
}
