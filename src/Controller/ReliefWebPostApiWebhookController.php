<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ocha_reliefweb\Entity\ReliefWebResourceInterface;
use Drupal\ocha_reliefweb\Helpers\DateHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the webhook endpoint to update a resource.
 */
class ReliefWebPostApiWebhookController extends ControllerBase {

  /**
   * Webhook callback to update a resource when pinged by ReliefWeb.
   *
   * @param \Drupal\ocha_reliefweb\Entity\ReliefWebResourceInterface $resource
   *   The ReliefWeb resource to update.
   *
   * @todo retrieve the request and validate the provider?
   * @todo should add something to the submission request that would returned
   *   in the feedback request to this webhook endpoint so prevent unwanted
   *   requests? (Maybe: provide UUID, "token" and resouce "status").
   */
  public function updateResource(ReliefWebResourceInterface $resource) {
    // @todo handle errors returned by the API. Maybe we should queue the
    // document to be rechecked at a later time otherwise it will never be
    // updated.
    $data = $resource->retrieveApiData(TRUE);

    // Retrieve the current status of the document.
    $status = $resource->getStatus();

    if (!empty($data)) {
      $status = 'published';
      $message = $this->t('Document publicly available.');
    }
    // We only change the status of resources that are not marked as error.
    // @todo explain why...
    elseif ($status !== 'error') {
      // @todo we may need to distinguish between the different unpublished
      // states: on-hold, refused and embargoed.
      //
      // Embargoed is a bit ambiguous because the data is not yet available
      // in the API. We can show a message in the RW resource collection
      // to indicate the document is embargoed if the date is not yet passed.
      // If over and still unpublished then it means the document was likely
      // refused or put on-hold.
      // There should be a ping when the embargoed document is automatically
      // published on ReliefWeb and the data should be in the API.
      $status = match($status) {
        // The first ping is when the ReliefWeb system processes the queued
        // document, in which case the if there is no data in the API, it means
        // the document is pending review by the editors.
        'queued' => 'pending',
        // The second ping is when the document is reviewed by the editorial
        // team, in which case the document is either put on-hold, refused or
        // marked as embargoed.
        'pending' => 'unpublished',
        // Any other ping means the document is unpublished: either archived,
        // put back on-hold, refused etc.
        default => 'unpublished',
      };

      // @todo move that to the `ReliefWebResource::preSave()` so there is
      // only one place where the message is set.
      if ($status === 'pending') {
        $message = $this->t('Document pending review by editorial team.');
      }
      else {
        $embargoed = DateHelper::getDateObject($resource->embargoed?->value);
        if (!empty($embargoed) && $embargoed < new \DateTime()) {
          $message = $this->t('Document embargoed until @date.', [
            '@date' => $embargoed->format('c'),
          ]);
        }
        else {
          $message = $this->t('Document not publicly available.');
        }
      }
    }

    // Update the status.
    if ($status !== $resource->getStatus()) {
      // Ensure the resource's content is not submitted again as we just want
      // to update the status.
      $resource->setSubmitContent(FALSE);
      $resource->set('status', $status);
      $resource->set('message', $message);
      $resource->save();
    }

    return new Response();
  }

}
