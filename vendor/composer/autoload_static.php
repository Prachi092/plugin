<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitae2861788e60bd62334e07b9bce7b69a
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static $classMap = array (
        'Stripe\\Account' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Account.php',
        'Stripe\\AccountLink' => __DIR__ . '/..' . '/stripe/stripe-php/lib/AccountLink.php',
        'Stripe\\AlipayAccount' => __DIR__ . '/..' . '/stripe/stripe-php/lib/AlipayAccount.php',
        'Stripe\\ApiOperations\\All' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/All.php',
        'Stripe\\ApiOperations\\Create' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/Create.php',
        'Stripe\\ApiOperations\\Delete' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/Delete.php',
        'Stripe\\ApiOperations\\NestedResource' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/NestedResource.php',
        'Stripe\\ApiOperations\\Request' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/Request.php',
        'Stripe\\ApiOperations\\Retrieve' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/Retrieve.php',
        'Stripe\\ApiOperations\\Update' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiOperations/Update.php',
        'Stripe\\ApiRequestor' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiRequestor.php',
        'Stripe\\ApiResource' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiResource.php',
        'Stripe\\ApiResponse' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApiResponse.php',
        'Stripe\\ApplePayDomain' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApplePayDomain.php',
        'Stripe\\ApplicationFee' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApplicationFee.php',
        'Stripe\\ApplicationFeeRefund' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ApplicationFeeRefund.php',
        'Stripe\\Balance' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Balance.php',
        'Stripe\\BalanceTransaction' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BalanceTransaction.php',
        'Stripe\\BankAccount' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BankAccount.php',
        'Stripe\\BaseStripeClient' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BaseStripeClient.php',
        'Stripe\\BaseStripeClientInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BaseStripeClientInterface.php',
        'Stripe\\BillingPortal\\Configuration' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BillingPortal/Configuration.php',
        'Stripe\\BillingPortal\\Session' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BillingPortal/Session.php',
        'Stripe\\BitcoinReceiver' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BitcoinReceiver.php',
        'Stripe\\BitcoinTransaction' => __DIR__ . '/..' . '/stripe/stripe-php/lib/BitcoinTransaction.php',
        'Stripe\\Capability' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Capability.php',
        'Stripe\\Card' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Card.php',
        'Stripe\\Charge' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Charge.php',
        'Stripe\\Checkout\\Session' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Checkout/Session.php',
        'Stripe\\Collection' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Collection.php',
        'Stripe\\CountrySpec' => __DIR__ . '/..' . '/stripe/stripe-php/lib/CountrySpec.php',
        'Stripe\\Coupon' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Coupon.php',
        'Stripe\\CreditNote' => __DIR__ . '/..' . '/stripe/stripe-php/lib/CreditNote.php',
        'Stripe\\CreditNoteLineItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/CreditNoteLineItem.php',
        'Stripe\\Customer' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Customer.php',
        'Stripe\\CustomerBalanceTransaction' => __DIR__ . '/..' . '/stripe/stripe-php/lib/CustomerBalanceTransaction.php',
        'Stripe\\Discount' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Discount.php',
        'Stripe\\Dispute' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Dispute.php',
        'Stripe\\EphemeralKey' => __DIR__ . '/..' . '/stripe/stripe-php/lib/EphemeralKey.php',
        'Stripe\\ErrorObject' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ErrorObject.php',
        'Stripe\\Event' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Event.php',
        'Stripe\\Exception\\ApiConnectionException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/ApiConnectionException.php',
        'Stripe\\Exception\\ApiErrorException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/ApiErrorException.php',
        'Stripe\\Exception\\AuthenticationException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/AuthenticationException.php',
        'Stripe\\Exception\\BadMethodCallException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/BadMethodCallException.php',
        'Stripe\\Exception\\CardException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/CardException.php',
        'Stripe\\Exception\\ExceptionInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/ExceptionInterface.php',
        'Stripe\\Exception\\IdempotencyException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/IdempotencyException.php',
        'Stripe\\Exception\\InvalidArgumentException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/InvalidArgumentException.php',
        'Stripe\\Exception\\InvalidRequestException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/InvalidRequestException.php',
        'Stripe\\Exception\\OAuth\\ExceptionInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/ExceptionInterface.php',
        'Stripe\\Exception\\OAuth\\InvalidClientException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/InvalidClientException.php',
        'Stripe\\Exception\\OAuth\\InvalidGrantException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/InvalidGrantException.php',
        'Stripe\\Exception\\OAuth\\InvalidRequestException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/InvalidRequestException.php',
        'Stripe\\Exception\\OAuth\\InvalidScopeException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/InvalidScopeException.php',
        'Stripe\\Exception\\OAuth\\OAuthErrorException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/OAuthErrorException.php',
        'Stripe\\Exception\\OAuth\\UnknownOAuthErrorException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/UnknownOAuthErrorException.php',
        'Stripe\\Exception\\OAuth\\UnsupportedGrantTypeException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/UnsupportedGrantTypeException.php',
        'Stripe\\Exception\\OAuth\\UnsupportedResponseTypeException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/OAuth/UnsupportedResponseTypeException.php',
        'Stripe\\Exception\\PermissionException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/PermissionException.php',
        'Stripe\\Exception\\RateLimitException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/RateLimitException.php',
        'Stripe\\Exception\\SignatureVerificationException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/SignatureVerificationException.php',
        'Stripe\\Exception\\UnexpectedValueException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/UnexpectedValueException.php',
        'Stripe\\Exception\\UnknownApiErrorException' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Exception/UnknownApiErrorException.php',
        'Stripe\\ExchangeRate' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ExchangeRate.php',
        'Stripe\\File' => __DIR__ . '/..' . '/stripe/stripe-php/lib/File.php',
        'Stripe\\FileLink' => __DIR__ . '/..' . '/stripe/stripe-php/lib/FileLink.php',
        'Stripe\\HttpClient\\ClientInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/HttpClient/ClientInterface.php',
        'Stripe\\HttpClient\\CurlClient' => __DIR__ . '/..' . '/stripe/stripe-php/lib/HttpClient/CurlClient.php',
        'Stripe\\HttpClient\\StreamingClientInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/HttpClient/StreamingClientInterface.php',
        'Stripe\\Identity\\VerificationReport' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Identity/VerificationReport.php',
        'Stripe\\Identity\\VerificationSession' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Identity/VerificationSession.php',
        'Stripe\\Invoice' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Invoice.php',
        'Stripe\\InvoiceItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/InvoiceItem.php',
        'Stripe\\InvoiceLineItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/InvoiceLineItem.php',
        'Stripe\\Issuing\\Authorization' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Issuing/Authorization.php',
        'Stripe\\Issuing\\Card' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Issuing/Card.php',
        'Stripe\\Issuing\\CardDetails' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Issuing/CardDetails.php',
        'Stripe\\Issuing\\Cardholder' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Issuing/Cardholder.php',
        'Stripe\\Issuing\\Dispute' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Issuing/Dispute.php',
        'Stripe\\Issuing\\Transaction' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Issuing/Transaction.php',
        'Stripe\\LineItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/LineItem.php',
        'Stripe\\LoginLink' => __DIR__ . '/..' . '/stripe/stripe-php/lib/LoginLink.php',
        'Stripe\\Mandate' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Mandate.php',
        'Stripe\\OAuth' => __DIR__ . '/..' . '/stripe/stripe-php/lib/OAuth.php',
        'Stripe\\OAuthErrorObject' => __DIR__ . '/..' . '/stripe/stripe-php/lib/OAuthErrorObject.php',
        'Stripe\\Order' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Order.php',
        'Stripe\\OrderItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/OrderItem.php',
        'Stripe\\OrderReturn' => __DIR__ . '/..' . '/stripe/stripe-php/lib/OrderReturn.php',
        'Stripe\\PaymentIntent' => __DIR__ . '/..' . '/stripe/stripe-php/lib/PaymentIntent.php',
        'Stripe\\PaymentMethod' => __DIR__ . '/..' . '/stripe/stripe-php/lib/PaymentMethod.php',
        'Stripe\\Payout' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Payout.php',
        'Stripe\\Person' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Person.php',
        'Stripe\\Plan' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Plan.php',
        'Stripe\\Price' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Price.php',
        'Stripe\\Product' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Product.php',
        'Stripe\\PromotionCode' => __DIR__ . '/..' . '/stripe/stripe-php/lib/PromotionCode.php',
        'Stripe\\Quote' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Quote.php',
        'Stripe\\Radar\\EarlyFraudWarning' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Radar/EarlyFraudWarning.php',
        'Stripe\\Radar\\ValueList' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Radar/ValueList.php',
        'Stripe\\Radar\\ValueListItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Radar/ValueListItem.php',
        'Stripe\\Recipient' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Recipient.php',
        'Stripe\\RecipientTransfer' => __DIR__ . '/..' . '/stripe/stripe-php/lib/RecipientTransfer.php',
        'Stripe\\Refund' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Refund.php',
        'Stripe\\Reporting\\ReportRun' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Reporting/ReportRun.php',
        'Stripe\\Reporting\\ReportType' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Reporting/ReportType.php',
        'Stripe\\RequestTelemetry' => __DIR__ . '/..' . '/stripe/stripe-php/lib/RequestTelemetry.php',
        'Stripe\\Review' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Review.php',
        'Stripe\\SKU' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SKU.php',
        'Stripe\\Service\\AbstractService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/AbstractService.php',
        'Stripe\\Service\\AbstractServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/AbstractServiceFactory.php',
        'Stripe\\Service\\AccountLinkService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/AccountLinkService.php',
        'Stripe\\Service\\AccountService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/AccountService.php',
        'Stripe\\Service\\ApplePayDomainService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/ApplePayDomainService.php',
        'Stripe\\Service\\ApplicationFeeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/ApplicationFeeService.php',
        'Stripe\\Service\\BalanceService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/BalanceService.php',
        'Stripe\\Service\\BalanceTransactionService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/BalanceTransactionService.php',
        'Stripe\\Service\\BillingPortal\\BillingPortalServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/BillingPortal/BillingPortalServiceFactory.php',
        'Stripe\\Service\\BillingPortal\\ConfigurationService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/BillingPortal/ConfigurationService.php',
        'Stripe\\Service\\BillingPortal\\SessionService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/BillingPortal/SessionService.php',
        'Stripe\\Service\\ChargeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/ChargeService.php',
        'Stripe\\Service\\Checkout\\CheckoutServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Checkout/CheckoutServiceFactory.php',
        'Stripe\\Service\\Checkout\\SessionService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Checkout/SessionService.php',
        'Stripe\\Service\\CoreServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/CoreServiceFactory.php',
        'Stripe\\Service\\CountrySpecService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/CountrySpecService.php',
        'Stripe\\Service\\CouponService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/CouponService.php',
        'Stripe\\Service\\CreditNoteService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/CreditNoteService.php',
        'Stripe\\Service\\CustomerService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/CustomerService.php',
        'Stripe\\Service\\DisputeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/DisputeService.php',
        'Stripe\\Service\\EphemeralKeyService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/EphemeralKeyService.php',
        'Stripe\\Service\\EventService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/EventService.php',
        'Stripe\\Service\\ExchangeRateService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/ExchangeRateService.php',
        'Stripe\\Service\\FileLinkService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/FileLinkService.php',
        'Stripe\\Service\\FileService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/FileService.php',
        'Stripe\\Service\\Identity\\IdentityServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Identity/IdentityServiceFactory.php',
        'Stripe\\Service\\Identity\\VerificationReportService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Identity/VerificationReportService.php',
        'Stripe\\Service\\Identity\\VerificationSessionService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Identity/VerificationSessionService.php',
        'Stripe\\Service\\InvoiceItemService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/InvoiceItemService.php',
        'Stripe\\Service\\InvoiceService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/InvoiceService.php',
        'Stripe\\Service\\Issuing\\AuthorizationService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Issuing/AuthorizationService.php',
        'Stripe\\Service\\Issuing\\CardService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Issuing/CardService.php',
        'Stripe\\Service\\Issuing\\CardholderService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Issuing/CardholderService.php',
        'Stripe\\Service\\Issuing\\DisputeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Issuing/DisputeService.php',
        'Stripe\\Service\\Issuing\\IssuingServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Issuing/IssuingServiceFactory.php',
        'Stripe\\Service\\Issuing\\TransactionService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Issuing/TransactionService.php',
        'Stripe\\Service\\MandateService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/MandateService.php',
        'Stripe\\Service\\OAuthService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/OAuthService.php',
        'Stripe\\Service\\OrderReturnService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/OrderReturnService.php',
        'Stripe\\Service\\OrderService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/OrderService.php',
        'Stripe\\Service\\PaymentIntentService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/PaymentIntentService.php',
        'Stripe\\Service\\PaymentMethodService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/PaymentMethodService.php',
        'Stripe\\Service\\PayoutService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/PayoutService.php',
        'Stripe\\Service\\PlanService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/PlanService.php',
        'Stripe\\Service\\PriceService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/PriceService.php',
        'Stripe\\Service\\ProductService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/ProductService.php',
        'Stripe\\Service\\PromotionCodeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/PromotionCodeService.php',
        'Stripe\\Service\\QuoteService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/QuoteService.php',
        'Stripe\\Service\\Radar\\EarlyFraudWarningService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Radar/EarlyFraudWarningService.php',
        'Stripe\\Service\\Radar\\RadarServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Radar/RadarServiceFactory.php',
        'Stripe\\Service\\Radar\\ValueListItemService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Radar/ValueListItemService.php',
        'Stripe\\Service\\Radar\\ValueListService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Radar/ValueListService.php',
        'Stripe\\Service\\RefundService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/RefundService.php',
        'Stripe\\Service\\Reporting\\ReportRunService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Reporting/ReportRunService.php',
        'Stripe\\Service\\Reporting\\ReportTypeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Reporting/ReportTypeService.php',
        'Stripe\\Service\\Reporting\\ReportingServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Reporting/ReportingServiceFactory.php',
        'Stripe\\Service\\ReviewService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/ReviewService.php',
        'Stripe\\Service\\SetupAttemptService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SetupAttemptService.php',
        'Stripe\\Service\\SetupIntentService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SetupIntentService.php',
        'Stripe\\Service\\Sigma\\ScheduledQueryRunService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Sigma/ScheduledQueryRunService.php',
        'Stripe\\Service\\Sigma\\SigmaServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Sigma/SigmaServiceFactory.php',
        'Stripe\\Service\\SkuService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SkuService.php',
        'Stripe\\Service\\SourceService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SourceService.php',
        'Stripe\\Service\\SubscriptionItemService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SubscriptionItemService.php',
        'Stripe\\Service\\SubscriptionScheduleService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SubscriptionScheduleService.php',
        'Stripe\\Service\\SubscriptionService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/SubscriptionService.php',
        'Stripe\\Service\\TaxCodeService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/TaxCodeService.php',
        'Stripe\\Service\\TaxRateService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/TaxRateService.php',
        'Stripe\\Service\\Terminal\\ConnectionTokenService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Terminal/ConnectionTokenService.php',
        'Stripe\\Service\\Terminal\\LocationService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Terminal/LocationService.php',
        'Stripe\\Service\\Terminal\\ReaderService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Terminal/ReaderService.php',
        'Stripe\\Service\\Terminal\\TerminalServiceFactory' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/Terminal/TerminalServiceFactory.php',
        'Stripe\\Service\\TokenService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/TokenService.php',
        'Stripe\\Service\\TopupService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/TopupService.php',
        'Stripe\\Service\\TransferService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/TransferService.php',
        'Stripe\\Service\\WebhookEndpointService' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Service/WebhookEndpointService.php',
        'Stripe\\SetupAttempt' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SetupAttempt.php',
        'Stripe\\SetupIntent' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SetupIntent.php',
        'Stripe\\Sigma\\ScheduledQueryRun' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Sigma/ScheduledQueryRun.php',
        'Stripe\\SingletonApiResource' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SingletonApiResource.php',
        'Stripe\\Source' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Source.php',
        'Stripe\\SourceTransaction' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SourceTransaction.php',
        'Stripe\\Stripe' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Stripe.php',
        'Stripe\\StripeClient' => __DIR__ . '/..' . '/stripe/stripe-php/lib/StripeClient.php',
        'Stripe\\StripeClientInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/StripeClientInterface.php',
        'Stripe\\StripeObject' => __DIR__ . '/..' . '/stripe/stripe-php/lib/StripeObject.php',
        'Stripe\\StripeStreamingClientInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/StripeStreamingClientInterface.php',
        'Stripe\\Subscription' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Subscription.php',
        'Stripe\\SubscriptionItem' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SubscriptionItem.php',
        'Stripe\\SubscriptionSchedule' => __DIR__ . '/..' . '/stripe/stripe-php/lib/SubscriptionSchedule.php',
        'Stripe\\TaxCode' => __DIR__ . '/..' . '/stripe/stripe-php/lib/TaxCode.php',
        'Stripe\\TaxId' => __DIR__ . '/..' . '/stripe/stripe-php/lib/TaxId.php',
        'Stripe\\TaxRate' => __DIR__ . '/..' . '/stripe/stripe-php/lib/TaxRate.php',
        'Stripe\\Terminal\\ConnectionToken' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Terminal/ConnectionToken.php',
        'Stripe\\Terminal\\Location' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Terminal/Location.php',
        'Stripe\\Terminal\\Reader' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Terminal/Reader.php',
        'Stripe\\ThreeDSecure' => __DIR__ . '/..' . '/stripe/stripe-php/lib/ThreeDSecure.php',
        'Stripe\\Token' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Token.php',
        'Stripe\\Topup' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Topup.php',
        'Stripe\\Transfer' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Transfer.php',
        'Stripe\\TransferReversal' => __DIR__ . '/..' . '/stripe/stripe-php/lib/TransferReversal.php',
        'Stripe\\UsageRecord' => __DIR__ . '/..' . '/stripe/stripe-php/lib/UsageRecord.php',
        'Stripe\\UsageRecordSummary' => __DIR__ . '/..' . '/stripe/stripe-php/lib/UsageRecordSummary.php',
        'Stripe\\Util\\CaseInsensitiveArray' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/CaseInsensitiveArray.php',
        'Stripe\\Util\\DefaultLogger' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/DefaultLogger.php',
        'Stripe\\Util\\LoggerInterface' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/LoggerInterface.php',
        'Stripe\\Util\\ObjectTypes' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/ObjectTypes.php',
        'Stripe\\Util\\RandomGenerator' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/RandomGenerator.php',
        'Stripe\\Util\\RequestOptions' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/RequestOptions.php',
        'Stripe\\Util\\Set' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/Set.php',
        'Stripe\\Util\\Util' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Util/Util.php',
        'Stripe\\Webhook' => __DIR__ . '/..' . '/stripe/stripe-php/lib/Webhook.php',
        'Stripe\\WebhookEndpoint' => __DIR__ . '/..' . '/stripe/stripe-php/lib/WebhookEndpoint.php',
        'Stripe\\WebhookSignature' => __DIR__ . '/..' . '/stripe/stripe-php/lib/WebhookSignature.php',
        'WCV_Admin_New_Refund_Request_Email' => __DIR__ . '/../..' . '/includes/refund/emails/class-wcv-admin-new-refund-request-email.php',
        'WCV_SC_Ajax' => __DIR__ . '/../..' . '/includes/class-wcv-sc-ajax.php',
        'WCV_SC_Connect_API' => __DIR__ . '/../..' . '/includes/class-connect-api.php',
        'WCV_SC_Customer' => __DIR__ . '/../..' . '/includes/class-customer.php',
        'WCV_SC_Exception' => __DIR__ . '/../..' . '/includes/class-exception.php',
        'WCV_SC_Gateway_Stripe_Bancontact' => __DIR__ . '/../..' . '/includes/class-gateway-stripe-bancontact.php',
        'WCV_SC_Gateway_Stripe_Connect' => __DIR__ . '/../..' . '/includes/class-gateway-stripe-connect.php',
        'WCV_SC_Gateway_Stripe_Connect_Subs' => __DIR__ . '/../..' . '/includes/class-gateway-stripe-connect-subs.php',
        'WCV_SC_Helper' => __DIR__ . '/../..' . '/includes/class-helper.php',
        'WCV_SC_Intent_Controller' => __DIR__ . '/../..' . '/includes/class-intent-controller.php',
        'WCV_SC_Logger' => __DIR__ . '/../..' . '/includes/class-logger.php',
        'WCV_SC_Meta_Box_Refund_Request_Data' => __DIR__ . '/../..' . '/includes/refund/meta-boxes/class-meta-box-refund-request-data.php',
        'WCV_SC_Order_Handler' => __DIR__ . '/../..' . '/includes/class-order-handler.php',
        'WCV_SC_Payment_Gateway' => __DIR__ . '/../..' . '/includes/abstracts/abstract-wcv-sc-payment-gateway.php',
        'WCV_SC_Payment_Tokens' => __DIR__ . '/../..' . '/includes/class-payment-tokens.php',
        'WCV_SC_Refund' => __DIR__ . '/../..' . '/includes/refund/class-refund.php',
        'WCV_SC_Refund_Emails' => __DIR__ . '/../..' . '/includes/refund/class-refund-emails.php',
        'WCV_SC_Refund_Helpers' => __DIR__ . '/../..' . '/includes/refund/class-refund-helpers.php',
        'WCV_SC_Refund_Request' => __DIR__ . '/../..' . '/includes/refund/class-refund-request.php',
        'WCV_SC_Refund_Request_Admin_Meta_Boxes' => __DIR__ . '/../..' . '/includes/refund/class-refund-request-admin-meta-boxes.php',
        'WCV_SC_Refund_Request_Admin_Post_Type' => __DIR__ . '/../..' . '/includes/refund/class-refund-request-admin-post-type.php',
        'WCV_SC_Refund_Request_Controller' => __DIR__ . '/../..' . '/includes/refund/class-refund-request-controller.php',
        'WCV_SC_Refund_Request_Dashboard' => __DIR__ . '/../..' . '/includes/refund/class-refund-request-dashboard.php',
        'WCV_SC_Refund_Request_Data_Store_CPT' => __DIR__ . '/../..' . '/includes/refund/data-stores/class-wcv-sc-refund-request-data-store-cpt.php',
        'WCV_SC_Users' => __DIR__ . '/../..' . '/includes/class-users.php',
        'WCV_SC_Vendor_Dashboard' => __DIR__ . '/../..' . '/includes/class-vendor-dashboard.php',
        'WCV_SC_WooCommerce_Myaccount' => __DIR__ . '/../..' . '/includes/class-woocommerce-myaccount.php',
        'WCV_Vendor_Refund_Request_Approved_Email' => __DIR__ . '/../..' . '/includes/refund/emails/class-wcv-vendor-refund-request-approved-email.php',
        'WCV_Vendor_Refund_Request_Rejected_Email' => __DIR__ . '/../..' . '/includes/refund/emails/class-wcv-vendor-refund-request-rejected-email.php',
        'WC_Software_License_Client' => __DIR__ . '/../..' . '/includes/lib/class-wc-software-license-client.php',
        'WC_Vendors_Stripe_Connect_Gateway' => __DIR__ . '/../..' . '/wc-vendors-gateway-stripe-connect.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitae2861788e60bd62334e07b9bce7b69a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitae2861788e60bd62334e07b9bce7b69a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitae2861788e60bd62334e07b9bce7b69a::$classMap;

        }, null, ClassLoader::class);
    }
}