<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class ImageFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class ImageFixture extends AbstractFixture {
  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Constructor.
   */
  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $source = __DIR__ . '/../../fixtures/images';
    $files = $this->fileSystem->scanDirectory($source, '/\.jpg$/');
    foreach ($files as $file) {
      $name = $file->filename;
      $destination = 'public://fixtures/images/' . $name;
      if (!is_dir(dirname($destination))) {
        $this->fileSystem->mkdir(dirname($destination), 0755, TRUE);
      }
      $image = file_save_data(file_get_contents($file->uri), $destination,
        FileSystemInterface::EXISTS_REPLACE);

      $this->setReference('image:' . $file->name, $image);
    }
  }

}
