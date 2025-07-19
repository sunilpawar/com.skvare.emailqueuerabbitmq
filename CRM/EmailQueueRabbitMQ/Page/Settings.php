<?php
use CRM_EmailQueueRabbitMQ_ExtensionUtil as E;

class CRM_EmailQueueRabbitMQ_Page_Settings extends CRM_Core_Page {

  public function getBAOName() {
    return 'CRM_EmailQueueRabbitMQ_BAO_Settings';
  }

  public function run() {
    $form = new CRM_EmailQueueRabbitMQ_Form_Settings();
    $form->controller = new CRM_Core_Controller_Simple('CRM_EmailQueueRabbitMQ_Form_Settings', 'Email Queue RabbitMQ Settings');
    $form->controller->run();
  }

}
