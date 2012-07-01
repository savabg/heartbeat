<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaVa
 * Date: 6/30/12
 * Time: 11:11 PM
 * To change this template use File | Settings | File Templates.
 */

class User extends Eloquent {

    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    public static $table = 'users';

    /**
     * Indicates if the model has update and creation timestamps.
     *
     * @var bool
     */
    public static $timestamps = true;
}