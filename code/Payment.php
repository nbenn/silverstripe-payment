<?php 

/**
 * "Abstract" class for a number of payment models
 * 
 *  @package payment
 */
class Payment extends DataObject {
  /**
   * The payment form fields that should
   * be shown on the checkout order form for the
   * payment type. This is mostly used for merchant-hosted payment
   */
  public $formFields;
  public $requiredFormFields;

  public function __construct() {
    $this->formFields = new FieldSet();
    $this->formRequirements = array();
  }
  
  /**
   * Get the payment gateway class name from the associating module name
   * 
   * @param $gatewayName
   * @return gateway class name
   * 
   * TODO: Generalize naming convention
   *       Take care of merchant hosted and gateway hosted sub classes
   */
  public static function gatewayClassName($gatewayname) {
    $className = $gatewayname . "_Payment";
    if (class_exists($className)) {
      return $className;
    } else {
      user_error("Payment gateway class is not defined", E_USER_ERROR);
    }
  }
  
  /**
   * Incomplete (default): Payment created but nothing confirmed as successful
   * Success: Payment successful
   * Failure: Payment failed during process
   * Pending: Payment awaiting receipt/bank transfer etc
   */
  public static $db = array(
      'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
      'Amount' => 'Money',
      'Message' => 'Text',
      'IP' => 'Varchar',
      'ProxyIP' => 'Varchar',
      'PaidForID' => "Int",
      'PaidForClass' => 'Varchar',
  
      //This is used only when the payment is one of the recurring payments, when a scheduler is trying to
      //find which is the latest one for the recurring payments
      'PaymentDate' => "Date",
  
      //Used for storing any Exception during this payment Process.
      'ExceptionError' => 'Text'
  );
  
  public static $has_one = array(
      'PaidObject' => 'Object',
      'PaidBy' => 'Member',
  );
  
  /**
   * Make payment table transactional.
   */
  static $create_table_options = array(
      'MySQLDatabase' => 'ENGINE=InnoDB'
  );
  
  /**
   * The currency code used for payments.
   * @var string
   */
  protected static $site_currency = 'USD';
  
  /**
   * Set the currency code that this site uses.
   * @param string $currency Currency code. e.g. "NZD"
   */
  public static function set_site_currency($currency) {
    self::$site_currency = $currency;
  }
  
  /**
   * Return the site currency in use.
   * @return string
   */
  public static function site_currency() {
    return self::$site_currency;
  }
}

class Payment_MerchantHosted extends Payment {
  protected static $cvn_mode = true;
  
  public function getCreditCardFields() {
    $fields = new FieldSet(
        new TextField('CardHolderName', 'Credit Card Holder Name :'),
        new CreditCardField('CardNumber', 'Credit Card Number :'),
        new TextField('DateExpiry', 'Credit Card Expiry : (MMYY)', '', 4)
    );
    
    if (self::$cvn_mode) $fields->push(new TextField('Cvc2', 'Credit Card CVN : (3 or 4 digits)', '', 4));
    return $fields;
  }
  
  public function getFormFields() {
    // Other business fields
    
    // Credit card fields
    $ccFields = $this->getCreditCardFields();
    foreach ($ccFields as $ccFields) {
      $this->formFields->push($ccField);
    }
    
    return $this->formFields;
  }
  
  public function getFormRequirements() {
    
  }
}

class Payment_GatewayHosted extends Payment {
  
}

/* Payment result classes. TODO: is there a cleaner way? */ 

abstract class Payment_Result {

  protected $value;

  function __construct($value = null) {
    $this->value = $value;
  }

  function getValue() {
    return $this->value;
  }

  abstract function isSuccess();

  abstract function isProcessing();

}
class Payment_Success extends Payment_Result {

  function isSuccess() {
    return true;
  }

  function isProcessing() {
    return false;
  }

}
class Payment_Processing extends Payment_Result {

  function isSuccess() {
    return false;
  }

  function isProcessing() {
    return true;
  }

}
class Payment_Failure extends Payment_Result {

  function isSuccess() {
    return false;
  }

  function isProcessing() {
    return false;
  }
}