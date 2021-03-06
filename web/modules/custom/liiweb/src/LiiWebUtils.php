<?php

namespace Drupal\liiweb;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * Class LiiWebUtils.
 */
class LiiWebUtils {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LiiWebUtils object.
   * {@inheritDoc}
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Retrieve a revision with a frbr uri.
   *
   * @param $uri
   *   Example: /akn/za/act/1993/31/eng@1993-01-01
   *
   * @return |null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRevisionFromFrbrUri($uri) {
    $uri = urldecode($uri);

    if (!$this->isAknUri($uri)) {
      return NULL;
    }

    $query = $this->database->select('node_revision__field_frbr_uri', 'n');
    $query->fields('n', ['entity_id', 'revision_id', 'langcode']);
    $query->condition('field_frbr_uri_value', $uri);
    $query->orderBy('revision_id', 'DESC');
    $result = $query->execute()->fetchAssoc();

    if (!empty($result['entity_id'])) {
      return $this->entityTypeManager->getStorage('node')->loadRevision($result['revision_id'])->getTranslation($result['langcode']);
    }

    return NULL;
  }

  /**
   * Check if an URI is in AKN format.
   *
   * @param $uri
   *   Example: /akn/za/act/1993/31/eng@1993-01-01
   *
   * @return bool
   */
  public function isAknUri($uri) {
    return strpos($uri, '/akn/') === 0;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param $langcode
   *
   * @return string|null
   */
  public function getLatestFrbrUriForNode(\Drupal\node\NodeInterface $node, $langcode) {
    if ($node->bundle() != 'legislation') {
      return NULL;
    }

    // Make sure we have the default revision.
    $node = Node::load($node->id());

    if ($node->language()->getId() == $langcode) {
      return $node->field_frbr_uri->value;
    }

    if ($node->hasTranslation($langcode)) {
      $node = $node->getTranslation($langcode);
      return $node->field_frbr_uri->value;
    }

    return NULL;
  }

}
