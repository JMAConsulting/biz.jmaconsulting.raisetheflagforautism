<?php
use CRM_Raisetheflagforautism_ExtensionUtil as E;

class CRM_Raisetheflagforautism_Page_Confirm extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    $id = CRM_Utils_Request::retrieve('id', 'Positive');

    $event = civicrm_api3('Event', 'get', [
      'id' => $id,
      'return' => [
        'event_type_id'
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
      'api.LocBlock.get' => ['id' => "\$value.loc_block_id"],
      'api.Address.get' => ['contact_id' => "\$value.created_id"],
      'api.Email.get' => ['contact_id' => "\$value.created_id"],
    ])['values'][$id];

    $this->assign('event', $event);

    CRM_Utils_System::setTitle(E::ts('Raise The Flag Event - \'%1\' submitted succesfully', [1 => $event['title']]));

    $params = ['entity_id' => $id, 'entity_table' => 'civicrm_event'];
    $location = CRM_Core_BAO_Location::getValues($params, TRUE);
    $this->assign('location', $locationC);

    //retrieve custom field information
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, $id, 0, $event['event_type_id'], NULL, TRUE, NULL, FALSE, TRUE, NULL, TRUE);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $id);
    $this->assign('action', CRM_Core_Action::VIEW);

    parent::run();
  }

}
