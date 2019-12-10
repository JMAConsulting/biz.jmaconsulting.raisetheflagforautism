<?php
use CRM_Raisetheflagforautism_ExtensionUtil as E;

class CRM_Raisetheflagforautism_Page_Confirm extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    $id = CRM_Utils_Request::retrieve('id', 'Positive');

    $event = civicrm_api3('Event', 'get', [
      'id' => $id,
      'return' => [
        'event_type_id',
        'description',
        'title',
        'start_date',
        'created_date',
        'created_id',
        'is_public',
        'loc_block_id',
        'custom_830',
        'custom_325',
        'custom_838',
        'custom_834',
      ],
      'api.LocBlock.get' => ['sequential' => 1, 'id' => "\$value.loc_block_id"],
      'api.Address.get' => ['sequential' => 1, 'contact_id' => "\$value.created_id"],
      'api.Email.get' => ['sequential' => 1, 'contact_id' => "\$value.created_id"],
    ])['values'][$id];

    $this->assign('event', $event);

    CRM_Utils_System::setTitle(E::ts('\'%1\' Event submitted succesfully', [1 => $event['title']]));

    $params = ['entity_id' => $id, 'entity_table' => 'civicrm_event'];
    $location = CRM_Core_BAO_Location::getValues($params, TRUE);
    $this->assign('location', $location);

    $params = ['contact_id' => $event['created_id']];
    $address = CRM_Core_BAO_Address::getValues($params);

    $createdByInfo = [
      'created_by' => [
        'label' => 'Created By',
        'value' => CRM_Contact_BAO_Contact::displayName($event['created_id']),
      ],
      'email_address' [
        'label' => ts('Creator Email Address'),
        'value' => sprint('<a href=\'mailto:%s\'>%s</a>', $event['api.Email.get'][0]['email'], $event['api.Email.get'][0]['email']),
      ],
      'mailing_address' => [
        'label' => ts('Creator Mailing Address'),
        'value' => $address,
      ],
    ];
    $this->assign('creator', $createdByInfo);

    parent::run();
  }

}
