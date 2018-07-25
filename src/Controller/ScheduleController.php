<?php

namespace Drupal\business_rules\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\business_rules\Entity\ScheduleInterface;

/**
 * Class ScheduleController.
 *
 *  Returns responses for Schedule routes.
 */
class ScheduleController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Schedule  revision.
   *
   * @param int $schedule_revision
   *   The Schedule  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($schedule_revision) {
    $schedule = $this->entityManager()->getStorage('schedule')->loadRevision($schedule_revision);
    $view_builder = $this->entityManager()->getViewBuilder('schedule');

    return $view_builder->view($schedule);
  }

  /**
   * Page title callback for a Schedule  revision.
   *
   * @param int $schedule_revision
   *   The Schedule  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($schedule_revision) {
    $schedule = $this->entityManager()->getStorage('schedule')->loadRevision($schedule_revision);
    return $this->t('Revision of %title from %date', ['%title' => $schedule->label(), '%date' => format_date($schedule->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Schedule .
   *
   * @param \Drupal\business_rules\Entity\ScheduleInterface $schedule
   *   A Schedule  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ScheduleInterface $schedule) {
    $account = $this->currentUser();
    $langcode = $schedule->language()->getId();
    $langname = $schedule->language()->getName();
    $languages = $schedule->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $schedule_storage = $this->entityManager()->getStorage('schedule');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $schedule->label()]) : $this->t('Revisions for %title', ['%title' => $schedule->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all schedule revisions") || $account->hasPermission('administer schedule entities')));
    $delete_permission = (($account->hasPermission("delete all schedule revisions") || $account->hasPermission('administer schedule entities')));

    $rows = [];

    $vids = $schedule_storage->revisionIds($schedule);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\business_rules\ScheduleInterface $revision */
      $revision = $schedule_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $schedule->getRevisionId()) {
          $link = $this->l($date, new Url('entity.business_rules_schedule.revision', ['schedule' => $schedule->id(), 'schedule_revision' => $vid]));
        }
        else {
          $link = $schedule->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.business_rules_schedule.revision_revert', ['schedule' => $schedule->id(), 'schedule_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.business_rules_schedule.revision_delete', ['schedule' => $schedule->id(), 'schedule_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['schedule_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
