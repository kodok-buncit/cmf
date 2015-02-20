<?php
/**
 * This file is part of the UnikaCMF project
 *
 * @author Fajar Khairil <fajar.khairil@gmail.com>
 * @license MIT
 */

namespace Unika\Provider;

use Pimple\Container;
use Unika\Interfaces\ServiceProviderInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Silex\EventListener\LogListener;


class MonologServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['logger'] = function () use ($app) {
            return $app['monolog'];
        };

        $app['monolog'] = function ($app) {
            $log = new \Monolog\Logger($app['monolog.name']);

            $log->pushHandler($app['monolog.handler']);

            if (isset($app['debug']) && $app['debug'] && isset($app['monolog.handler.debug'])) {
                $log->pushHandler($app['monolog.handler.debug']);
            }

            return $log;
        };

        $app['monolog.formatter'] = function () {
            return new LineFormatter();
        };

        $app['monolog.handler'] = function () use ($app) {
            $level = MonologServiceProvider::translateLevel($app['monolog.level']);

            $handler = new StreamHandler($app['monolog.logfile'], $level, $app['monolog.bubble'], $app['monolog.permission']);
            $handler->setFormatter($app['monolog.formatter']);

            return $handler;
        };

        $app['monolog.level'] = function ($app) {
            if( $app['debug'] )
                return Logger::DEBUG;
            else
                return $app->config('app.debug_level');
        };

        $app['monolog.listener'] = function () use ($app) {
            return new LogListener($app['logger'], $app['monolog.exception.logger_filter']);
        };

        $app['monolog.name'] = 'myapp';
        $app['monolog.bubble'] = true;
        $app['monolog.permission'] = null;
        $app['monolog.exception.logger_filter'] = null;
    }

    public function boot(Application $app)
    {
        if (isset($app['monolog.listener'])) {
            $app['dispatcher']->addSubscriber($app['monolog.listener']);
        }
    }

    public static function translateLevel($name)
    {
        // level is already translated to logger constant, return as-is
        if (is_int($name)) {
            return $name;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($name);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException("Provided logging level '$name' does not exist. Must be a valid monolog logging level.");
        }

        return $levels[$upper];
    }

    /**
     *
     *  return description of provider
     */
    public function getDescription()
    {
        return 'Monolog Service Provider';
    }

    /**
     *
     *  return array of service with each description
     */
    public function getServices()
    {
        return array(
            'logger'  => 'Monolog\Logger'
        );
    }

    /**
     *
     *  return an array('author' => '','license' => '','url' => '');
     */
    public function getInfo()
    {
        return array(
            'author'    => 'Fajar Khairil',
            'license'   => 'MIT',
            'url'       => 'http://www.unikacreative.com/',
            'version'   => '0.1'
        );
    }
}