<?php

namespace Drupal\entity_reference_token_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'slug' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_token_link",
 *   module = "entity_reference_token_link",
 *   label = @Translation("Token link"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TokenLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        'link_text' => '',
        'link_uri' => '',
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('The link text, optionally containing tokens.'),
      '#default_value' => $this->getSetting('link_text'),
      '#required' => TRUE,
    ];

    $element['link_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URI'),
      '#description' => $this->t('The URL to link to, optionally containing tokens.'),
      '#default_value' => $this->getSetting('link_uri'),
      '#required' => TRUE,
    ];

    // If we have the token module available, add the token tree link.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $token_types = [$this->fieldDefinition->getTargetEntityTypeId()];
      $token_types[] = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');

      $element['token_tree_link'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array_unique($token_types),
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Link text') . ': ' . $this->getSetting('link_text');
    $summary[] = $this->t('Link URI') . ': ' . $this->getSetting('link_uri');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $token_data = ['user' => \Drupal::currentUser()];
      $token_data[$items->getEntity()->getEntityTypeId()] = $items->getEntity();
      $token_data[$items->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type')] = $item->entity;
      $text = \Drupal::token()->replace($this->getSetting('link_text'), $token_data);
      $uri = \Drupal::token()->replace($this->getSetting('link_uri'), $token_data);
      $link = Link::fromTextAndUrl($text, Url::fromUserInput($uri));

      $elements[$delta] = $link->toRenderable();
    }

    return $elements;
  }

}
