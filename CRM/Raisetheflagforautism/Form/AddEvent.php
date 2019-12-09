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
      'event_title' => [
        'title' => ts('Event Title'),
        'type' => 'text',
        'required' => TRUE,
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
    foreach ($fields as $name => $field) {
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
      if ($field['type'] == 'date') {
        $params = array(
          'date' => 'dd/mm/yy',
          'time' => 12,
        );
        $this->add('datepicker', $name, $title, '', $field['required'], $params);
      }
    }

    $this->buildCustom(142, 'individual');

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
    $this->_elementNames = $this->getRenderableElementNames();
    // export form elements
    $this->assign('elementNames', $this->_elementNames);
    parent::buildQuickForm();
  }

  public function postProcess() {

    $values = $this->exportValues();
    $contactID = NULL;
    $fields = CRM_Core_BAO_UFGroup::getFields(OAP_INDIVIDUAL, FALSE, CRM_Core_Action::VIEW);
    $values['skip_greeting_processing'] = TRUE;
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($values, $fields, $contactID, NULL, 142);

    $params = [
      'summary' => "",
      'event_type_id' => key(CRM_Event_PseudoConstant::eventType()),
      'is_public' => $values['open_to_public'],
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

  public function getColorOptions() {
    $options = array(
      '' => E::ts('- select -'),
      '#f00' => E::ts('Red'),
      '#0f0' => E::ts('Green'),
      '#00f' => E::ts('Blue'),
      '#f0f' => E::ts('Purple'),
    );
    foreach (array('1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e') as $f) {
      $options["#{$f}{$f}{$f}"] = E::ts('Grey (%1)', array(1 => $f));
    }
    return $options;
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
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
