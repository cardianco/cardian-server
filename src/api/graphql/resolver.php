<?php
/**
 * @api graphql
 * @author smr
 * @package dev
 * @version 0.1.0
 * @copyright MIT
 */

namespace src\api;
use \src\core\database;
use \GraphQL\Type\Definition\ResolveInfo;

class resolver {
    static public function values(database $db) {
        $typeResolvers = [
            'Session' => function($values, $args, $ctx, $inf) use($db) {
                $session = $db->getSession($inf->rootValue['sid']);
                $session['user'] = $inf->rootValue['User'];
                $session['type'] = $inf->rootValue['DeviceType'];
                return $session;
            },
            'LatestStatus' => function($values, $args, $ctx, $inf) use ($db) {
                $lst = $db->getLatestStatus($values['uid']);
                $lst['user'] = $inf->rootValue['User'];
                return $lst;
            },
            'User' => function($values, $args, $ctx, $inf) use ($db) {
                $user = $db->getUser($values['uid']);
                $user['state'] = $inf->rootValue['State'];
                $user['info'] = $inf->rootValue['UserInfo'];
                return $user;
            },
            'Field' => fn($values, $args, $ctx, $inf) =>  $db->getFields($values['fid']),
            'State' => fn($values, $args, $ctx, $inf) =>  $db->getStates($values['fid']),
            'DeviceType' => fn($values, $args, $ctx, $inf) =>  $db->getDeviceType($values['tid']),
            'UserInfo' => fn($values, $args, $ctx, $inf) =>  $db->getUserInfo($values['id'])
        ];

        $rootResolvers = [
            'user' => $typeResolvers['User'],
            'session' => $typeResolvers['Session'],
            'latestStatus' => $typeResolvers['LatestStatus'],
            'sessions' => function($root, $args, $ctx, $inf) use ($db) {
                $userSessions = $db->getUserSessions($root['uid']);
                foreach($userSessions as &$ss) $ss['user'] = $inf->rootValue['User'];
                foreach($userSessions as &$ss) $ss['type'] = $inf->rootValue['DeviceType'];
                return $userSessions;
            },
            'statusHistory' => function($root, $args, $ctx, $inf) use ($db) {
                [$sid, $limit, $accessed] = [$root['sid'], $args['limit'], $args['accessed']];
                $hist = $db->getStatusHistory($sid, $limit, $accessed);

                foreach($hist as &$h) $h['session'] = $root['Session'];
                foreach($hist as &$h) $h['fieldType'] = $root['Field'];

                return $hist;
            },
            'commandHistory' => function($root, $args, $ctx, $inf) use ($db) {
                [$sid, $limit, $accessed] = [$root['sid'], $args['limit'], $args['accessed']];
                $hist = $db->getCommandHistory($sid, $limit, $accessed);

                foreach($hist as &$h) $h['session'] = $root['Session'];
                foreach($hist as &$h) $h['fieldType'] = $root['Field'];

                return $hist;
            },
            'boundaries' => function($root, $args, $ctx, $inf) use ($db) {
                $bndry = $db->getBoundariesByUser($root['uid']);

                foreach($bndry as &$b) $b['session'] = $root['Session'];
                foreach($bndry as &$b) $b['state'] = $root['State'];

                return $bndry;
            }
        ];

        $mutations = [
            'newStatus' => function($root, $args, $ctx, $inf) use ($db) {
                [$fid, $value] = [$args['fieldId'], $args['value']];
                $fields = $db->getFieldsPair();

                $jsonVal = $fields[$fid] != "json" ? [$fields[$fid] => $value] : $value;

                $db->updateLatestStatus($root['uid'], $jsonVal);
                return $db->addStatus($root['sid'], $fid, $value);
            },
            'createSession' => function($root, $args, $ctx, $inf) use ($db) {
                $session = $db->createSession($root['uid'], $args['tid'], $args['mac'], $root['ip']);
                $session['user'] = $inf->rootValue['User'];
                $session['type'] = $inf->rootValue['DeviceType'];
                return $session;
            },
            'sendCommand'   =>  fn($root, $args, $c, $i) => $db->addCommand($root['sid'], $args['fieldId'], $args['value']),
            'createBoundary' => fn($root, $args, $c, $i) => $db->addBoundary($root['sid'], $args['stateId'], $args['poly']),
            'updateBoundary'    => fn($r, $args, $c, $i) => $db->updateBoundary($args['id'], $args['stateId'], $args['poly']),
            'removeCommands'    => fn($r, $args, $c, $i) => $db->removeCommands($args['idList']) ?: null,
            'removeBoundaries'  => fn($r, $args, $c, $i) => $db->removeBoundaries($args['idList']) ?: null,
            'terminateSessions' => fn($r, $args, $c, $i) => $db->terminateSessions($args['idList']) ?: null,
        ];

        return array_merge($typeResolvers, $rootResolvers, $mutations);
    }
}