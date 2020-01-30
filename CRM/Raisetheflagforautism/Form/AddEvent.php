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
        CRM_Core_Session::setStatus(E::ts('Some of the profile fields cannot be configured for this page.'));
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

      /* if ($addCaptcha && !$viewOnly) {
        $captcha = CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign('isCaptcha', TRUE);
      } */
    }
  }

  public function buildQuickForm() {
    $locale = CRM_Core_I18n::getLocale();
    $this->add('hidden', 'form_locale', $locale);
    CRM_Utils_System::setTitle(E::ts('Autism Ontario - Flag Raising'));
    $groupID = civicrm_api3('CustomGroup', 'getValue', ['name' => 'flag_raising', 'return' => 'id']);
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, $groupID, 0, NULL);
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
    //CRM_Core_Error::debug('a', CRM_Event_PseudoConstant::eventType());

    $fields = [
      'require_flag' => [
        'title' => E::ts('Do you require a flag?'),
        'type' => 'YesNo',
        'required' => TRUE,
      ],
      'local_chapter' => [
        'title' => E::ts('Closest local chapter of Autism Ontario?'),
        'type' => 'select',
        'required' => TRUE,
      ],
      'address_name' => [
        'title' => E::ts('Name of location of flag raising?'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'street_address' => [
        'title' => E::ts('Street address of flag raising'),
        'type' => 'text',
        'post_help' => E::ts('This is where the flag will be sent. If you want the flag sent to a different address please email RTF@autismontario.com'),
        'required' => TRUE,
      ],
      'city' => [
        'title' => E::ts('City of flag raising'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'postal_code' => [
        'title' => E::ts('Postal Code of flag raising'),
        'type' => 'text',
        'required' => TRUE,
      ],
      /*
      'event_image' => [
        'title' => ts('Image'),
        'type' => 'file',
        'required' => FALSE,
      ],
      */
      'ceremony_date' => [
        'title' => E::ts('What date and time of the ceremony'),
        'type' => 'date',
        'required' => TRUE,
      ],
      'open_to_public' => [
        'title' => E::ts('Is this ceremony open to public?'),
        'post_help' => E::ts('i.e. do you want Autism Ontario to list your ceremony and help spread the word?'),
        'type' => 'YesNo',
        'required' => TRUE,
      ],
      'event_title' => [
        'title' => E::ts('Event Title'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'event_description' => [
        'title' => E::ts('Event Description'),
        'type' => 'textarea',
        'required' => FALSE,
      ],
      'attending' => [
        'title' => E::ts('Autism Ontario Representation - Who is attending?'),
        'type' => 'text',
        'required' => FALSE,
      ],
    ];
    $postHelps = [];
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
        $this->add('file', $name, E::ts('Image'));
        $this->addUploadElement($name);
      }
    }

    $this->buildCustom(145, 'individual');

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
    $fields = CRM_Core_BAO_UFGroup::getFields(145, FALSE, CRM_Core_Action::VIEW);
    $values['skip_greeting_processing'] = TRUE;
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($values, $fields, $contactID, NULL, 145);

    $url = '';
    if (!empty($values['event_image'])) {
      $fileInfo = $values['event_image'];
      rename($fileInfo['name'], CRM_Core_Config::singleton()->imageUploadDir . basename($fileInfo['name']));
      $fileDAO = new CRM_Core_DAO_File();
      $filename = pathinfo($fileInfo['name'], PATHINFO_BASENAME);
      $fileDAO->uri = $filename;
      $fileDAO->mime_type = $fileInfo['type'];
      $fileDAO->upload_date = date('YmdHis');
      $fileDAO->save();
      $fileID = $fileDAO->id;

      $photo = basename($fileInfo['name']);
      //$url = sprintf("<img src='%s' style=\"height:516px; width:617px\">", );
      $url = "<img src='http://staging.raisetheflagforautism.com/wp-content/uploads/civicrm/persist/" . basename($fileInfo['name']) . "' style='height:516px;width:617px'>";
    }

    $description = sprintf('
    <p>%s</p>
     <br>
    <p>%s</p>
    ', $url , $values['event_description']);

    $params = [
      'summary' => "",
      'event_type_id' => 23,
      'is_public' => $values['open_to_public'],
      'description' => $description,
      //'is_online_registration' => '1',
      'start_date' => $values['ceremony_date'],
      'is_monetary' => '0',
      'is_active' => 0,
      'title' => "Raise The Flag - " . $values['event_title'],
      'created_date' => date('YmdHis'),
      'created_id' => $contactID,
    ];
    $title = $params['title'];
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
      'name',
      'street_address',
      'city',
      'postal_code',

    ] as $name) {
      if ($name === 'name') {
        $address[1][$name] = $values['address_name'];
      }
      else {
        $address[1][$name] = $values[$name];
      }
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

    civicrm_api3('CustomValue', 'create', [
      'entity_id' => $eventID,
      'custom_830' => $contactID, // Event Created By
      'custom_325' => CRM_Core_DAO::VALUE_SEPARATOR . $values['local_chapter'] . CRM_Core_DAO::VALUE_SEPARATOR, // Event Chapter
      'custom_850' => $values['attending'], // Autism Ontario Representative
      'custom_846' => $values['require_flag'], // Flag required?
    ]);

    $url = "https://autismontario.com/civicrm/event/manage/settings?reset=1&action=update&id=" . $eventID;
    $link = "<a href='" . $url . "'>" . $title . "</a>";
    //sprintf("<a href='%s'>%s</a>", CRM_Utils_System::url('civicrm/event/manage/settings', 'reset=1&action=update&id=' . $eventID), $title);
    $displayName = CRM_Contact_BAO_Contact::displayName($contactID);

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->id = 89;
    $messageTemplates->find(TRUE);

    $body_subject = CRM_Core_Smarty::singleton()->fetch("string:$messageTemplates->msg_subject");
    $body_text    = str_replace('{creator-name}', $displayName, str_replace('{event-link}', $title, $messageTemplates->msg_text));
    $body_html    = "{crmScope extensionKey='biz.jmaconsulting.raisetheflagforautism'}" . $messageTemplates->msg_html . "{/crmScope}";
    $body_html    = str_replace('{creator-name}', $displayName, str_replace('{event-link}', $link, $body_html));
    $body_html = CRM_Core_Smarty::singleton()->fetch("string:{$body_html}");
    $body_text = CRM_Core_Smarty::singleton()->fetch("string:{$body_text}");

    $mailParams = array(
      'groupName' => 'New Raise the Flag Event Submitted',
      'from' => '"Autism Ontario" <info@autismontario.com>',
      'toName' =>  "Jennifer Dent",
      'toEmail' => "jennifer@autismontario.com",
      'subject' => $body_subject,
      'messageTemplateID' => $messageTemplates->id,
      'html' => $body_html,
      'text' => $body_text,
    );
    CRM_Utils_Mail::send($mailParams);

    if (substr($values['form_locale'], 0, 2) !== 'en') {
      $urlParam = 'fr/';
    }
    else {
      $urlParam = '';
    }
//    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event-confirm', 'reset=1&id=' . $eventID));
     CRM_Utils_System::redirect("https://www.autismontario.com/{$urlParam}civicrm/event-confirm?reset=1&id=" . $eventID);
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
