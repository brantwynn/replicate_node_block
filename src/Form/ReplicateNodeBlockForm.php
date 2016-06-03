<?php

/**
 * @file
 * Contains \Drupal\replicate_node_block\Form\ReplicateNodeForm.
 */

namespace Drupal\replicate_node_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\replicate\Replicator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ReplicateNodeBlockForm.
 *
 * @package Drupal\replicate_node_block\Form
 */
class ReplicateNodeBlockForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, EventDispatcherInterface $eventDispatcher) {
    $this->entityTypeManager = $entityTypeManager;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'replicate_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form = [];
    if ($node != NULL && $nid = $node->id()) {
      $form['node_id'] = array(
        '#type' => 'hidden',
        '#value' => $nid
      );
      $form['replicate_node'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Replicate Node'),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = $form_state->getValue('node_id');
    $node = Node::load($nid);
    $replicator = new Replicator($this->entityTypeManager, $this->eventDispatcher);
    // Do Androids Dream of Electric Sheep?
    if ($replicant = $replicator->replicateByEntityId('node', $nid)) {
      $message = $this->t('Node:@nodeId "@nodeTitle" has been replicated as Node:@replicantId "@replicantTitle" and you have been redirected.', [
        '@nodeId' => $node->id(),
        '@nodeTitle' => $node->getTitle(),
        '@replicantId' => $replicant->id(),
        '@replicantTitle' => $replicant->getTitle(),
      ]);
      drupal_set_message($message, 'status');
      $redirectUrl = Url::fromUserInput('/node/' .  $replicant->id());
      $form_state->setRedirectUrl($redirectUrl);
    }
  }

}
