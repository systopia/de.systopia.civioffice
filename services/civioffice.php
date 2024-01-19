<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

// phpcs:disable Drupal.Commenting.DocComment.ContentAfterOpen
/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

use Civi\Civioffice\Api4\Action\Civioffice\RenderWebAction;
use Civi\Civioffice\EntitySubscriber\ActivityCiviOfficeTokenSubscriber;
use Civi\Civioffice\EntitySubscriber\CaseCiviOfficeTokenSubscriber;
use Civi\Civioffice\EntitySubscriber\ContactCiviOfficeTokenSubscriber;
use Civi\Civioffice\EntitySubscriber\ContributionCiviOfficeTokenSubscriber;
use Civi\Civioffice\EntitySubscriber\EventCiviOfficeTokenSubscriber;
use Civi\Civioffice\EntitySubscriber\MembershipCiviOfficeTokenSubscriber;
use Civi\Civioffice\EntitySubscriber\ParticipantCiviOfficeTokenSubscriber;
use Civi\Civioffice\Render\Queue\RenderQueueBuilderFactory;
use Civi\Civioffice\Render\Queue\RenderQueueRunner;

if (!$container->has(\CRM_Queue_Service::class)) {
  $container->autowire(\CRM_Queue_Service::class, \CRM_Queue_Service::class);
}

$container->autowire(RenderQueueBuilderFactory::class)
  ->setPublic(TRUE);
$container->autowire(RenderQueueRunner::class)
  ->setPublic(TRUE);

$container->autowire(RenderWebAction::class)
  ->setPublic(TRUE)
  ->setShared(TRUE);

$container->autowire(ActivityCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(CaseCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(ContactCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(ContributionCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(EventCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(MembershipCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(ParticipantCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
