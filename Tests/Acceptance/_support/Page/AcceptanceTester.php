<?php

use Codeception\Actor;
use Page\Acceptance\AdminLogin;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    public function loginToAdminPanel()
    {
        $I = $this;
        $I->amOnUrl(AdminLogin::$URL);
        $I->fillField(AdminLogin::$usernameField, AdminLogin::$username);
        $I->fillField(AdminLogin::$passwordField, AdminLogin::$password);
        $I->click(AdminLogin::$submitButton);
    }

    public function fillPaymentCardRequiredData()
    {
        $I = $this;
        $I->switchToFrame();
        $I->switchToFrame('#nets-checkout-iframe');
        $I->waitForElementClickable('#btnPay');
        $I->click('#btnPay');
        $I->wait(1);
        $I->switchToFrame('#easy-checkout-iframe');
        $cardNumber = "5413 0000 0000 0000";
        $I->fillField('#cardNumberInput', $cardNumber);
        $I->wait(1);
        $I->fillField('#cardExpiryInput', '0624');
        $I->fillField('#cardCvcInput', '123');
        $I->fillField('#cardCardholderNameInput', 'John Kovalsky');
        $cardNumberIsInvalidError = "//input[contains(@class,'card-input has-value visited has-error')]";

        try {
            if ($I->seeElement($cardNumberIsInvalidError)) {
                $I->fillField('#cardNumberInput', $cardNumber);
                $I->wait(1);
            }
        } catch (Exception $e) {
            $I->comment('no card number validation error');
        }
        $I->wait(2);
    }

    public function acceptPaymentTerms()
    {
        $I = $this;
        $I->switchToFrame();
        $I->switchToFrame('#nets-checkout-iframe');
        $I->click('#consentOnMerchantTerms');
        $I->wait(2);
    }

    public function successfullyPay()
    {
        $I = $this;
        $I->seeElement('#amountToPay');
        $I->waitForElementClickable('#btnPay');
        $I->click('#btnPay');
        $I->wait(2);
    }

    public function authorizePaymentInSimulator()
    {
        $I = $this;
        $I->switchToFrame();
        $I->switchToFrame('#nets-checkout-iframe');
        $I->switchToFrame('#nets-checkout-inception-iframe');
        $I->waitForElementClickable('#AuthenticationSuccessButton');
        $I->click('#AuthenticationSuccessButton');
    }

    public function fillRequiredAddressData()
    {
        $I = $this;
        $I->see('2. Address');
        $I->see('Purchase without registration');
        $I->click("//div[@id='optionNoRegistration']/div[contains(.,'Next')]/button");
        $I->see('Customer information');
        $I->see('Billing address');
        $I->see('Please fill in all mandatory fields labeled in bold.');
        $I->see('Shipping address');
        $I->fillField('#userLoginName', 'test@testnexigroup.com');
        $I->fillField('invadr[oxuser__oxfname]', 'John');
        $I->fillField('invadr[oxuser__oxlname]', 'Doe');
        $I->fillField('invadr[oxuser__oxstreet]', 'Maple Street');
        $I->fillField('invadr[oxuser__oxstreetnr]', '2425');
        $I->fillField('invadr[oxuser__oxzip]', '9041');
        $I->fillField('invadr[oxuser__oxcity]', 'Any City');
        $I->click("//button[@data-id='invCountrySelect']");
        $I->click('Germany');
        $I->seeCheckboxIsChecked('#showShipAddress');
    }

    public function selectNetsEasyPaymentMethod()
    {
        $I = $this;
        $I->see('3. Pay');
        $I->see('Payment method');
        $I->see('Nets Easy');
        $I->click("//label[contains(.,'Nets Easy')]");
    }

    public function goToOxidWebsite()
    {
        $I = $this;
        $oxidUrl = "https://oxidlocal.sokoni.it/eshop_6_5/source/";
        $I->amOnUrl($oxidUrl);
    }

    public function switchToNavigationAdminFrame()
    {
        $I = $this;
        $I->switchToFrame();
        $I->switchToFrame('navigation');
        $I->switchToFrame('adminnav');
    }

    public function switchToBaseListFrame()
    {
        $I = $this;
        $I->switchToFrame();
        $I->switchToFrame('basefrm');
        $I->switchToFrame('list');
    }

    public function switchToBaseEditFrame()
    {
        $I = $this;
        $I->switchToFrame();
        $I->switchToFrame('basefrm');
        $I->switchToFrame('edit');
    }
}
