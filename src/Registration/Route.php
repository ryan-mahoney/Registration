<?php
/**
 * Opine\Registration\Application
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine\Registration;

class Route {
    private $route;
    private $separation;
    private $root;
    private $registration;
    private $authentication;

    public function __construct ($root, $route, $separation, $formRoute, $registration, $authentication) {
        $this->route = $route;
        $this->separation = $separation;
        $this->root = $root;
        $this->formRoute = $formRoute;
        $this->registration = $registration;
        $this->authentication = $authentication;
    }

    public function paths ($bundleRoot='') {
        $this->route->get('/Registration/{eventSlug}', function ($eventSlug) {
            $event = $this->registration->eventFindBySlug($eventSlug);
            if ($event === false) {
                $this->error('Unknown event');
                return false;
            }
            if (isset($event['login_required']) && $event['login_required'] == 't') {
                $this->authentication->checkAndRedirect();
            }
            $orderId = $this->registration->orderIdMake($event);
            header('Location: /Registration/' . $eventSlug . '/options/registration_orders:' . $orderId);
        });

        $this->route->get('/Registration/{eventSlug}/options/{orderId}', function ($eventSlug, $orderId) {
            $data = [];
            if ($this->inputValidation('options', $eventSlug, $orderId, $data) === false) {
                return;
            }
            $this->separation->
                app($data['app'])->
                layout($data['layout'])->
                args('form', [
                    'id' => $orderId
                ])->
                template()->
                write();
        });

        $this->route->get('/Registration/{eventSlug}/attendees/{orderId}', function ($eventSlug, $orderId) {
            $data = [];
            if ($this->inputValidation('attendees', $eventSlug, $orderId, $data) === false) {
                return;
            }
            $this->separation->
                app($data['app'])->
                layout($data['layout'])->
                args('form', [
                    'id' => $orderId
                ])->template()->
                write();
        });

        $this->route->get('/Registration/{eventSlug}/payment/{orderId}', function ($eventSlug, $orderId) {
            $data = [];
            if ($this->inputValidation('payment', $eventSlug, $orderId, $data) === false) {
                return;
            }
            $this->separation->
                app($data['app'])->
                layout($data['layout'])->
                args('form', [
                    'id' => $orderId
                ])->
                template()->
                write();
        });

        $this->route->get('/Registration/{eventSlug}/receipt/{orderId}', function ($eventSlug, $orderId) {
            $data = [];
            if ($this->inputValidation('receipt', $eventSlug, $orderId, $data) === false) {
                return;
            }
            $this->separation->
                app($data['app'])->
                layout($data['layout'])->
                bindingAdd('data', ['type' => 'array'], $data)->
                template()->
                write();
        });
    }

    private function inputValidation ($mode, $eventSlug, $orderId, &$data) {
        $data['event'] = $this->registration->eventFindBySlug($eventSlug);
        if ($data['event'] === false) {
            $this->error('Unknown event');
            return false;
        }
        if ($this->registration->eventVerifyOptions($data['event']) === false) {
            $this->error('Event has no registration options');
            return false;
        }
        $data['order'] = $this->registration->orderFindById($orderId);
        if ($data['order'] === false) {
            $this->error('Invalid order.');
            return false;
        }
        if (in_array($mode, ['attendees', 'payment'])) {
            if (isset($data['order']['status']) && $data['order']['status'] == 'completed') {
                $this->error('Order has been completed.');
                return false;
            }
        }
        $data['app'] = 'bundles/Registration/app/forms/' . $mode;
        $data['layout'] = 'Registration/forms/' . $mode;
        if ($mode == 'receipt') {
            $data['app'] = str_replace('/forms/', '/documents/', $data['app']);
            $data['layout'] = str_replace('/forms/', '/documents/', $data['layout']);
        }
        if (!empty($data['event']['config_' . $mode . '_app'])) {
            $data['app'] = $event['config_' . $mode . '_app'];
        }
        if (!empty($event['config_' . $mode . '_layout'])) {
            $data['layout'] = $event['config_' . $mode . '_layout'];
        }
    }

    private function error ($message) {
        $this->separation->
            app('bundles/Registration/app/')->
            layout('Registration/error')->
            bindingAdd('error', ['type' => 'array'], $message)->
            template()->
            write();
    }

    public function build ($bundleRoot) {}

    private function authenticationBuild ($authorizations) {}

    public function upgrade ($bundleRoot) {}

    public function location () {
        return __DIR__;
    }
}