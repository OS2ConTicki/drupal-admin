<?php

namespace Drupal\conference_content\Plugin\Block;

use Drupal\conference_content\Helper\ConferenceHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block listing all entities belonging to a conference.
 *
 * @Block(
 *   id = "conference_entities_block",
 *   admin_label = @Translation("Conference entities"),
 *   category = @Translation("Conference"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class ConferenceEntitiesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The conference helper.
   *
   * @var \Drupal\conference_content\Helper\ConferenceHelper
   */
  private $conferenceHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConferenceHelper $conferenceHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->conferenceHelper = $conferenceHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('conference_content.conference_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf('conference' === $this->getContextValue('node')->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface $conference */
    $conference = $this->getContextValue('node');
    $build = [];

    $info = $this->conferenceHelper->getConferenceEntititesInfo();

    foreach ([
      'event' => [
        'title' => $this->t('Events'),
        'add' => $this->t('Add event'),
      ],
      'speaker' => [
        'title' => $this->t('Speakers'),
        'add' => $this->t('Add speaker'),
      ],
      'tag' => [
        'title' => $this->t('Tags'),
        'add' => $this->t('Add tag'),
      ],
      'location' => [
        'title' => $this->t('Locations'),
        'add' => $this->t('Add location'),
      ],
      'theme' => [
        'title' => $this->t('Themes'),
        'add' => $this->t('Add theme'),
      ],
      'sponsor' => [
        'title' => $this->t('Sponsors'),
        'add' => $this->t('Add sponsor'),
      ],
      'organizer' => [
        'title' => $this->t('Organizers'),
        'add' => $this->t('Add organizer'),
      ],
    ] as $type => $info) {
      $entities = $this->conferenceHelper->getEntitites($conference, $type);

      $build['conference_' . $type] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $info['title'] ?? $type,
        'list' => [
          '#theme' => 'conference_content_conference_entity_list',
          '#conference' => $conference,
          '#type' => $type,
          '#entities' => $entities,
        ],
      ];
    }

    return $build;
  }

}
