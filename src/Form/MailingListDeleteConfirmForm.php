<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete mailing list entities.
 */
class MailingListDeleteConfirmForm extends EntityConfirmFormBase {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $queryFactory;

  /**
   * Constructs a new MailingListDeleteConfirmForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity query object.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->queryFactory = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $subscription_count = $this->queryFactory->getStorage('mailing_list_subscription')->getQuery();
    $subscription_count->condition('mailing_list', $this->entity->id())
      ->count()
      ->execute();

    if ($subscription_count) {
      $caption = '<p>' . $this->formatPlural($subscription_count, 'There is 1 subscription to the %type mailing list. You can not remove this mailing list until you have removed that subscription.', 'There are @count subscriptions to the %type mailing list. You can not remove this mailing list until you have removed all its subscriptions.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      $form['link'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.mailing_list_subscription.collection'),
        '#title' => $this->t('Manage subscriptions'),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.mailing_list.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addStatus($this->t('content @type: deleted @label.',
      [
        '@type' => $this->entity->bundle(),
        '@label' => $this->entity->label(),
      ]
    ));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
