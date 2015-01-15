<?php

/**
 * @file
 * Contains \Drupal\stringoverrides\Form\StringOverridesConfiguration.
 */

namespace Drupal\stringoverrides\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\String;
// use Symfony\Component\HttpFoundation\Request;

class StringOverridesConfiguration extends ConfigFormBase {

  public function getFormId() {
    return 'stringoverrides_admin';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $userInputValues = $form_state->getUserInput();
    dsm($form);
    $config = \Drupal::config('stringoverrides.settings');

    if (empty($lang)) {
      global $language;
      $lang = $language->language;
    }

    // // Setup the form
    // $form['#cache'] = TRUE;
    // $form['#attached']['css'][drupal_get_path('module', 'stringoverrides') . '/stringoverrides.admin.css'] = array();
    // $form['lang'] = array(
    //   '#type' => 'hidden',
    //   '#value' => $lang,
    // );
    // $form['string'] = array(
    //   '#tree' => TRUE,
    //   '#theme' => 'stringoverrides_strings',
    // );

    // // Retrieve the string overrides from the variables table.
    $words = array(
      FALSE => $config->get("locale_custom_disabled_strings_$lang") ?: array(),
      TRUE => $config->get("locale_custom_strings_$lang") ?: array(),
    );
    $strings = array();
    foreach ($words as $enabled => $custom_strings) {
      foreach ($custom_strings as $context => $translations) {
        foreach ($translations as $source => $translation) {
          $strings[] = array(
            'enabled' => $enabled,
            'context' => $context,
            'source' => $source,
            'translation' => $translation,
          );
        }
      }
    }
    // See how many string rows there should be.
    $string_count = 0;
    if (isset($userInputValues['string_count'])) {
      $string_count = $userInputValues['string_count'];
    }
    else {
      $string_count = count($strings) + 1;
    }

    // Sort the strings and display them in the form.
    usort($strings, 'stringoverrides_admin_word_sort');
    for ($index = 0; $index < $string_count; $index++) {
      if (isset($strings[$index])) {
        $string = $strings[$index];
        $form['string'][$index] = stringoverrides_textbox_combo($index, $string['enabled'], $string['context'], $string['source'], $string['translation']);
      }
      else {
        $form['string'][$index] = stringoverrides_textbox_combo($index, -1);
      }
    }

    // Add the buttons to the form.
    $form['actions'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form-actions', 'container-inline')),
    );
    $form['actions']['more_strings'] = array(
      '#type' => 'submit',
      '#value' => t('Add row'),
      '#description' => t("If the amount of boxes above isn't enough, click here to add more choices."),
      '#weight' => 2,
      '#submit' => array('stringoverrides_more_strings_submit'),
      '#ajax' => array(
        'callback' => 'stringoverrides_ajax',
        'wrapper' => 'stringoverrides-wrapper',
        'method' => 'replace',
        'effect' => 'none',
      ),
    );
    $form['actions']['remove'] = array(
      '#type' => 'submit',
      '#value' => t('Remove disabled strings'),
      '#weight' => 4,
      '#access' => !empty($strings),
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->get('time_spent.settings');
    $clicked_button = '';

    foreach ($form_state->getButtons() as $key => $value) {
      if ($value['#value'] == $userInputValues['op']) {
        $clicked_button = $value['#id'];
      }
    }
    if (!in_array($clicked_button, array('edit-submit', 'edit-remove'))) {
      // Submit the form only for save and remove buttons.
      return;
    }
    
    // Format the words correctly so that they're put into the database correctly.
    dsm($userInputValues);
    $words = array();
    $words = array(FALSE => array(), TRUE => array());
    foreach ($userInputValues as $index => $string) {
      if(is_array($string)) {
        dsm($string);
        if (!empty($string['source'])) {
          $context = String::checkPlain($string['context']);
          dsm($context);
          // Get rid of carriage returns.
          list($source, $translation) = str_replace("\r", '', array($string['source'], $string['translation']));
          $words[$string['enabled']][$context][$source] = $translation;
        }
      }
    }
    dsm($words);
    

    // Save into the correct language definition.
    $lang = $form['lang']['#value'];
    if (empty($lang)) {
      global $language;
      $lang = $language->language;
    }
    $config->set('locale_custom_strings_$lang',$words[1]);

    // Save the values and display a message to the user depend.
    switch ($clicked_button) {
      case 'edit-submit':
        $config->set('locale_custom_disabled_strings_$lang',$words[0]);
        drupal_set_message(t('Your changes have been saved.'));
        break;

      case 'edit-remove':
        Drupal::state()->delete("locale_custom_disabled_strings_$lang");
        drupal_set_message(t('The disabled strings have been removed.'));
        break;
    }
    parent::submitForm($form, $form_state);
  }
}

function stringoverrides_textbox_combo($delta = 0, $enabled = TRUE, $context = '', $source = '', $translation = '') {
  $form['#tree'] = TRUE;

  $form['enabled'] = array(
    '#type' => 'checkbox',
    '#default_value' => ($enabled == -1) ? TRUE : $enabled,
    // '#access' => $enabled != -1,
    '#attributes' => array(
      'title' => t('Flag whether this override should be active.'),
    ),
  );

  $form['source'] = array(
    '#type' => 'textarea',
    '#default_value' => $source,
    '#rows' => 1,
    '#attributes' => array(
      'title' => t('The original source text to be replaced.'),
    ),
  );
  $form['translation'] = array(
    '#type' => 'textarea',
    '#default_value' => $translation,
    '#rows' => 1,
    '#attributes' => array(
      'title' => t('The text to replace the original source text.'),
    ),
    // Hide the translation when the source is empty.
    '#states' => array(
      'invisible' => array(
        "#edit-string-$delta-source" => array('empty' => TRUE),
      ),
    ),
  );
  $form['context'] = array(
    '#type' => 'textfield',
    '#default_value' => $context,
    '#size' => 5,
    '#maxlength' => 255,
    '#attributes' => array(
      'title' => t('Strings sometimes can have context applied to them. Most cases, this is not the case.'),
    ),
    // Hide the context when the source is empty.
    '#states' => array(
      'invisible' => array(
        "#edit-string-$delta-source" => array('empty' => TRUE),
      ),
    ),
  );
  return $form;
}

/**
 * Menu callback; Display a new string override.
 */
function stringoverrides_ajax(array $form, FormStateInterface $form_state) {
  return $form['string'];
}

/**
 * Submit handler; The "Add row" button.
 */
function stringoverrides_more_strings_submit(array $form, FormStateInterface $form_state) {
  $userInputValues = $form_state->getUserInput();
  // $form_state['string_count'] = count($userInputValues['string']) + 1;
  $form_state->setRebuild(True);
}

/**
 * Sorts two words based on their source text.
 */
function stringoverrides_admin_word_sort($word1, $word2) {
  return strcasecmp($word1['source'], $word2['source']);
}

/**
 * Theme the enabled box and the two text box strings
 */
function theme_stringoverrides_strings($variables) {
  $form = $variables['form'];
  $rows = array();
  foreach (element_children($form) as $key) {
    // Build the table row.
    $rows[$key] = array(
      'data' => array(
        array('data' => drupal_render($form[$key]['enabled']), 'class' => 'stringoverrides-enabled'),
        array('data' => drupal_render($form[$key]['source']), 'class' => 'stringoverrides-source'),
        array('data' => drupal_render($form[$key]['translation']), 'class' => 'stringoverrides-translation'),
        array('data' => drupal_render($form[$key]['context']), 'class' => 'stringoverrides-context'),
      ),
    );
    // Add any attributes on the element to the row, such as the ahah class.
    if (array_key_exists('#attributes', $form[$key])) {
      $rows[$key] = array_merge($rows[$key], $form[$key]['#attributes']);
    }
  }
  $header = array(
    ($form[0]['enabled']['#access']) ? array('data' => t('Enabled'), 'title' => t('Flag whether the given override should be active.')) : NULL,
    array('data' => t('Original'), 'title' => t('The original source text to be replaced.')),
    array('data' => t('Replacement'), 'title' => t('The text to replace the original source text.')),
    array('data' => t('Context'), 'title' => t('Some strings have context applied to them. In those cases, you can provide the context in this column.')),
  );

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'stringoverrides-wrapper'),
  ));
  $output .= drupal_render_children($form);
  return $output;
}
