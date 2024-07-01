<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Controller to download preview files.
 */
class ReliefWebFilePreviewController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * Download a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to download.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Binary file response.
   */
  public function downloadFile(FileInterface $file) {
    $path = $this->fileSystem->realpath($file->getFileUri());

    $response = new BinaryFileResponse($path);

    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_INLINE,
      $file->getFileName(),
    );
    $response->headers->set('Content-Disposition', $disposition);
    $response->headers->set('Cache-Control', 'private');
    $response->headers->set('Content-Type', $file->getMimeType());

    return $response;
  }

}
