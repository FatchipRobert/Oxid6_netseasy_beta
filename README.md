# NETS A/S - Oxid 6 Payment Module - BETA VERSION
==============================================================

|Module       | Nets Easy Payment Module for Oxid 6
|-------------|-----------------------------------------------
|Author       | `Nets eCom`
|Prefix       | `EASY-OX6`
|Shop Version | `6+`
|Version      | `2.0.0`
|Guide        | https://tech.nets.eu/shopmodules
|Github       | https://github.com/Nets-eCom/oxid6_netseasy_beta

### :memo: *NOTE* :
##### 1. After version update, we advise to deactivate and reactivate extension again.
##### 2. This version upgrade contains significant changes that can prevent charges/refunds on past transactions. Shall you experience any issue with charges/refunds from Oxid Admin Panel, we advise to proceed to charges/refunds directly from the Easy portal.

## INSTALLATION
* See our user guide with illustrations in /nets/docs/Oxid_guide_Nets.pdf

### Download / Installation
1] Install Nets Plugin:

    Run command: composer require nets-mehabub/ox6_metadata

    Add below lines of code in composer.json file

    "psr-4": {
                "Es\\NetsEasy\\":"./source/modules/es/esnetseasy"
            }

    Run command: composer dump-autoload

2] Activate the Nets module:

    vendor/bin/oe-console oe:module:install .\source\modules\es\esnetseasy
    vendor/bin/oe-console oe:module:activate esnetseasy
    vendor/bin/oe-console oe:module:install-configuration .\source\modules\es\esnetseasy
    vendor/bin/oe-console oe:module:apply-configuration

3] Initialize Codeception:

    vendor/bin/codecept bootstrap

4] To execute codeception on correct test folder: add or update below lines of code in codeception.yml file.

    paths:
        test:source/modules/es/esnetseasy/Tests
    coverage:
        enabled: true
        includeUncoveredFiles: false
        include:
            - source/modules/es/esnetseasy/extend/*
            - source/modules/es/esnetseasy/Core/*
            - source/modules/es/esnetseasy/Api/*
        exclude:
            - source/modules/es/esnetseasy/Tests/*

5] Update below lines in test_config.php

	shop_tests_path: source/modules/es/esnetseasy/Tests
    	partial_module_paths: source/modules/es/esnetseasy/Tests

6] Add xDebug driver to run code coverage

    Follow instructions from xDebug documentation
    https://xdebug.org/docs/install

7] Generate code coverage report by executing below command.

    .\vendor\bin\codecept run --coverage --coverage-xml --coverage-html
    
8] Congratulation code coverage looks like below.
    ![Alt text](code-coverage-report.JPG?raw=true "Title")
    
    
### Configuration
1. To configure and setup the plugin navigate to : Admin > Extensions > Modules
2. Locate and select Nets Easy plugin from the list of installed plugins.
3. Select the Overview tab and press the Activate button. If the Nets Easy has been installed correct you will now see a green checked circle under active column next Nets Easy on your list.
4. To activate your new payment method and setup the plugin for your shop navigate to :
   Admin > Shop Settings > Payment Methods
5. Payment methods are listed in alphabetical order on multiple pages. Find or search for Nets Easy and select.
6. On Main tab make sure to check the Active box.
   NOTE : Name and Payment Description input fields can be used for a customized description of the Nets Easy plugin to your customers on selection of payment methods.
7. Once assigned User Groups and Countries and translations if needed then remember to Save.
8. Navigate back to the plugin settings :
   Admin > Extensions > Modules > Nets Easy
9. Select the Settings tab and press on Nets Easy settings to reveal the content of configuration settings.

* Nets plugin configuration settings
1. Mode. Select between Test/Live transactions. Live mode requires an approved account.
   Testcard information can be found here : https://tech.dibspayment.com/easy/test-information
2. Test / Live keys. Login to your Nets Easy account. Keys can be found in Company > Integration : https://portal.dibspayment.eu/
3. Terms Url. Set the url for your Terms and Conditions page.
4. Merchant Terms Url. Set the url for your Terms of use and Cookies page.
   NOTE : Term Links can be found inside the Nets Easy payment window.
5. Icons bar url. Set and customize icons listing by editing directly in parameters in the url or visit our custom Icons url generator : https://easymoduler.dk/icon/
6. Checkout Type. Hosted / Embedded. Select between 2 checkout types. Hosted - Nets Hosted loads a new payment page. Embedded checkout inserts the payment window directly on the checkout page.
7. Embedded Checkout Layout. Layout 1 / Layout 2. Select between 2 layouts for your Embedded checkout
8. Auto-capture. This function allows you to instantly charge a payment straight after the order is placed.
   NOTE. Capturing a payment before shipment of the order might be liable to restrictions based upon legislations set in your country. Misuse can result in your Easy account being forfeit.
9. Debug. This function will reveal raw api data that you can copy / paste in case you experience errors on your transactions. Data will be visible in order details. This is intended to be used when contacting Nets support.

### Operations
* cancel / capture / refund - Partial capture/refund
1. Navigate to admin > Administer Orders > Orders. Select an Order payed through Nets Easy.
2. Choose your desired function :
   - Fully cancel / charge / refund your order.
   - Partially charge / refund your order.
   NOTE :
	- Partial functionality handles items in your order as blocks. Custom amount is not possible.
	- Use -/+ to adjust quantity for partial functionality.
	- Press partial button to execute action per line.
3. All transactions by Nets are accessible in our portal : https://portal.dibspayment.eu/login
4. Payment status is real-time and updated in Order details even if you make the changes in Nets Easy Portal.

### Troubleshooting
* Nets payment plugin is not visible as a payment method
- Ensure the Nets plugin is activated in Shop Settings and in the Extensions plugin configuration.
- Ensure the Nets plugin is assigned in available listings in various User Groups and Countries.
- Various User Groups, Countries and Payment Methods setup listings can be found in Admin > Shop Settings :
  Payment Methods and Shipping Methods

* Nets payment window is blank
- Ensure your keys in Nets plugin Settings are correct and with no additional blank spaces.
- Temporarily deactivate 3.rd party plugins that might effect the functionality of the Nets plugin.
- Check if there is any temporary technical inconsistencies : https://nets.eu/Pages/operational-status.aspx

* Payments in live mode dont work
- Ensure you have an approved Live Easy account for production.
- Ensure your Live Easy account is approved for payments with selected currency.
- Ensure payment method data is correct and supported by your Nets Easy agreement.

* How do I choose currency or language in the payment window?
- Currency and Language is based on customers selection on the frontend shop page.
- A complete list of supported currencies and languages can be found here :
  https://tech.dibspayment.com/easy/integration-guide

### Contact
* Nets customer service
- Nets Easy provides support for both test and live Easy accounts. Contact information can be found here : https://nets.eu/en/payments/customerservice/

** CREATE YOUR FREE NETS EASY TEST ACCOUNT HERE : https://portal.dibspayment.eu/registration **
