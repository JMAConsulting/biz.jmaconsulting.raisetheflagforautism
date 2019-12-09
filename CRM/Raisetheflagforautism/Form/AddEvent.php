<?php

use CRM_Raisetheflagforautism_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Raisetheflagforautism_Form_AddEvent extends CRM_Core_Form {

  public $_elementNames;

  public function buildCustom($id, $name, $viewOnly = FALSE, $ignoreContact = FALSE) {
    if ($id) {
      $button = substr($this->controller->getButtonName(), -4);
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');

      // we don't allow conflicting fields to be
      // configured via profile
      $fieldsToIgnore = array(
        'participant_fee_amount' => 1,
        'participant_fee_level' => 1,
      );
      if ($contactID && !$ignoreContact) {
        //FIX CRM-9653
        if (is_array($id)) {
          $fields = array();
          foreach ($id as $profileID) {
            $field = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::ADD,
              NULL, NULL, FALSE, NULL,
              FALSE, NULL, CRM_Core_Permission::CREATE,
              'field_name', TRUE
            );
            $fields = array_merge($fields, $field);
          }
        }
        else {
          if (CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
            $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD,
              NULL, NULL, FALSE, NULL,
              FALSE, NULL, CRM_Core_Permission::CREATE,
              'field_name', TRUE
            );
          }
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD,
          NULL, NULL, FALSE, NULL,
          FALSE, NULL, CRM_Core_Permission::CREATE,
          'field_name', TRUE
        );
      }

      if (array_intersect_key($fields, $fieldsToIgnore)) {
        $fields = array_diff_key($fields, $fieldsToIgnore);
        CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'));
      }
      $addCaptcha = FALSE;

      if (!empty($this->_fields)) {
        $fields = @array_diff_assoc($fields, $this->_fields);
      }

      $this->assign($name, $fields);
      if (is_array($fields)) {
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // ignore file upload fields
            //continue;
          }
          //make the field optional if primary participant
          //have been skip the additional participant.
          if ($button == 'skip') {
            $field['is_required'] = FALSE;
          }
          // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
          elseif ($field['add_captcha'] && !$contactID) {
            // only add captcha for first page
            $addCaptcha = TRUE;
          }
          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          if ($viewOnly) {
            $field['is_view'] = $viewOnly;
            if ($field['data_type'] == 'File' || $field['name'] == 'image_URL') {
              $this->add('text', $field['name'], $field['title'], []);
              $this->freeze($field['name']);
              continue;
            }
          }
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);

          $this->_fields[$key] = $field;
        }
      }

      if ($addCaptcha && !$viewOnly) {
        $captcha = CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign('isCaptcha', TRUE);
      }
    }
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Autism Ontario - Flag Raising'));
    $groupID = civicrm_api3('CustomGroup', 'getValue', ['name' => 'flag_raising', 'return' => 'id']);
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, $groupID, 0, NULL);
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
    //CRM_Core_Error::debug('a', CRM_Event_PseudoConstant::eventType());

    $fields = [
      'local_chapter' => [
        'title' => ts('Closest local chapter of Austism Ontario?'),
        'type' => 'select',
        'required' => TRUE,
      ],
      'event_image' => [
        'title' => ts('Image'),
        'type' => 'file',
        'required' => FALSE,
      ],
      'event_title' => [
        'title' => ts('Event Title'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'event_description' => [
        'title' => ts('Event Description'),
        'type' => 'textarea',
        'required' => FALSE,
      ],
      'location' => [
        'title' => ts('Name of location of flag raising?'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'street_address' => [
        'title' => ts('Street Address'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'city' => [
        'title' => ts('City'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'postal_code' => [
        'title' => ts('Postal Code'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'ceremony_date' => [
        'title' => ts('What date and time of the ceremony'),
        'type' => 'date',
        'required' => TRUE,
      ],
      'attending' => [
        'title' => ts('Autism Ontario - Who is attending?'),
        'type' => 'Text',
        'required' => TRUE,
      ],
      'open_to_public' => [
        'title' => ts('Is this ceremony open to public?'),
        'post_help' => ts('i.e. do you want Autism Ontario to list your ceremony and help spread the word?'),
        'type' => 'YesNo',
        'required' => TRUE,
      ],
    ];
    $postHelps = [
      'last_name' => ts('Will not be shared with the public - will be used to verify ceremony and send flag as required'),
      'email-Primary' =>  ts('Will not be shared with the public - will be used to verify ceremony and send flag as required'),
    ];
    foreach ($fields as $name => $field) {
      if (!empty($field['post_help'])) {
        $postHelps[$name] = $field['post_help'];
      }
      $title = $field['title'];
      if ($field['type'] == 'select') {
         $this->add('select', $name, $title, CRM_Core_OptionGroup::values('chapter_20180619153429'), $field['required']);
      }
      if ($field['type'] == 'text') {
        $this->add('text', $name, $title, NULL, $field['required']);
      }
      if ($field['type'] == 'YesNo') {
        $this->addYesNo($name, $title, FALSE, $field['required']);
      }
      if ($field['type'] == 'textarea') {
        $this->add('textarea',
         $name,
         $title,
         "rows=4 cols=60"
       );
      }
      if ($field['type'] == 'date') {
        $params = array(
          'date' => 'dd/mm/yy',
          'time' => 12,
        );
        $this->add('datepicker', $name, $title, '', $field['required'], $params);
      }
      if ($field['type'] ==  'file') {
        $this->add('file', $name, ts('Image'));
        $this->addUploadElement($name);
      }
    }

    $this->buildCustom(142, 'individual');

    $this->assign('postHelps', $postHelps);

    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
    $this->_elementNames = $this->getRenderableElementNames();
    // export form elements
    $this->assign('elementNames', $this->_elementNames);

    /**
    $captcha = CRM_Utils_ReCAPTCHA::singleton();
    $captcha->add($this);
    $this->assign('isCaptcha', TRUE);
   */

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);
    $contactID = NULL;
    $fields = CRM_Core_BAO_UFGroup::getFields(142, FALSE, CRM_Core_Action::VIEW);
    $values['skip_greeting_processing'] = TRUE;
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($values, $fields, $contactID, NULL, 142);

    $url = '';
    if ($values['event_image']) {
      $fileInfo = $values['event_image'];
      $fileDAO = new CRM_Core_DAO_File();
      $filename = pathinfo($fileInfo['name'], PATHINFO_BASENAME);
      $fileDAO->uri = $filename;
      $fileDAO->mime_type = $fileInfo['type'];
      $fileDAO->upload_date = date('YmdHis');
      $fileDAO->save();
      $fileID = $fileDAO->id;

      $photo = basename($fileInfo['name']);
      $url = sprintf("<img src='%s' style=\"height:516px; width:617px\"", CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=' . $photo, TRUE, NULL, TRUE, TRUE));
    }

    $description = sprintf('
    <p>%s</p>
     <br>
    <p>%s</p>
    ', $url , $values['event_description']);

    $params = [
      'summary' => "",
      'event_type_id' => key(CRM_Event_PseudoConstant::eventType()),
      'is_public' => $values['open_to_public'],
      'description' => $description,
      'is_online_registration' => '1',
      'start_date' => $values['ceremony_date'],
      'is_monetary' => '0',
      'is_active' => 1,
      'title' => $values['event_title'],
      'created_date' => date('YmdHis'),
      'created_id' => $contactID,
    ];
    $eventID = civicrm_api3('Event', 'create', $params)['id'];

    $address = [
      1 => [
        'state_province_id' => 1108,
        'country_id' => 939,
        'is_primary' => 1,
        'location_type_id' => 1,
      ],
    ];
    foreach ([
      'street_address',
      'city',
      'postal_code',

    ] as $name) {
      $address[1][$name] = $values[$name];
    }
    $locParams = [
      'entity_table' => 'civicrm_event',
      'entity_id' => $eventID,
      'address' => $address,
    ];
    $params = [
      'id' => $eventID,
      'loc_block_id' => CRM_Core_BAO_Location::create($locParams, TRUE, 'event')['id'],
    ];
    civicrm_api3('Event', 'create', $params);
    //CRM_Core_Error::debug('aaaa', $values);exit;
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label) && !strstr($label, 'captcha')) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
