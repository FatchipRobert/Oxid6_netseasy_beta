<?php

/**
 * Nets Oxid Payment module metadata
 *
 * @version 2.0.0
 * @package Nets
 * @copyright nets
 */
/**
 * Metadata version
 */
$sMetadataVersion = '2.1';

/**
 * Module information
 */
$aModule = [
    'id' => 'esnetseasy',
    'title' => 'Nets Easy',
    'version' => '2.0.0',
    'author' => 'Nets eCom',
    'url' => 'http://www.nets.eu',
    'email' => 'https://www.nets.eu/contact-nets/Pages/Customer-service.aspx',
    'thumbnail' => 'out/src/img/nets_logo.png',
    'description' => [
        'de' => 'Nets einfach sicher zahlen',
        'en' => 'Nets safe online payments'
    ],
    'controllers' => [
        'nets_order_overview' => \Es\NetsEasy\ShopExtend\Application\Controller\Admin\OrderOverviewController::class
    ],
    'extend' => [
        \OxidEsales\Eshop\Application\Controller\Admin\OrderOverview::class => \Es\NetsEasy\ShopExtend\Application\Controller\Admin\OrderOverviewController::class,
        \OxidEsales\Eshop\Application\Controller\OrderController::class => \Es\NetsEasy\ShopExtend\Application\Controller\OrderController::class,
        \OxidEsales\Eshop\Application\Controller\PaymentController::class => \Es\NetsEasy\ShopExtend\Application\Controller\PaymentController::class,
        \OxidEsales\Eshop\Application\Controller\ThankYouController::class => \Es\NetsEasy\ShopExtend\Application\Controller\ThankyouController::class,
    ],
    'blocks' => [
        [
            'template' => 'order_overview.tpl',
            'block' => 'admin_order_overview_export',
            'file' => 'views/blocks/admin_order_overview_export.tpl'
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => 'views/blocks/page/checkout/payment/select_payment.tpl'
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'shippingAndPayment',
            'file' => 'views/blocks/page/checkout/order/shippingAndPayment.tpl'
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'checkout_order_errors',
            'file' => 'views/blocks/page/checkout/order/checkout_order_errors.tpl'
        ],
        [
            'template' => 'page/checkout/thankyou.tpl',
            'block' => 'checkout_thankyou_info',
            'file' => 'views/blocks/page/checkout/thankyou/checkout_thankyou_info.tpl'
        ]
    ],
    'settings' => [
        [
            'group' => 'nets_main',
            'name' => 'nets_blMode',
            'type' => 'select',
            'value' => '0',
            'constraints' => '0|1'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_secret_key_live',
            'type' => 'str',
            'value' => ''
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_checkout_key_live',
            'type' => 'str',
            'value' => ''
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_secret_key_test',
            'type' => 'str',
            'value' => ''
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_checkout_key_test',
            'type' => 'str',
            'value' => ''
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_terms_url',
            'type' => 'str',
            'value' => 'https://mysite.com/index.php?cl=content&oxloadid=oxagb'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_merchant_terms_url',
            'type' => 'str',
            'value' => 'https://cdn.dibspayment.com/terms/easy/terms_of_use.pdf'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_payment_url',
            'type' => 'str',
            'value' => 'http://easymoduler.dk/icon/img?set=2&icons=VISA_MC_MTRO_PP_RP'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_checkout_mode',
            'type' => 'select',
            'value' => 'hosted',
            'constraints' => 'embedded|hosted'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_layout_mode',
            'type' => 'select',
            'value' => 'layout_1',
            'constraints' => 'layout_1|layout_2'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_autocapture',
            'type' => 'bool',
            'value' => 'false'
        ],
        [
            'group' => 'nets_main',
            'name' => 'nets_blDebug_log',
            'type' => 'bool',
            'value' => 'false'
        ]
    ],
    'templates' => [],
    'events' => [
        'onActivate' => '\Es\NetsEasy\Core\Events::onActivate',
        'onDeactivate' => '\Es\NetsEasy\Core\Events::onDeactivate'
    ]
];
