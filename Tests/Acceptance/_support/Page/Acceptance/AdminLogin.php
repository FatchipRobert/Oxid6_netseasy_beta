<?php
namespace Page\Acceptance;

class AdminLogin extends \AcceptanceTester
{
    /**
      @var AcceptanceTester
     */
    //protected $tester;

    // we inject AcceptanceTester into our class
   /* public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;
    }*/

    // include url of current page
    public static $URL = 'https://oxidlocal.sokoni.it/eshop_6_5/source/admin';

    //
     // Declare UI map for this page here. CSS or XPath allowed.
    public static $usernameField = '#usr';
    public static $passwordField = '#pwd';
    public static $submitButton = '#login > input.btn';
    public static $username = 'info@easymoduler.dk';
    public static $password = 'Password123';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }
}
