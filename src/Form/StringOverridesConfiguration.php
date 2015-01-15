<?php

/**
 * @file
 * Contains \Drupal\stringoverrides\Form\StringOverridesConfiguration.
 */

namespace Drupal\stringoverrides\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
// use Symfony\Component\HttpFoundation\Request;

class StringOverridesConfiguration extends ConfigFormBase {

  public function getFormId() {
    return 'stringoverrides_admin';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $userInputValues = $form_state->getUserInput();
    dsm($userInputValues);
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
    dsm($string);
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
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#weight' => 3,
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
    // $userInputValues = $form_state->getUserInput();
    // $config = $this->configFactory->get('time_spent.settings');
    // $config->set('time_spent_node_types', $userInputValues['time_spent_node_types']);
    // $config->set('time_spent_pager_limit', $userInputValues['time_spent_pager_limit']);
    // $config->set('time_spent_roles', $userInputValues['time_spent_roles']);
    // $config->set('time_spent_timer', $userInputValues['time_spent_timer']);
    // $config->set('time_spent_limit', $userInputValues['time_spent_limit']);
    // $config->save();
    // parent::submitForm($form, $form_state);
  }
}

function stringoverrides_textbox_combo($delta = 0, $enabled = TRUE, $context = '', $source = '', $translation = '') {
  $form['#tree'] = TRUE;
  $form['enabled'] = array(
    '#type' => 'checkbox',
    '#default_value' => ($enabled == -1) ? TRUE : $enabled,
    // Have access if it's not a placeholder value.
    '#access' => $enabled != -1,
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
    dsm("d,jhd");
  return $form['string'];
}