<?php
use Page\Acceptance\AdminLogin;
use Codeception\Util\Locator;

class FirstCest
{

    public function checkThatFrontPageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Home');
        $I->see('Kiteboarding');
        $I->see('Gear');
        $I->see('Special offers');
        $I->see('Downloads');
        $I->see('Week\'s special');
        $I->see('Our brands');
        $I->see('Just arrived');
        $I->see('Top seller');
        $I->seeElement('#searchParam');
    }

    public function buyItemFromHomeByUsingNetsEasyPaymentTypeAndEmbeddedCheckoutTypeWithoutBeingLoggedIn(AcceptanceTester $I)
    {
        $I->wantToTest("if I can purchase the product using Nets Easy payment method if Embedded checkout type is set");
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->click("//b[normalize-space()='Extensions']");
        $I->click("//a[contains(.,'Modules')]");
        $I->switchToBaseListFrame();
        $I->click("//a[contains(text(),'Nets Easy')]");
        $I->wait(1);
        $I->waitForElementClickable("//a[normalize-space()='Settings']");
        $I->click("//a[normalize-space()='Settings']");
        $I->switchToBaseEditFrame();
        $I->waitForElementClickable("//a[contains(.,'Nets Easy settings')]");
        $I->click("//a[contains(.,'Nets Easy settings')]");
        $I->see('Nets Easy settings');
        $I->selectOption("select[name='confselects[nets_checkout_mode]']", 'Embedded');
        $I->click("//input[@type='submit'][@name='save']");
        $I->waitForElementNotVisible("select[name='confselects[nets_checkout_mode]']");
        $I->goToOxidWebsite();
        $I->click("//a[@id='bargainItems_1']/following::button[contains(@data-original-title,'To cart')][1]");
        $I->waitForText('1 Items in cart');
        $I->see('1 Items in cart');
        $total = $I->grabTextFrom("//tfoot/tr/td/strong[contains(@class,'price')]");
        $I->assertNotEquals('0,00 € *', $total, 'Total is 0,00 €!');
        $I->waitForElement("//a[contains(text(),'Display cart')]");
        $cartUrl = $I->grabAttributeFrom("//a[contains(text(),'Display cart')]", 'href');
        $I->amOnUrl($cartUrl);
        $I->see('1. Cart');
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->fillRequiredAddressData();
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->see('Please select your shipping method');
        $I->selectNetsEasyPaymentMethod();
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->see('4. Order');
        $I->see('Please check all data on this overview before submitting your order!');
        $I->seeElement("//h3[contains(@class,'card-title')][contains(.,'Nets Easy')]");
        $I->see('Billing address');
        $I->see('Shipping address');
        $I->see('Shipping method');
        $I->see('Payment method');
        $I->see('Cart');
        $I->fillPaymentCardRequiredData();
        $I->acceptPaymentTerms();
        $I->successfullyPay();
        $I->authorizePaymentInSimulator();
        $I->switchToFrame();
        $I->waitForElement('#thankyouPage > h3');
        $I->seeElement('#thankyouPage > h3');
        $I->seeElement("//b[contains(.,'Nets Payment ID')]");
        $orderText = $I->grabTextFrom('#thankyouPage');
        $trimmedTextOne = preg_replace('/\r|\n/', '', $orderText);
        $trimmedTextTwo = substr($trimmedTextOne, 135);
        $orderNumberFromText = preg_replace('~\D~', '', $trimmedTextTwo);
        $paymentIdText = $I->grabTextFrom('#thankyouPage > div');
        $paymentIdFromText = trim($paymentIdText, 'Nets Payment ID - ');
        $I->see('Thank you for ordering at OXID eShop 6.');
        $I->see('We registered your order with number');
        $I->see('You\'ve already received an e-mail with an order confirmation.');
        $I->see('We will inform you immediately if an item is not deliverable.');
        $I->see('Thank you');
        $I->see('back to start page');

        //check Order in Admin Panel
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->waitForElementClickable("//b[normalize-space()='Administer Orders']");
        $I->click("//b[normalize-space()='Administer Orders']");
        $I->click("//li[contains(@name,'nav_admin_order')]/a[contains(.,'Orders')]");
        $I->switchToBaseListFrame();
        $I->click("//*[@id='row.1']/td[3]/div/a[contains(.,'$orderNumberFromText')]");
        $I->wait(3);
        $I->switchToBaseEditFrame();
        $I->waitForElementVisible("//div[contains(@class,'nets-container')]");
        $textInPaymentIdFromOrder = $I->grabTextFrom("//div[contains(@class,'nets-header')]");
        $paymentIdFromOrder = trim($textInPaymentIdFromOrder, 'Payment ID : ');
        $I->assertEquals($paymentIdFromText, $paymentIdFromOrder);
    }

    public function buyItemFromHomeByUsingNetsEasyPaymentTypeAndHostedCheckoutTypeWithoutBeingLoggedIn(AcceptanceTester $I)
    {
        $I->wantToTest("if I can purchase the product using Nets Easy payment method if Hosted checkout type is set");
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->click("//b[normalize-space()='Extensions']");
        $I->click("//a[contains(.,'Modules')]");
        $I->switchToBaseListFrame();
        $I->click("//a[contains(text(),'Nets Easy')]");
        $I->wait(1);
        $I->waitForElementClickable("//a[normalize-space()='Settings']");
        $I->click("//a[normalize-space()='Settings']");
        $I->switchToBaseEditFrame();
        $I->waitForElementClickable("//a[contains(.,'Nets Easy settings')]");
        $I->click("//a[contains(.,'Nets Easy settings')]");
        $I->see('Nets Easy settings');
        $I->selectOption("select[name='confselects[nets_checkout_mode]']", 'Hosted');
        $I->click("//input[@type='submit'][@name='save']");
        $I->waitForElementNotVisible("select[name='confselects[nets_checkout_mode]']");
        $I->goToOxidWebsite();
        $I->click("//a[@id='bargainItems_1']/following::button[contains(@data-original-title,'To cart')][1]");
        $I->waitForText('1 Items in cart');
        $I->see('1 Items in cart');
        $total = $I->grabTextFrom("//tfoot/tr/td/strong[contains(@class,'price')]");
        $I->assertNotEquals('0,00 € *', $total, 'Total is 0,00 €!');
        $I->waitForElement("//a[contains(text(),'Display cart')]");
        $cartUrl = $I->grabAttributeFrom("//a[contains(text(),'Display cart')]", 'href');
        $I->amOnUrl($cartUrl);
        $I->see('1. Cart');
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->fillRequiredAddressData();
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->see('Please select your shipping method');
        $I->selectNetsEasyPaymentMethod();
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->see('4. Order');
        $I->see('Please check all data on this overview before submitting your order!');
        $I->see('Billing address');
        $I->see('Shipping address');
        $I->see('Shipping method');
        $I->see('Payment method');
        $I->see('Terms and Conditions and Right to Withdrawal');
        $I->see('Cart');
        $I->click('Order now');
        $I->seeInCurrentUrl('hostedpaymentpage');
        $I->seeInTitle('Easy Checkout Host');
        $I->fillPaymentCardRequiredData();
        $I->acceptPaymentTerms();
        $I->successfullyPay();
        $I->authorizePaymentInSimulator();
        $I->switchToFrame();
        $I->waitForElement('#thankyouPage > h3');
        $I->seeElement('#thankyouPage > h3');
        $I->seeElement("//b[contains(.,'Nets Payment ID')]");
        $orderText = $I->grabTextFrom('#thankyouPage');
        $trimmedTextOne = preg_replace('/\r|\n/', '', $orderText);
        $trimmedTextTwo = substr($trimmedTextOne, 135);
        $orderNumberFromText = preg_replace('~\D~', '', $trimmedTextTwo);
        $paymentIdText = $I->grabTextFrom('#thankyouPage > div');
        $paymentIdFromText = trim($paymentIdText, 'Nets Payment ID - ');
        $I->see('Thank you for ordering at OXID eShop 6.');
        $I->see('We registered your order with number');
        $I->see('You\'ve already received an e-mail with an order confirmation.');
        $I->see('We will inform you immediately if an item is not deliverable.');
        $I->see('Thank you');
        $I->see('back to start page');

        //check Order in Admin Panel
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->waitForElementClickable("//b[normalize-space()='Administer Orders']");
        $I->click("//b[normalize-space()='Administer Orders']");
        $I->click("//li[contains(@name,'nav_admin_order')]/a[contains(.,'Orders')]");
        $I->switchToBaseListFrame();
        $I->click("//*[@id='row.1']/td[3]/div/a[contains(.,'$orderNumberFromText')]");
        $I->wait(3);
        $I->switchToBaseEditFrame();
        $I->waitForElementVisible("//div[contains(@class,'nets-container')]");
        $textInPaymentIdFromOrder = $I->grabTextFrom("//div[contains(@class,'nets-header')]");
        $paymentIdFromOrder = trim($textInPaymentIdFromOrder, 'Payment ID : ');
        $I->assertEquals($paymentIdFromText, $paymentIdFromOrder);
    }

    public function buyItemFromSpecialOffersByUsingNetsEasyPaymentTypeAndHostedCheckoutTypeWithoutBeingLoggedIn(AcceptanceTester $I)
    {
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->click("//b[normalize-space()='Extensions']");
        $I->click("//a[contains(.,'Modules')]");
        $I->switchToBaseListFrame();
        $I->click("//a[contains(text(),'Nets Easy')]");
        $I->wait(1);
        $I->waitForElementClickable("//a[normalize-space()='Settings']");
        $I->click("//a[normalize-space()='Settings']");
        $I->switchToBaseEditFrame();
        $I->waitForElementClickable("//a[contains(.,'Nets Easy settings')]");
        $I->click("//a[contains(.,'Nets Easy settings')]");
        $I->see('Nets Easy settings');
        $I->selectOption("select[name='confselects[nets_checkout_mode]']", 'Embedded');
        $I->click("//input[@type='submit'][@name='save']");
        $I->waitForElementNotVisible("select[name='confselects[nets_checkout_mode]']");
        $I->goToOxidWebsite();
        $specialOffersUrl = $I->grabAttributeFrom("//a[contains(text(),'Special Offers')]", 'href');
        $I->amOnUrl($specialOffersUrl);
        $I->see('Special Offers');
        $I->waitForElementClickable(Locator::firstElement("//button[@data-original-title='To cart']"));
        $I->click(Locator::firstElement("//button[@data-original-title='To cart']"));
        $I->waitForText('1 Items in cart');
        $I->see('1 Items in cart');
        $total = $I->grabTextFrom("//tfoot/tr/td/strong[contains(@class,'price')]");
        $I->assertNotEquals('0,00 € *', $total, 'Total is 0,00 €!');
        $I->waitForElement("//a[contains(text(),'Display cart')]");
        $cartUrl = $I->grabAttributeFrom("//a[contains(text(),'Display cart')]", 'href');
        $I->amOnUrl($cartUrl);
        $I->see('1. Cart');
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->fillRequiredAddressData();
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->see('Please select your shipping method');
        $I->selectNetsEasyPaymentMethod();
        $I->click("//button[contains(.,'Continue to the next step')]");
        $I->see('4. Order');
        $I->see('Please check all data on this overview before submitting your order!');
        $I->seeElement("//h3[contains(@class,'card-title')][contains(.,'Nets Easy')]");
        $I->see('Billing address');
        $I->see('Shipping address');
        $I->see('Shipping method');
        $I->see('Payment method');
        $I->see('Cart');
        $I->fillPaymentCardRequiredData();
        $I->acceptPaymentTerms();
        $I->successfullyPay();
        $I->switchToFrame();
        $I->waitForElement('#thankyouPage > h3');
        $I->seeElement('#thankyouPage > h3');
        $I->seeElement("//b[contains(.,'Nets Payment ID')]");
        $orderText = $I->grabTextFrom('#thankyouPage');
        $trimmedTextOne = preg_replace('/\r|\n/', '', $orderText);
        $trimmedTextTwo = substr($trimmedTextOne, 135);
        $orderNumberFromText = preg_replace('~\D~', '', $trimmedTextTwo);
        $paymentIdText = $I->grabTextFrom('#thankyouPage > div');
        $paymentIdFromText = trim($paymentIdText, 'Nets Payment ID - ');
        $I->see('Thank you for ordering at OXID eShop 6.');
        $I->see('We registered your order with number');
        $I->see('You\'ve already received an e-mail with an order confirmation.');
        $I->see('We will inform you immediately if an item is not deliverable.');
        $I->see('Thank you');
        $I->see('back to start page');

        //check Order in Admin Panel
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->waitForElementClickable("//b[normalize-space()='Administer Orders']");
        $I->click("//b[normalize-space()='Administer Orders']");
        $I->click("//li[contains(@name,'nav_admin_order')]/a[contains(.,'Orders')]");
        $I->switchToBaseListFrame();
        $I->click("//*[@id='row.1']/td[3]/div/a[contains(.,'$orderNumberFromText')]");
        $I->wait(3);
        $I->switchToBaseEditFrame();
        $I->waitForElementVisible("//div[contains(@class,'nets-container')]");
        $textInPaymentIdFromOrder = $I->grabTextFrom("//div[contains(@class,'nets-header')]");
        $paymentIdFromOrder = trim($textInPaymentIdFromOrder, 'Payment ID : ');
        $I->assertEquals($paymentIdFromText, $paymentIdFromOrder);
    }

    public function checkThatAdminPageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/admin');
        $I->seeElement(AdminLogin::$usernameField);
        $I->seeElement(AdminLogin::$passwordField);
        $I->seeElement(AdminLogin::$submitButton);
    }

    public function loginToOxidAdminPanelAndSelectNetsEasyModuleInExtensions(AcceptanceTester $I)
    {
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->click("//b[normalize-space()='Extensions']");
        $I->click("//a[contains(.,'Modules')]");
        $I->switchToBaseListFrame();
        $I->click("//a[contains(text(),'Nets Easy')]");
        $I->switchToBaseEditFrame();
        $I->see('Nets safe online payments', 'p');
        $I->see('Nets Easy', 'h1');
        $I->seeElement("//dl[@class='moduleDesc clear']");
        $I->seeElement("//input[@type='submit']");
        $I->seeElement("//img[contains(@src,'nets_logo.png')]");
    }

    public function loginToOxidAdminPanelAndSelectNetsEasyPaymentMethodInShopSettings(AcceptanceTester $I)
    {
        $I->loginToAdminPanel();
        $I->switchToNavigationAdminFrame();
        $I->click("//b[normalize-space()='Shop Settings']");
        $I->click("//a[contains(.,'Payment Methods')]");
        $I->switchToBaseListFrame();
        $I->click("//a[contains(text(),'Nets Easy')]");
        $I->switchToBaseEditFrame();
        $I->waitForElementVisible("//input[contains(@value,'Nets Easy')]");
        $I->seeElement("//input[contains(@value,'Nets Easy')]");
    }
}