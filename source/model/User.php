<?php
/**
 * This file is part of sPof.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @lincense  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      http://spof.fem-net.de
 */

namespace FeM\sPof\model;

use FeM\sPof\InvalidParameterCheck;
use FeM\sPof\Cache;

/**
 * User repository.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
abstract class User extends AbstractModelWithId
{
    use SearchableTrait;

    /**
     * Referenced Table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_user';


    /**
     * @internal
     *
     * @param array $input
     */
    protected static function validate(array $input)
    {
        self::getValidator($input)
            ->isNoId('user_id', _('Ungültige user id.'))
            ->isEmpty('name', _('Es wurde kein Name angegeben.'))
            ->isEmpty('email', _('E-Mail Adresse darf nicht leer sein.'))
            ->isNoEmail('email', _('Es wurde keine Email Adresse angegeben.'))
            ->byRegex(
                'passphrase',
                InvalidParameterCheck::REGEX_ACCEPTABLE_PASSWORD,
                _('Es wurde kein Passwort angegeben.')
            )
            ->validate();
    } // function


    /**
     * Get total number of users.
     *
     * @api
     *
     * @return int
     */
    public static function getCount()
    {
        $user_count = Cache::fetch('user_count');
        if ($user_count !== false) {
            return $user_count;
        }

        $stmt = self::createStatement(
            "
            SELECT count(*)
            FROM tbl_user
            WHERE
                disabled IS FALSE AND
                visible IS TRUE
                "
        );

        $user_count = $stmt->fetchColumn();
        Cache::store('user_count', $user_count, 300);
        return $user_count;
    } // function


    /**
     * Given the credentials of a user, the id is returned or false for invalid credentials.
     *
     * @api
     *
     * @param string $name
     * @param string $password
     * @param bool $crypted (optional)
     *
     * @return int
     */
    public static function getIdByCredentials($name, $password, $crypted = false)
    {
        if (!$crypted) {
            $stmt = self::createStatement(
                "
                SELECT id
                FROM tbl_user
                WHERE
                    lower(name) = lower(:name)
                    AND passphrase = crypt(md5(:passphrase),passphrase)
                    AND disabled = FALSE
                LIMIT 1
                "
            );

            $stmt->assign('name', $name);
            $stmt->assign('passphrase', $password);

            return $stmt->fetchColumn();
        } else {
            $stmt = self::createStatement(
                "
                SELECT
                    id,
                    passphrase
                FROM tbl_user
                WHERE
                    lower(name) = lower(:name)
                    AND disabled = FALSE
                LIMIT 1
                "
            );
            $stmt->assign('name', $name);
            $user = $stmt->fetch();
            if ($user !== false && crypt($user['passphrase'], '$1$jk$') == $password) {
                return $user['id'];
            }
        }

        return false;
    } // function


    /**
     * Get the passphrase of a user.
     *
     * @internal
     *
     * @param int $user_id
     *
     * @return string
     */
    public static function getPassphraseById($user_id)
    {
        $stmt = self::createStatement(
            "
            SELECT passphrase
            FROM tbl_user
            WHERE id=:user_id
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * Indicate that a user has updated his profile or associated data.
     *
     * @api
     *
     * @param int $user_id
     *
     * @return bool
     */
    public static function touchById($user_id)
    {
        $stmt = self::createStatement(
            "
            UPDATE tbl_user
            SET lastupdate=CURRENT_TIMESTAMP
            WHERE
                id=:user_id
                AND lastupdate < (CURRENT_TIMESTAMP - INTERVAL '1 DAY')
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Returns an array containing information about the user with the given name.
     *
     * @api
     *
     * @param string $name The nickname of the target user.
     *
     * @return array|false An array containing the entries mentioned above or an empty array if the user was not found.
     *         Returns false if the given name is invalid.
     */
    public static function getByName($name)
    {
        if (empty($name)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                firstname,
                lastname,
                email
            FROM tbl_user
            WHERE
                lower(name)=lower(:name)
                AND visible IS TRUE
            "
        );
        $stmt->assign('name', $name);

        return $stmt->fetch();
    } // function


    /**
     * Check if a user with the given E-Mail-Address exists.
     *
     * @api
     *
     * @param string $email
     *
     * @return bool
     */
    public static function existsEmail($email)
    {
        if (empty($email)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT TRUE
            FROM tbl_user
            WHERE lower(email)=lower(:email)
            "
        );
        $stmt->assign('email', $email);

        return $stmt->affected();
    } // function


    /**
     * Update the password of a user.
     *
     * @api
     *
     * @param int $user_id
     * @param string $password
     *
     * @return bool
     */
    public static function updatePassword($user_id, $password)
    {
        self::getValidator(['user_id' => $user_id, 'password' => $password])
            ->isNoId('user_id', _('Ungültige user id.'))
            ->byRegex('password', InvalidParameterCheck::REGEX_ACCEPTABLE_PASSWORD, _('Passwort darf nicht leer sein.'))
            ->validate();

        $stmt = self::createStatement(
            "
            UPDATE tbl_user
            SET passphrase=crypt(MD5(:password),gen_salt('bf',5))
            WHERE id=:user_id
            "
        );
        $stmt->assign('password', $password);
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Search for similiar names.
     *
     * @api
     *
     * @param string $exact
     * @param string $like
     *
     * @return array
     */
    public static function searchNameBySimilarity($exact, $like)
    {
        if (empty($exact) && empty($like)) {
            return [];
        }

        $stmt = self::createStatement(
            "SELECT
                name AS value,
                CONCAT(firstname, ' ''', name, ''' ', lastname) AS label,
                CASE WHEN similarity(name,:exact) IS NULL THEN 0 ELSE similarity(name,:exact) END AS similarity
            FROM tbl_user
            WHERE
                name % :exact
                OR name % :like
                OR CONCAT(firstname, ' ', lastname) % :like
            ORDER BY
                similarity DESC,
                label ASC
            LIMIT 10
            "
        );
        $stmt->assign('exact', $exact);
        $stmt->assign('like', $like);

        return $stmt->fetchAll();
    } // function


    /**
     * Get user id by user token.
     *
     * @api
     *
     * @param string $token
     *
     * @return int|false
     */
    public static function getIdByToken($token)
    {
        $stmt = self::createStatement(
            "
            SELECT id
            FROM tbl_user
            WHERE id=:token
            "
        );
        $stmt->assign('token', $token);

        return $stmt->fetchInt();
    } // function


    /**
     * Get a user by token.
     *
     * @api
     * @deprecated 1.0.0 use getIdByToken() in combination with getByPk()
     *
     * @param string $token
     *
     * @return array|false
     */
    public static function getByToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT
                id,
                firstname,
                lastname,
                name,
                passphrase,
                email,
                tokenexpiry,
                email_unverified
            FROM tbl_user
            WHERE token=:token
                AND tokenexpiry > now()
            LIMIT 1
            "
        );
        $stmt->assign('token', $token);

        return $stmt->fetch();
    } // function


    /**
     * Assign a random token to a user. The token will expire after 1 day.
     *
     * @api
     *
     * @param int $user_id
     *
     * @return array|bool
     */
    public static function generateToken($user_id)
    {
        $token = uniqid('sPof', true);
        $expirydate = new \DateTime();
        $expirydate->modify('+1 day');

        if (self::updateByPk($user_id, ['token' => $token, 'tokenexpiry' => $expirydate])) {
            return ['code' => $token, 'expirydate' => $expirydate];
        }
        return false;
    } // function


    /**
     * Mark a user token as invalid and let it expire immediatly.
     *
     * @api
     *
     * @param int $user_id
     *
     * @return bool
     */
    public static function invalidateToken($user_id)
    {
        return self::updateByPk($user_id, ['token' => null, 'tokenexpiry' => null]);
    } // function


    /**
     * Returns information about all user accounts with the given mail address.
     *
     * @api
     *
     * @param string $email The mail address to be looked up.
     *
     * @return array|false
     */
    public static function getByEmailAddress($email)
    {
        if (empty($email)) {
            return [];
        }

        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                firstname,
                lastname,
                email
            FROM tbl_user
            WHERE lower(email)=lower(:email)
            "
        );
        $stmt->assign('email', $email);

        return $stmt->fetchAll();
    } // function


    /**
     * Create a new user.
     *
     * @api
     * @deprecated 1.0.0 instead use parent::add, updatePassword and generateToken.
     *
     * @param array $input
     *
     * @return int
     */
    public static function add(array $input)
    {
        self::getValidator($input)
            ->isEmpty('name', _('Username has to be not empty!'))
            ->isEmpty('passphrase', _('Passphrase has to be not empty!'))
            ->isEmpty('email', _('Email has to be not empty!'))
            ->isEmpty('token', _('Token has to be not empty!'))
            ->validate();

        $stmt = self::createStatement(
            "
            INSERT INTO tbl_user (name, passphrase, email, token, tokenexpiry, firstname, lastname, disabled,
                                  visible)
            VALUES (
                :name,
                crypt(MD5(:passphrase),gen_salt('bf',5)),
                :email,
                :token,
                NOW() + '1 day',
                :firstname,
                :lastname,
                TRUE,
                FALSE
            )
            RETURNING id
            "
        );
        $stmt->assign('name', $input['name']);
        $stmt->assign('passphrase', $input['passphrase']);
        $stmt->assign('email', $input['email']);
        $stmt->assign('token', $input['token']);
        $stmt->assign('firstname', $input['firstname']);
        $stmt->assign('lastname', $input['lastname']);

        return $stmt->fetchColumn();
    } // function


    /**
     * Get database profile field names with description.
     *
     * @api
     *
     * @return array
     */
    public static function getProfileFieldNames()
    {
        return [
            'profile_homepage' => _('Homepage'),
            'profile_telephone' => _('Festnetz'),
            'profile_mobile' => _('Handy'),
            'profile_messenger_icq' => _('ICQ'),
            'profile_messenger_jabber' => _('Jabber'),
            'profile_public_email' => _('E-Mail'),
            ];
    } // function
}// class
