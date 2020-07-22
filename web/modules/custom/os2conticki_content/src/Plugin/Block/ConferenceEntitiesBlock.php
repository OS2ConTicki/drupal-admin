<?php

namespace Drupal\os2conticki_content\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\os2conticki_content\Helper\ConferenceHelper;
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
   * @var \Drupal\os2conticki_content\Helper\ConferenceHelper
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
      $container->get('os2conticki_content.conference_helper')
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

    foreach ($this->conferenceHelper->getConferenceEntitiesInfo() as $type => $info) {
      $entities = $this->conferenceHelper->getEntitites($conference, $type);
      $fragmentId = 'entities-' . $type;

      $createUrl = NULL;
      $user = \Drupal::currentUser();
      if ($user->hasPermission('create ' . $type . ' content')) {
        $destination = Url::fromRoute('<current>', [],
          ['fragment' => $fragmentId])->toString();
        $createUrl = Url::fromRoute('node.add', [
          'node_type' => $type,
          'conference' => $conference->uuid(),
          'destination' => $destination,
        ]);

        /** @var \Drupal\group\Entity\GroupContentInterface[] $groups */
        $groups = GroupContent::loadByEntity($conference);
        $group = $groups ? reset($groups)->getGroup() : NULL;
        if (NULL !== $group) {
          $createUrl = Url::fromRoute('entity.group_content.create_form', [
            'group' => $group->id(),
            'plugin_id' => 'group_node:' . $type,
            'conference' => $conference->uuid(),
            'destination' => $destination,
          ]);
        }
      }

      $build['conference_' . $type] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $info['title'] ?? $type,
        'list' => [
          '#theme' => 'os2conticki_content_conference_entity_list',
          '#conference' => $conference,
          '#type' => $type,
          '#entities' => $entities,
          '#create_url' => $createUrl,
        ],
      ];
    }

    $build['#attached']['library'][] = 'os2conticki_content/form-conference';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
